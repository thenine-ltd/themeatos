{{-- Displays the transformable fields in a multi-select form item. For variables that can be used, see the actual view
    itself.

    Required variables:
        array<string, array<string, string>> $transformableFields
--}}

@include('form-items.combined.multi-select-with-label', [
    'options' => $transformableFields,
])