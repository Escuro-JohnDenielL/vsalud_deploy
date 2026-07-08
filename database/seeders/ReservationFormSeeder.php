<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\FormField;
use Illuminate\Database\Seeder;

class ReservationFormSeeder extends Seeder
{
    public function run(): void
    {
        $form = Form::create([
            'name'        => 'Reservation Form',
            'slug'        => 'reservation',
            'description' => 'Main reservation inquiry form for patrons and admin use.',
            'is_active'   => true,
            'is_published' => true,
        ]);

        $fields = [
            [
                'field_type' => 'text',
                'label'      => 'Name',
                'name'       => 'name',
                'placeholder' => 'Your full name',
                'required'   => true,
                'order'      => 0,
                'validation_rules' => ['required', 'string', 'max:255'],
                'is_fixed'   => true,
            ],
            [
                'field_type' => 'email',
                'label'      => 'Email',
                'name'       => 'email',
                'placeholder' => 'your@email.com',
                'required'   => true,
                'order'      => 1,
                'validation_rules' => ['required', 'email', 'max:255'],
                'is_fixed'   => true,
            ],
            [
                'field_type' => 'tel',
                'label'      => 'Contact Number',
                'name'       => 'contact_number',
                'placeholder' => 'e.g. 09171234567',
                'required'   => true,
                'order'      => 2,
                'validation_rules' => ['required', 'string', 'max:20'],
                'is_fixed'   => true,
            ],
            [
                'field_type' => 'date',
                'label'      => 'Date',
                'name'       => 'date',
                'placeholder' => null,
                'required'   => true,
                'order'      => 3,
                'validation_rules' => ['required', 'date'],
                'is_fixed'   => true,
            ],
            [
                'field_type' => 'select',
                'label'      => 'Select Period',
                'name'       => 'period',
                'placeholder' => 'Choose AM or PM',
                'required'   => true,
                'order'      => 4,
                'options'    => [
                    ['label' => 'AM', 'value' => 'AM'],
                    ['label' => 'PM', 'value' => 'PM'],
                ],
                'validation_rules' => ['required'],
                'has_other_option' => false,
                'is_fixed'   => true,
            ],
            [
                'field_type' => 'select',
                'label'      => 'Time Slot',
                'name'       => 'time',
                'placeholder' => 'Select a time slot',
                'required'   => true,
                'order'      => 5,
                'options'    => [],
                'validation_rules' => ['required'],
                'has_other_option' => false,
                'is_fixed'   => true,
            ],
            [
                'field_type' => 'select',
                'label'      => 'Venue',
                'name'       => 'venue',
                'placeholder' => 'Choose a venue',
                'required'   => true,
                'order'      => 6,
                'options'    => [
                    ['label' => 'Villa I', 'value' => 'Villa I'],
                    ['label' => 'Villa II', 'value' => 'Villa II'],
                    ['label' => 'Elizabeth Hall', 'value' => 'Elizabeth Hall'],
                    ['label' => 'Private Pool', 'value' => 'Private Pool'],
                ],
                'validation_rules' => ['required', 'string'],
                'has_other_option' => true,
                'is_fixed'   => false,
            ],
            [
                'field_type' => 'select',
                'label'      => 'Event Type',
                'name'       => 'event_type',
                'placeholder' => 'Choose event type',
                'required'   => true,
                'order'      => 7,
                'options'    => [
                    ['label' => 'Baptismal Package', 'value' => 'Baptismal Package'],
                    ['label' => 'Birthday Package', 'value' => 'Birthday Package'],
                    ['label' => 'Debut Package', 'value' => 'Debut Package'],
                    ['label' => 'Kiddie Package', 'value' => 'Kiddie Package'],
                    ['label' => 'Wedding Package', 'value' => 'Wedding Package'],
                    ['label' => 'Standard Package', 'value' => 'Standard Package'],
                ],
                'validation_rules' => ['required', 'string'],
                'has_other_option' => true,
                'is_fixed'   => false,
            ],
            [
                'field_type' => 'select',
                'label'      => 'Theme / Motif',
                'name'       => 'theme_motif',
                'placeholder' => 'Choose theme/motif',
                'required'   => true,
                'order'      => 8,
                'options'    => [
                    ['label' => 'Floral', 'value' => 'Floral'],
                    ['label' => 'Rustic', 'value' => 'Rustic'],
                    ['label' => 'Elegant', 'value' => 'Elegant'],
                    ['label' => 'Beach', 'value' => 'Beach'],
                    ['label' => 'Modern', 'value' => 'Modern'],
                ],
                'validation_rules' => ['required', 'string'],
                'has_other_option' => true,
                'is_fixed'   => false,
            ],
            [
                'field_type' => 'textarea',
                'label'      => 'Other Request',
                'name'       => 'message',
                'placeholder' => 'Please describe your specific requirements...',
                'required'   => true,
                'order'      => 9,
                'validation_rules' => ['required', 'string'],
                'is_fixed'   => false,
            ],
        ];

        foreach ($fields as $fieldData) {
            FormField::create(array_merge($fieldData, ['form_id' => $form->id]));
        }
    }
}
