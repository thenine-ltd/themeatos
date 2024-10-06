<?php

use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Objects\Transformation\Translation\TranslationService;

$service = TranslationService::getInstance();

$translationApiClients = $service->getOptionsForSelect();
$container = json_encode(['closest' => 'table']);
foreach ($translationApiClients as $apiClientKey => &$data) {
    if (!is_array($data)) continue;
    $data['dependants'] = '[".' . $apiClientKey . '"]';
    $data['container'] = $container;
}

$translationLanguages = $service->getLanguagesForView();
$clientKeys = array_keys($translationLanguages);

?>


<?php echo $__env->make('form-items.combined.select-with-label', [
    'name'  => SettingKey::WPCC_SELECTED_TRANSLATION_SERVICE,
    'title' => _wpcc('Translate with'),
    'info'  => _wpcc('Select the translation service you want to use to translate contents. The API keys will be
        retrieved from the general settings.'),
    'options' => $translationApiClients,
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>


<?php $__currentLoopData = $clientKeys; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clientKey): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        // Prepare language options
        $languagesFrom = $translationLanguages[$clientKey]['from'] ?? [];
        $languagesTo   = $translationLanguages[$clientKey]['to']   ?? [];
    ?>

    
    <?php if(!$languagesFrom || !$languagesTo): ?>
        <tr class="<?php echo e($clientKey); ?> no-langs-message">
            <td colspan="2">
                <?php echo e(_wpcc('Languages for the selected translation service are not available. Please load the languages
                    from the general settings and then refresh this page.')); ?>

            </td>
        </tr>

    <?php else: ?>
        <?php echo $__env->make('form-items.combined.multiple-select-translation-lang-pair-with-label', [
            'name'  => sprintf('%1$s%2$s', $clientKey, TranslationService::LANGS_INPUT_NAME_SUFFIX),
            'title' => _wpcc('Languages'),
            'info'  => _wpcc('Select the languages. For each language pair, the first language is the current language
                of the content, while the second language is the language the content will be translated to. You can
                enter many language pairs to translate the content multiple times. The translations will be applied in
                the specified order.'),
            'languagesFrom' => $languagesFrom,
            'languagesTo'   => $languagesTo,
            'class'         => $clientKey,
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/form-items/combined/select-translation-langs-with-label.blade.php ENDPATH**/ ?>