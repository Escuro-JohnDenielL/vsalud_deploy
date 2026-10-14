<?php

namespace App\Support;

/**
 * How the Form Builder decides whether a field is still in use.
 *
 * A field's values live in the form_data JSON blob of every submission that
 * used it, so "is this field still in use" is a text question about that blob.
 * The pattern lives here, pure and static, so the escaping can be unit tested
 * without a database.
 */
final class FormFieldUsage
{
    /**
     * The LIKE pattern that finds a stored key inside form_data.
     *
     * Anchored on the surrounding double quotes so `name` does not match
     * `full_name`, and the machine name is escaped so an underscore or percent
     * sign in it cannot act as a wildcard.
     */
    public static function jsonKeyPattern(string $name): string
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $name);

        return '%"' . $escaped . '"%';
    }
}
