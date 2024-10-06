{{-- Short code buttons with label for "create element" command. For variables that can be used, see the actual view
    itself. --}}

<?php
use WPCCrawler\Factory
?>

@include('form-items.combined.short-code-buttons-with-label', [
    // The buttons of the short codes that cannot be used under the category tab are hidden via CSS.
    'buttons' => Factory::postService()->getEditorButtonsMain(),
])