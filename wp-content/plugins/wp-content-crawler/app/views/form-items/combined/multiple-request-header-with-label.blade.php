{{--
    An element that is used to enter request header key/value pairs and a label. See
    form-items.combined.multiple-key-value-with-label for details.
--}}

@include('form-items.combined.multiple-key-value-with-label', [
    'keyPlaceholder'    => _wpcc('Header name'),
    'valuePlaceholder'  => _wpcc('Header value'),
    'hasExportButton'   => true,
    'hasImportButton'   => true,
])