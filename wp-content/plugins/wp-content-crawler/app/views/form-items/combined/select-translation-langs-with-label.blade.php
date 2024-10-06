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

{{-- TRANSLATE WITH --}}
@include('form-items.combined.select-with-label', [
    'name'  => SettingKey::WPCC_SELECTED_TRANSLATION_SERVICE,
    'title' => _wpcc('Translate with'),
    'info'  => _wpcc('Select the translation service you want to use to translate contents. The API keys will be
        retrieved from the general settings.'),
    'options' => $translationApiClients,
])

{{-- "FROM" and "TO" selects for each translation API --}}
@foreach($clientKeys as $clientKey)
    <?php
        // Prepare language options
        $languagesFrom = $translationLanguages[$clientKey]['from'] ?? [];
        $languagesTo   = $translationLanguages[$clientKey]['to']   ?? [];
    ?>

    {{-- If the languages are not available, show an information message instead of the rows --}}
    @if(!$languagesFrom || !$languagesTo)
        <tr class="{{ $clientKey }} no-langs-message">
            <td colspan="2">
                {{ _wpcc('Languages for the selected translation service are not available. Please load the languages
                    from the general settings and then refresh this page.') }}
            </td>
        </tr>

    @else
        @include('form-items.combined.multiple-select-translation-lang-pair-with-label', [
            'name'  => sprintf('%1$s%2$s', $clientKey, TranslationService::LANGS_INPUT_NAME_SUFFIX),
            'title' => _wpcc('Languages'),
            'info'  => _wpcc('Select the languages. For each language pair, the first language is the current language
                of the content, while the second language is the language the content will be translated to. You can
                enter many language pairs to translate the content multiple times. The translations will be applied in
                the specified order.'),
            'languagesFrom' => $languagesFrom,
            'languagesTo'   => $languagesTo,
            'class'         => $clientKey,
        ])
    @endif
@endforeach
