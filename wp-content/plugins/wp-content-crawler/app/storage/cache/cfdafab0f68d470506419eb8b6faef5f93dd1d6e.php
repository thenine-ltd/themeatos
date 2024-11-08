<div class="wcc-settings-title">
    <h3><?php echo e(_wpcc('API Settings')); ?></h3>
    <span><?php echo e(_wpcc('Contains settings for APIs that cannot be categorized as translation or spinning APIs')); ?></span>
</div>

<table class="wcc-settings">
    <?php echo $__env->make('partials.reference-row', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    
    <?php echo $__env->make('partials.table-section-title', [
        'title' => _wpcc("OpenAI"),
        'key'   => \WPCCrawler\Objects\Enums\SectionKey::GENERAL_SETTINGS_APIS_OPENAI,
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    
    <?php echo $__env->make('form-items.combined.input-with-label', [
        'name'  =>  \WPCCrawler\Objects\Settings\Enums\SettingKey::WPCC_API_OPENAI_SECRET_KEY,
        'title' =>  _wpcc('Secret Key'),
        'info'  =>  _wpcc("The secret key retrieved from OpenAI's website. This will be used to use OpenAI's services.")
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    
    <?php echo $__env->make('form-items.combined.button-openai-gpt-with-label', [
        'name'  =>  \WPCCrawler\Objects\Settings\Enums\SettingKey::WPCC_API_OPENAI_SECRET_KEY_TEST,
        'title' =>  _wpcc('Test'),
        'info'  =>  _wpcc("Click the button to create an OpenAI GPT short code and test it by using the secret key.")
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php

    /** @var array $settings */
    /** @var bool  $isGeneralPage */
    /** @var bool  $isOption */
    /**
     * Fires before closing table tag in APIs tab of general settings page.
     *
     * @param array $settings       Existing settings and their values saved by the user before
     * @param bool  $isGeneralPage  True if this is called from a general settings page.
     * @param bool  $isOption       True if this is an option, instead of a setting. A setting is a post meta, while
     *                              an option is a WordPress option. This is true when this is fired from general
     *                              settings page.
     * @since 1.13.0
     */
    do_action('wpcc/view/general-settings/tab/apis', $settings, $isGeneralPage, $isOption);

    ?>

</table>
<?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/general-settings/tab-apis.blade.php ENDPATH**/ ?>