{{--
    A select element that shows the available location options with a label. See
    form-items.combined.select-with-label for details.
--}}

<?php

use WPCCrawler\Objects\Html\ElementCreator;

?>

@include('form-items.combined.select-with-label', [
    'options' => ElementCreator::getLocationOptionsForSelect(),
])