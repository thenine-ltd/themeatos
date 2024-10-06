{{--
    Required variables are the same as the required variables of the parent view.
--}}

<?php

use WPCCrawler\Objects\Transformation\Translation\Clients\DeeplTranslateAPIClient;

$suffixApiKey = 'api_key';
$suffixFormality = 'formality';

?>

@extends('general-settings.translation.translation-api-settings-base', [
    'apiOptionKeySuffixes' => [$suffixApiKey],
])

@section('api-options')

    {{-- DEEPL TRANSLATE - AUTHENTICATION KEY --}}
    @include('form-items.combined.input-with-label', [
        'name'  => $service->getOptionKey($suffixApiKey, $clientClass),
        'title' => _wpcc('Authentication Key'),
        'info'  => _wpcc('Authentication key retrieved from DeepL Translate.'),
        'class' => $clientKey
    ])

    {{-- DEEPL TRANSLATE - FORMALITY --}}
    @include('form-items.combined.select-with-label', [
        'name'    => $service->getOptionKey($suffixFormality, $clientClass),
        'title'   => _wpcc('Formality'),
        'info'    => _wpcc("How formal the translation result should be. This is supported for only certain languages.")
            . ' ' . _wpcc("See the API's documentation for more information."),
        'class'   => $clientKey,
        'options' => DeeplTranslateAPIClient::getFormalityOptions(),
    ])

@overwrite