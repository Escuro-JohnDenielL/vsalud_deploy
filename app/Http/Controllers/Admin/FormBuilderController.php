<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use App\Support\FormFieldUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FormBuilderController extends Controller
{
    /**
     * List all forms.
     */
    public function index()
    {
        $forms = Form::withCount('fields')->latest()->get();
        return view('admin.it.forms.index', compact('forms'));
    }

    /**
     * Show the form builder editor (drag-and-drop field management).
     */
    public function edit(Form $form)
    {
        $form->load('fields');
        return view('admin.it.forms.edit', compact('form'));
    }

    /**
     * Add a new field to the form.
     */
    public function addField(Request $request, Form $form)
    {
        $validated = $request->validate([
            'field_type'  => 'required|in:text,textarea,email,tel,select,checkbox,radio,date,time',
            'label'       => 'required|string|max:255',
            'placeholder' => 'nullable|string|max:255',
            'required'    => 'boolean',
            'options'     => 'nullable|json',
            'has_other_option' => 'boolean',
        ]);

        $name = Str::snake(Str::lower($validated['label']));

        // Ensure unique name within this form
        $baseName = $name;
        $counter = 1;
        while (FormField::where('form_id', $form->id)->where('name', $name)->exists()) {
            $name = $baseName . '_' . $counter;
            $counter++;
        }

        $maxOrder = FormField::where('form_id', $form->id)->max('order') ?? -1;

        $field = $form->fields()->create([
            'field_type'       => $validated['field_type'],
            'label'            => $validated['label'],
            'name'             => $name,
            'placeholder'      => $validated['placeholder'] ?? null,
            'required'         => $request->boolean('required', false),
            'order'            => $maxOrder + 1,
            'options'          => $validated['options'] ? json_decode($validated['options'], true) : null,
            'validation_rules' => null,
            'has_other_option' => $request->boolean('has_other_option', false),
            'is_fixed'         => false,
            'is_active'        => true,
        ]);

        // Auto-publish: mark form as not published so superadmin can review
        $form->update(['is_published' => false]);

        return response()->json([
            'success' => true,
            'message' => "Field '{$field->label}' added.",
            'field'   => $field,
        ]);
    }

    /**
     * Update an existing field.
     */
    public function updateField(Request $request, Form $form, FormField $field)
    {
        if ($field->form_id !== $form->id) {
            abort(404);
        }

        $validated = $request->validate([
            'field_type'       => 'required|in:text,textarea,email,tel,select,checkbox,radio,date,time',
            'label'            => 'required|string|max:255',
            'name'             => 'required|string|max:255|regex:/^[a-z_][a-z0-9_]*$/',
            'placeholder'      => 'nullable|string|max:255',
            'required'         => 'boolean',
            'options'          => 'nullable|json',
            'has_other_option' => 'boolean',
            'is_active'        => 'boolean',
        ]);

        // Prevent renaming fixed fields
        if ($field->is_fixed && $field->name !== $validated['name']) {
            return response()->json([
                'success' => false,
                'message' => 'Fixed fields cannot be renamed.',
            ], 422);
        }

        // Ensure unique name within the form (excluding self)
        $exists = FormField::where('form_id', $form->id)
            ->where('name', $validated['name'])
            ->where('id', '!=', $field->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Another field already uses this machine name.',
            ], 422);
        }

        $field->update([
            'field_type'       => $validated['field_type'],
            'label'            => $validated['label'],
            'name'             => $validated['name'],
            'placeholder'      => $validated['placeholder'] ?? null,
            'required'         => $request->boolean('required', $field->required),
            'options'          => $validated['options'] ? json_decode($validated['options'], true) : $field->options,
            'has_other_option' => $request->boolean('has_other_option', $field->has_other_option),
            'is_active'        => $request->boolean('is_active', $field->is_active),
        ]);

        $form->update(['is_published' => false]);

        return response()->json([
            'success' => true,
            'message' => "Field '{$field->label}' updated.",
        ]);
    }

    /**
     * Deactivate a field.
     *
     * This used to delete the row. Every submission that used the field already
     * carries its value in the form_data JSON blob, so deleting the definition
     * only orphaned those values: the record still held them, but the builder
     * could no longer explain what they were. Deactivating instead keeps the
     * definition, removes the field from the form the patron sees (validation
     * and rendering both read activeFields()), and leaves every stored value
     * readable exactly as it was.
     *
     * The first call reports how many saved records still hold the field and
     * asks for confirmation; the confirmed call does the work.
     */
    public function deleteField(Request $request, Form $form, FormField $field)
    {
        if ($field->form_id !== $form->id) {
            abort(404);
        }

        if ($field->is_fixed) {
            return response()->json([
                'success' => false,
                'message' => 'Fixed fields cannot be deactivated.',
            ], 422);
        }

        $label = $field->label;
        $storedRecords = $this->countRecordsContainingField($field->name);

        if ($storedRecords > 0 && ! $request->boolean('confirmed')) {
            return response()->json([
                'success' => false,
                'requires_confirmation' => true,
                'count' => $storedRecords,
                'message' => "{$storedRecords} saved record(s) still contain a value for '{$label}'. "
                    . 'The field will be deactivated, not deleted, so those values are kept. Confirm to continue.',
            ], 409);
        }

        $field->update(['is_active' => false]);

        // Unpublishing keeps the change behind an explicit publish step.
        $form->update(['is_published' => false]);

        return response()->json([
            'success' => true,
            'deactivated' => true,
            'count' => $storedRecords,
            'message' => $storedRecords > 0
                ? "Field '{$label}' deactivated. {$storedRecords} saved record(s) keep their values."
                : "Field '{$label}' deactivated.",
        ]);
    }

    /**
     * How many saved records still carry a value for this field name.
     *
     * form_data is a JSON blob and is read here as text: the point is only to
     * tell the admin what a delete would have orphaned. The name is escaped so
     * an underscore in a machine name cannot act as a LIKE wildcard.
     */
    private function countRecordsContainingField(string $name): int
    {
        $needle = FormFieldUsage::jsonKeyPattern($name);

        return DB::table('inquiry')->where('form_data', 'like', $needle)->count()
            + DB::table('reservation')->where('form_data', 'like', $needle)->count();
    }

    /**
     * Reorder fields. Expects an array of field IDs in new order.
     */
    public function reorderFields(Request $request, Form $form)
    {
        $validated = $request->validate([
            'field_ids'   => 'required|array',
            'field_ids.*' => 'integer|exists:form_fields,id',
        ]);

        foreach ($validated['field_ids'] as $index => $fieldId) {
            FormField::where('id', $fieldId)
                ->where('form_id', $form->id)
                ->update(['order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fields reordered.',
        ]);
    }

    /**
     * Preview the form as it would appear to users.
     */
    public function preview(Form $form)
    {
        $form->load('activeFields');
        return view('admin.it.forms.preview', compact('form'));
    }

    /**
     * Toggle publish status.
     */
    public function publish(Form $form)
    {
        $form->update([
            'is_published' => !$form->is_published,
        ]);

        $status = $form->is_published ? 'published' : 'unpublished';

        return response()->json([
            'success' => true,
            'message' => "Form '{$form->name}' {$status}.",
            'is_published' => $form->is_published,
        ]);
    }

    /**
     * Get form definition as JSON (for API / JS consumption).
     */
    public function getFormDefinition(Form $form)
    {
        $form->load('activeFields');

        return response()->json($form);
    }
}
