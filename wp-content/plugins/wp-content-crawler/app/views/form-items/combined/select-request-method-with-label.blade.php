{{--
    A select element that shows the available request methods and a label. See form-items.combined.select-with-label
    for details.
--}}

@include('form-items.combined.select-with-label', [
    'options' => \WPCCrawler\Objects\Enums\RequestMethod::getRequestMethodsForSelect(),
])