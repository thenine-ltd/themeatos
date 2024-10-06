<?php

use WPCCrawler\Objects\Transformation\Translation\Clients\DeeplTranslateAPIClient;

$suffixApiKey = 'api_key';
$suffixFormality = 'formality';

?>



<?php $__env->startSection('api-options'); ?>

    
    <?php echo $__env->make('form-items.combined.input-with-label', [
        'name'  => $service->getOptionKey($suffixApiKey, $clientClass),
        'title' => _wpcc('Authentication Key'),
        'info'  => _wpcc('Authentication key retrieved from DeepL Translate.'),
        'class' => $clientKey
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    
    <?php echo $__env->make('form-items.combined.select-with-label', [
        'name'    => $service->getOptionKey($suffixFormality, $clientClass),
        'title'   => _wpcc('Formality'),
        'info'    => _wpcc("How formal the translation result should be. This is supported for only certain languages.")
            . ' ' . _wpcc("See the API's documentation for more information."),
        'class'   => $clientKey,
        'options' => DeeplTranslateAPIClient::getFormalityOptions(),
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php $__env->stopSection(true); ?>
<?php echo $__env->make('general-settings.translation.translation-api-settings-base', [
    'apiOptionKeySuffixes' => [$suffixApiKey],
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/general-settings/translation/settings-deepl_translate.blade.php ENDPATH**/ ?>