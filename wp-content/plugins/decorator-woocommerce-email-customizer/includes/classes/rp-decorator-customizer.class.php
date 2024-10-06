<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Customizer Setup
 *
 * @class RP_Decorator_Customizer
 * @package Decorator
 * @author WebToffee
 */
if (!class_exists('RP_Decorator_Customizer')) {

    class RP_Decorator_Customizer {

        // Properties
        private static $panels_added = array();
        private static $sections_added = array();
        private static $css_suffixes = null;
        public static $customizer_url = null;
        public static $wt_template_type = null;
        public static $wt_template_object = null;
        // Singleton instance
        private static $instance = false;

        /**
         * Singleton control
         */
        public static function get_instance() {
            if (!self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Class constructor
         *
         * @access public
         * @return void
         */
        public function __construct() {
            
            if(isset($_REQUEST['kt-woomail-preview']) && ($_REQUEST['kt-woomail-preview'] === '1') ){
                return ;
            }   
            
             // pre built template 
            $old_style_preview = get_option('wt_decorator_old_custom_styles','');
            if(!empty($old_style_preview) && isset($old_style_preview['prebuilt_template_preview_loop'])&& isset($_GET['pre_built_template'])&& !empty($_GET['pre_built_template'])){
                unset($old_style_preview['prebuilt_template_preview_loop']);
                update_option('wt_decorator_old_custom_styles', $old_style_preview);
            }else{
                if(!empty($old_style_preview) && isset($old_style_preview['prebuilt_template_preview'])&& isset($_GET['pre_built_template'])&& !empty($_GET['pre_built_template'])){
                    unset($old_style_preview['prebuilt_template_preview']);
                    update_option('wt_decorator_old_custom_styles', $old_style_preview);
                } elseif (!empty($old_style_preview) && !isset($old_style_preview['prebuilt_template_preview'])&& isset($_GET['pre_built_template'])&& !empty($_GET['pre_built_template'])) {
                    if(!isset($_POST['customized'])){
                        foreach ($old_style_preview as $key => $value) {
                            if($key == 'data'){
                                update_option('wt_decorator_custom_styles', $value);
                                continue;
                            }
                            update_option($key, $value);
                        }
                        delete_option('wt_decorator_old_custom_styles');
                    }
                }
            }
            
            // Add settings
            add_action('customize_register', array($this, 'add_settings'));

            // Maybe add custom styles to default WooCommerce styles
            add_filter('woocommerce_email_styles', array($this, 'maybe_add_custom_styles'), 9999);

           // add_filter('woocommerce_email_from_address', array($this, 'maybe_add_custom_temp_style'), 9999, 3);

            add_action('woocommerce_email_header', array($this, 'wt_email_header_before'), 1, 2);
            
            add_action('woocommerce_email_header', array($this, 'maybe_add_custom_temp_style'), 1, 2);
            // Ajax handler
            add_action('wp_ajax_rp_decorator_reset', array($this, 'ajax_reset'));

            add_action('wp_ajax_rp_decorator_set_as_default', array($this, 'wt_decorator_set_as_default'));

            // Ajax handler
            add_action('wp_ajax_rp_decorator_button_text', array($this, 'wt_decorator_button_text'));

            add_action('wp_ajax_rp_decorator_delete_autosave_post', array($this, 'wt_decorator_delete_autosave_post'));

            // Ajax handler
            add_action('wp_ajax_wt_send_test_email', array($this, 'wt_ajax_send_user_email'));
            
            // Ajax handler
            add_action('wp_ajax_wt_apply_prebult_template', array($this, 'wt_apply_prebult_template'));
            
            add_action('wp_ajax_wt_send_reset_slider', array($this, 'wt_ajax_wt_send_reset_slider'));

            // Only proceed if this is own request
            if (!RP_Decorator::is_own_customizer_request() && !RP_Decorator::is_own_preview_request()) {
                return;
            }

            // Add controls, sections and panels
            add_action('customize_register', array($this, 'add_controls'));

            // Add user capability
            add_filter('user_has_cap', array($this, 'add_customize_capability'), 99);

            // Change site name
            add_filter('option_blogname', array($this, 'change_site_name'), 99);

            // Remove unrelated components
            add_filter('customize_loaded_components', array($this, 'remove_unrelated_components'), 99, 2);

            // Remove unrelated sections
            add_filter('customize_section_active', array($this, 'remove_unrelated_sections'), 99, 2);

            // Remove unrelated controls
            add_filter('customize_control_active', array($this, 'remove_unrelated_controls'), 99, 2);

            // Enqueue Customizer scripts
            add_filter('customize_controls_enqueue_scripts', array($this, 'enqueue_customizer_scripts'));
        }

        /**
         * Add customizer capability
         *
         * @access public
         * @param array $capabilities
         * @return array
         */
        public function add_customize_capability($capabilities) {
            // Remove filter (circular reference)
            remove_filter('user_has_cap', array($this, 'add_customize_capability'), 99);

            // Add customize capability for admin user if this is own customizer request
            if (RP_Decorator::is_admin() && RP_Decorator::is_own_customizer_request()) {
                $capabilities['customize'] = true;
            }

            // Add filter
            add_filter('user_has_cap', array($this, 'add_customize_capability'), 99);

            // Return capabilities
            return $capabilities;
        }

        /**
         * Get Customizer URL
         *
         * @access public
         * @return string
         */
        public static function get_customizer_url() {
            if (RP_Decorator_Customizer::$customizer_url === null) {
                RP_Decorator_Customizer::$customizer_url = add_query_arg(array(
                    'rp-decorator-customize' => '1',
                    'url' => urlencode(add_query_arg(array('rp-decorator-preview' => '1'), site_url('/'))),
                    'return' => urlencode(RP_Decorator_WC::get_email_settings_page_url()),
                        ), admin_url('customize.php'));
            }

            return RP_Decorator_Customizer::$customizer_url;
        }

        /**
         * Change site name
         *
         * @access public
         * @param string $name
         * @return string
         */
        public function change_site_name($name) {
            return __('WooCommerce Emails', 'decorator-woocommerce-email-customizer');
        }

        /**
         * Remove unrelated components
         *
         * @access public
         * @param array $components
         * @param object $wp_customize
         * @return array
         */
        public function remove_unrelated_components($components, $wp_customize) {
            // Iterate over components
            foreach ($components as $component_key => $component) {

                // Check if current component is own component
                if (!RP_Decorator_Customizer::is_own_component($component)) {
                    unset($components[$component_key]);
                }
            }

            // Return remaining components
            return $components;
        }

        /**
         * Remove unrelated sections
         *
         * @access public
         * @param bool $active
         * @param object $section
         * @return bool
         */
        public function remove_unrelated_sections($active, $section) {

            // Check if current section is own section
            if (!RP_Decorator_Customizer::is_own_section($section->id)) {
                return false;
            }

            // We can override $active completely since this runs only on own Customizer requests
            return true;
        }

        /**
         * Remove unrelated controls
         *
         * @access public
         * @param bool $active
         * @param object $control
         * @return bool
         */
        public function remove_unrelated_controls($active, $control) {
            // Check if current control belongs to own section
            if (!RP_Decorator_Customizer::is_own_section($control->section)) {
                return false;
            }

            // We can override $active completely since this runs only on own Customizer requests
            return true;
        }

        /**
         * Check if current component is own component
         *
         * @access public
         * @param string $component
         * @return bool
         */
        public static function is_own_component($component) {
            return false;
        }

        /**
         * Check if current section is own section
         *
         * @access public
         * @param string $key
         * @return bool
         */
        public static function is_own_section($key) {
            // Iterate over own sections
            foreach (RP_Decorator_Settings::get_sections() as $section_key => $section) {
                if ($key === 'rp_decorator_' . $section_key) {
                    return true;
                }
            }

            // Section not found
            return false;
        }

        /**
         * Enqueue Customizer scripts
         *
         * @access public
         * @return void
         */
        public function enqueue_customizer_scripts() {
            // Enqueue Customizer script
            wp_enqueue_script('rp-decorator-customizer-scripts', RP_DECORATOR_PLUGIN_URL . '/assets/js/customizer-scripts.js', array('jquery'), RP_DECORATOR_VERSION, true);
            wp_enqueue_style('customizer-main', RP_DECORATOR_PLUGIN_URL . '/assets/css/customizer-main.css', array(), RP_DECORATOR_VERSION);

            // Send variables to Javascript
            wp_localize_script('rp-decorator-customizer-scripts', 'rp_decorator', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce( 'wt_rp_admin_nonce' ),
                'customizer_url' => RP_Decorator_Customizer::get_customizer_url(),
                'labels' => array(
                    'reset' => sprintf(__('%sReset', 'decorator-woocommerce-email-customizer'),'&nbsp;&nbsp;'),
                    'send_mail' => __('Send test', 'decorator-woocommerce-email-customizer'),
                    'reset_confirmation' => __('Are you sure you want to reset all changes made to your WooCommerce emails?', 'decorator-woocommerce-email-customizer'),
                    'description' => __('<p>Use native WordPress Customizer to make WooCommerce emails match your brand.</p>', 'decorator-woocommerce-email-customizer') . '<p>' . sprintf(__('<a href="%s">Decorator</a> plugin by <a href="%s">WebToffee</a>.', 'decorator-woocommerce-email-customizer'), 'https://wordpress.org/plugins/decorator-woocommerce-email-customizer', 'https://www.webtoffee.com') . '</p>',
                    'wt_logo' => RP_DECORATOR_PLUGIN_URL . '/assets/images/webtoffee-logo_small.svg',
                    'wt_plugin_name' => 'Decorator by',
                    'sent' => __('Email Sent!', 'decorator-woocommerce-email-customizer'),
                    'failed' => __('Email failed, make sure you have a working email server for your site.', 'decorator-woocommerce-email-customizer'),
                    'wt_rest_btn_icon' => RP_DECORATOR_PLUGIN_URL . '/assets/images/wt_rest_btn_icon.svg',
                    'green_tic' => RP_DECORATOR_PLUGIN_URL . '/assets/images/green-tic.svg',
                    'red_cross' => RP_DECORATOR_PLUGIN_URL . '/assets/images/red-cross.svg',
                    'email_type_lbl' => __('Email type', 'decorator-woocommerce-email-customizer'),
                    'choose_order_lbl' => __('Choose order', 'decorator-woocommerce-email-customizer'),
                    'wt_beforeunload' => __('Changes that you made may not be saved.', 'decorator-woocommerce-email-customizer'),

                ),
            ));
        }

        /**
         * Add settings
         *
         * @access public
         * @param object $wp_customize
         * @return void
         */
        public function add_settings($wp_customize) {
            // Iterate over settings
            foreach (RP_Decorator_Settings::get_settings() as $setting_key => $setting) {

                // Add setting
                $wp_customize->add_setting('rp_decorator[' . $setting_key . ']', array(
                    'type' => 'option',
                    'transport' => isset($setting['transport']) ? $setting['transport'] : 'postMessage',
                    'capability' => RP_Decorator::get_admin_capability(),
                    'default' => isset($setting['default']) ? $setting['default'] : '',
                ));
            }

            // Iterate over settings
            foreach (RP_Decorator_Settings::wt_get_custom_text_edit_settings() as $setting_key => $setting) {
                // Add setting
                $wp_customize->add_setting(
                        $setting_key, array(
                    'type' => 'option',
                    'transport' => isset($setting['transport']) ? $setting['transport'] : 'postMessage',
                    'capability' => RP_Decorator::get_admin_capability(),
                    'default' => isset($setting['default']) ? $setting['default'] : '',
                        )
                );
            }
        }

        /**
         * Add controls, sections and panels
         *
         * @access public
         * @param object $wp_customize
         * @return void
         */
        public function add_controls($wp_customize) {
            
            // Iterate over settings
            foreach (RP_Decorator_Settings::get_settings() as $setting_key => $setting) {

                // Maybe add section
                RP_Decorator_Customizer::maybe_add_section($wp_customize, $setting);

                // Maybe add panel
                RP_Decorator_Customizer::maybe_add_panel($wp_customize, $setting);

                // Get control class name (none, color, upload, image)
                $control_class = isset($setting['control_type']) ? ucfirst($setting['control_type']) . '_' : '';
                $control_class = 'WP_Customize_' . $control_class . 'Control';
                // Control configuration
                $control_config = array(
                    'label' => isset($setting['title']) ? $setting['title'] : '',
                    'settings' => 'rp_decorator[' . $setting_key . ']',
                    'capability' => RP_Decorator::get_admin_capability(),
                    'priority' => isset($setting['priority']) ? $setting['priority'] : 10,
                );

                // Description
                if (!empty($setting['description'])) {
                    $control_config['description'] = $setting['description'];
                }

                // Add control to section
                if (!empty($setting['section'])) {
                    $control_config['section'] = 'rp_decorator_' . $setting['section'];
                }

                // Add control to panel
                if (!empty($setting['panel'])) {
                    $control_config['panel'] = 'rp_decorator_' . $setting['panel'];
                }

                // Add custom field type
                if (!empty($setting['type'])) {
                    $control_config['type'] = $setting['type'];
                }

                // Add select field options
                if (!empty($setting['choices'])) {
                    $control_config['choices'] = $setting['choices'];
                }

                // Input attributes
                if (!empty($setting['input_attrs'])) {
                    $control_config['input_attrs'] = $setting['input_attrs'];
                }

                // Add control
                $wp_customize->add_control(new $control_class($wp_customize, 'rp_decorator_' . $setting_key, $control_config));
            }


            $wt_custom_style = RP_Decorator_Customizer::$wt_template_type;
            if (empty($wt_custom_style)) {

                $wt_custom_style = RP_Decorator_Customizer::wt_get_current_template();
            }

            // Iterate over settings
            foreach (RP_Decorator_Settings::wt_get_custom_text_edit_settings($wt_custom_style) as $setting_key => $setting) {

                // Maybe add section
                RP_Decorator_Customizer::maybe_add_section($wp_customize, $setting);

                // Maybe add panel
                RP_Decorator_Customizer::maybe_add_panel($wp_customize, $setting);

                // Get control class name (none, color, upload, image)
                $control_class = isset($setting['control_type']) ? ucfirst($setting['control_type']) . '_' : '';
                $control_class = 'WP_Customize_' . $control_class . 'Control';

                // Control configuration
                $control_config = array(
                    'label' => $setting['title'],
                    'settings' => $setting_key,
                    'capability' => RP_Decorator::get_admin_capability(),
                    'priority' => isset($setting['priority']) ? $setting['priority'] : 10,
                );

                // Description
                if (!empty($setting['description'])) {
                    $control_config['description'] = $setting['description'];
                }

                // Add control to section
                if (!empty($setting['section'])) {
                    $control_config['section'] = 'rp_decorator_' . $setting['section'];
                }

                // Add control to panel
                if (!empty($setting['panel'])) {
                    $control_config['panel'] = 'rp_decorator_' . $setting['panel'];
                }
                // Add custom field type
                if (!empty($setting['type'])) {
                    $control_config['type'] = $setting['type'];
                }
                // Add custom field type
                if (!empty($setting['label'])) {
                    $control_config['label'] = $setting['label'];
                }

                // Add select field options
                if (!empty($setting['choices'])) {
                    $control_config['choices'] = $setting['choices'];
                }
                // Input attributese
                if (!empty($setting['input_attrs'])) {
                    $control_config['input_attrs'] = $setting['input_attrs'];
                }

                // Add control
                $wp_customize->add_control(new $control_class($wp_customize, $setting_key, $control_config));
            }
        }

        /**
         * Maybe add section
         *
         * @access public
         * @param object $wp_customize
         * @param array $child
         * @return void
         */
        public static function maybe_add_section($wp_customize, $child) {
            // Get sections
            $sections = RP_Decorator_Settings::get_sections();

            // Check if section is set and exists
            if (!empty($child['section']) && isset($sections[$child['section']])) {

                // Reference current section key
                $section_key = $child['section'];

                // Check if section was not added yet
                if (!in_array($section_key, self::$sections_added, true)) {

                    // Reference current section
                    $section = $sections[$section_key];

                    // Section config
                    $section_config = array(
                        'title' => $section['title'],
                        'priority' => (isset($section['priority']) ? $section['priority'] : 10),
                    );

                    // Description
                    if (!empty($section['description'])) {
                        $section_config['description'] = $section['description'];
                    }

                    // Maybe add panel
                    RP_Decorator_Customizer::maybe_add_panel($wp_customize, $section);

                    // Maybe add section to panel
                    if (!empty($section['panel'])) {
                        $section_config['panel'] = 'rp_decorator_' . $section['panel'];
                    }

                    // Register section
                    $wp_customize->add_section('rp_decorator_' . $section_key, $section_config);

                    // Track which sections were added
                    self::$sections_added[] = $section_key;
                }
            }
        }

        /**
         * Maybe add panel
         *
         * @access public
         * @param object $wp_customize
         * @param array $child
         * @return void
         */
        public static function maybe_add_panel($wp_customize, $child) {
            // Get panels
            $panels = RP_Decorator_Settings::get_panels();

            // Check if panel is set and exists
            if (!empty($child['panel']) && isset($panels[$child['panel']])) {

                // Reference current panel key
                $panel_key = $child['panel'];

                // Check if panel was not added yet
                if (!in_array($panel_key, self::$panels_added, true)) {

                    // Reference current panel
                    $panel = $panels[$panel_key];

                    // Panel config
                    $panel_config = array(
                        'title' => $panel['title'],
                        'priority' => (isset($panel['priority']) ? $panel['priority'] : 10),
                        'capability' => RP_Decorator::get_admin_capability(),
                    );

                    // Panel description
                    if (!empty($panel['description'])) {
                        $panel_config['description'] = $panel['description'];
                    }

                    // Register panel
                    $wp_customize->add_panel('rp_decorator_' . $panel_key, $panel_config);

                    // Track which panels were added
                    self::$panels_added[] = $panel_key;
                }
            }
        }

        /**
         * Get styles string
         *
         * @access public
         * @param bool $add_custom_css
         * @return string
         */
        public static function get_styles_string($add_custom_css = true) {
            $styles_array = array();
            $styles = '';

            // Iterate over settings
            foreach (RP_Decorator_Settings::get_settings() as $setting_key => $setting) {

                // Only add CSS properties
                if (isset($setting['live_method']) && $setting['live_method'] === 'css') {

                    // Iterate over selectors
                    foreach ($setting['selectors'] as $selector => $properties) {

                        // Iterate over properties
                        foreach ($properties as $property) {

                            // Add value to styles array
                            $styles_array[$selector][$property] = RP_Decorator_Customizer::opt($setting_key, $selector, $property);
                        }
                    }
                }
            }

            // Join property names with values
            foreach ($styles_array as $selector => $properties) {

                // Open selector
                $styles .= $selector . '{';

                foreach ($properties as $property_key => $property_value) {

                    // Add property
                    $styles .= $property_key . ':' . $property_value . ';';
                }

                // Close selector
                $styles .= '}';
            }

            // Add custom CSS
            if ($add_custom_css) {
                $styles .= RP_Decorator_Customizer::opt('custom_css');
            }

            // Return styles string
            return $styles;
        }

        /**
         * Get value for use in templates
         *
         * @access public
         * @param string $key
         * @param string $selector
         * @return string
         */
        public static function opt($key, $selector = null, $property = null) {
            $wt_custom_preview = '';
            if (isset($_POST['customized']) && !empty($_POST['customized'])) {
                $data = json_decode(wp_unslash($_POST['customized']), true);
                if (isset($data['rp_decorator[preview_order_id]']) && $data['rp_decorator[preview_order_id]']) {
                    $wt_custom_preview = isset($data['rp_decorator[preview_order_id]']) ? $data['rp_decorator[preview_order_id]'] : '';
                    if (!isset($_SESSION)) {
                        session_start();
                    }
                    $_SESSION["preview_order_id"] = $wt_custom_preview;
                } elseif (!isset($_SESSION["preview_order_id"]) && !empty($_SESSION["preview_order_id"])) {
                    $wt_custom_preview = $_SESSION["preview_order_id"];
                }
            }
            $wt_custom_style = self::$wt_template_type;
            if (empty($wt_custom_style)) {
                $wt_custom_style = self::wt_get_current_template();
            }

            $default_template_value = (array) get_option('wt_decorator_default_template_value', array());
            $default_template_value = array_filter($default_template_value);
            $save_btn_text = isset($default_template_value['save_btn_text']) && !empty($default_template_value['save_btn_text']) ? $default_template_value['save_btn_text'] : 'Publish';
            $default_template = isset($default_template_value['email_type']) && !empty($default_template_value['email_type']) ? $default_template_value['email_type'] : 'new_order';
            if (isset($default_template_value) && !empty($default_template_value)) {
                $wt_custom_data_draft = (array) get_option('wt_decorator_custom_styles_in_draft', array());
                $wt_custom_data_published = (array) get_option('wt_decorator_custom_styles', array());
                $wt_custom_data_scheduled = (array) get_option('wt_decorator_custom_styles_scheduled', array());
                $wc_emails = WC_Emails::instance();
                $emails = $wc_emails->get_emails();
                $image_link_btn = get_option('wt_decorator_' . $default_template . '_image_link_btn_switch');
                if ($save_btn_text == 'draft') {

                    $default_style = $wt_custom_data_draft[$default_template];
                    if($image_link_btn){
                        $default_style['image_link'] = TRUE;
                    }
                    $draft_arr = array();
                    if (isset($emails) && !empty($emails)) {
                        foreach ($emails as $email_key => $email) {
                            if (isset($email->id)) {
                                $default_style['email_type'] = $email->id;
                                $draft_arr[$email->id] = $default_style;
                            }
                        }
                    }
                    foreach (RP_Decorator_Preview::get_email_types() as $key => $value) {
                        if (!array_key_exists($key, $draft_arr)) {
                            $draft_arr[$key] = $default_style;
                        }
                    }
                    update_option('wt_decorator_custom_styles_in_draft', $draft_arr);
                    update_option('wt_decorator_custom_styles_scheduled', array());
                } else if ($save_btn_text == 'publish') {

                    $default_style = $wt_custom_data_published[$default_template];
                    if($image_link_btn){
                        $default_style['image_link'] = TRUE;
                    }
                    $publish_arr = array();
                    if (isset($emails) && !empty($emails)) {
                        foreach ($emails as $email_key => $email) {
                            if (isset($email->id)) {
                                $default_style['email_type'] = $email->id;
                                $publish_arr[$email->id] = $default_style;
                            }
                        }
                    }
                    foreach (RP_Decorator_Preview::get_email_types() as $key => $value) {
                        if (!array_key_exists($key, $publish_arr)) {
                            $publish_arr[$key] = $default_style;
                        }
                    }
                    update_option('wt_decorator_custom_styles', $publish_arr);
                    update_option('wt_decorator_custom_styles_in_draft', array());
                    update_option('wt_decorator_custom_styles_scheduled', array());
                }
                update_option('wt_decorator_default_template_value', array());
            }

            // Get raw value
            $stored_value = RP_Decorator_Customizer::get_stored_value($key, RP_Decorator_Settings::get_default_value($key), $wt_custom_style, $wt_custom_preview);

            // Prepare value
            $value = RP_Decorator_Customizer::prepare($key, $stored_value, $selector, $property);

            // Allow developers to override
            return apply_filters('rp_decorator_option_value', $value, $key, $selector, $stored_value);
        }

        /**
         * Get current template
         *
         * @access public
         * @param string $key
         * @param string $selector
         * @return string
         */
        public static function wt_get_current_template() {
            $wt_custom_preview = '';
            $last_template = get_option('wt_decorator_last_selected_template') && array_key_exists(get_option('wt_decorator_last_selected_template'), RP_Decorator_Preview::get_email_types()) ? get_option('wt_decorator_last_selected_template') : 'new_order';

            if ((isset($_POST['customized']) && !empty($_POST['customized']))) {
                $data = json_decode(wp_unslash($_POST['customized']), true);
                if (isset($data['rp_decorator[email_type]'])) {
                    $wt_custom_style = isset($data['rp_decorator[email_type]']) ? $data['rp_decorator[email_type]'] : '';
                    update_option('wt_decorator_last_selected_template', $wt_custom_style);
                    unset($_POST['customized']);
                }
            }
            if (empty($wt_custom_style)) {
                $wt_custom_style = get_option('wt_decorator_last_selected_template') && array_key_exists(get_option('wt_decorator_last_selected_template'), RP_Decorator_Preview::get_email_types()) ? get_option('wt_decorator_last_selected_template') : 'new_order';
            }
            return $wt_custom_style;
        }

        /**
         * Get value stored in database
         *
         * @access public
         * @param string $key
         * @param string $default
         * @return string
         */
        public static function get_stored_value($key, $default = '', $wt_custom_style = 'new_order', $wt_custom_preview = '') {
            
            if(isset($_REQUEST['kt-woomail-preview']) && ($_REQUEST['kt-woomail-preview'] === '1') ){
                return $default;
            }

            if ($wt_custom_style == 'customer_partially_refunded_order') {
                $wt_custom_style = 'customer_refunded_order';
            }
            // Get all stored values
            $stored_values = (array) get_option('wt_decorator_custom_styles', array());
            $screen_value = (array) get_option('rp_decorator', array());
            if (isset($stored_values[$wt_custom_style]) && !empty($stored_values[$wt_custom_style])) {
                $stored = $stored_values[$wt_custom_style];
            } else {
                $stored = (array) get_option('rp_decorator', array());
            }
            $drafted_values = get_option('wt_decorator_custom_styles_in_draft', array());
            $scheduled_values = get_option('wt_decorator_custom_styles_scheduled', array());
            if (isset($drafted_values[$wt_custom_style]) && !empty($drafted_values[$wt_custom_style]) && (isset($_REQUEST['rp-decorator-preview']) && $_REQUEST['rp-decorator-preview'] == '1' || isset($_REQUEST['action']) && $_REQUEST['action'] == 'wt_send_test_email')) {
                $stored = $drafted_values[$wt_custom_style];
            } elseif (isset($scheduled_values[$wt_custom_style]) && !empty($scheduled_values[$wt_custom_style]) && (isset($_REQUEST['rp-decorator-preview']) && $_REQUEST['rp-decorator-preview'] == '1')) {
                $stored = $scheduled_values[$wt_custom_style];
            }
            if (isset($wt_custom_preview) && !empty($wt_custom_preview)) {
                $stored['preview_order_id'] = $wt_custom_preview;
            }
            if (isset($screen_value) && isset($screen_value[$key]) && $default != $screen_value[$key]) {
                $stored[$key] = $screen_value[$key];
            }
            // Check if value exists in stored values array
            if (!empty($stored) && isset($stored[$key])) {  
                if($key == 'email_type' && isset($stored['email_type']) && $stored['email_type'] != $wt_custom_style){
                    return $wt_custom_style;
                }else{
                    return $stored[$key];
                }
            }

            // Stored value not found, use default value
            return $default;
        }

        /**
         * Prepare value for use in HTML
         *
         * @access public
         * @param string $key
         * @param string $value
         * @param string $selector
         * @return string
         * 
         * @since 1.2.5   Webtoffee Quote plugin button compatibility
         */
        public static function prepare($key, $value, $selector = null, $property = null) {
            // Append CSS suffix to value
            $value .= RP_Decorator_Customizer::get_css_suffix($key);

            // Special case for border_radius #template_header
            if ($key === 'border_radius' && $selector === '#template_header') {
                $value = trim(str_replace('!important', '', $value));
                $value = $value . ' ' . $value . ' 0 0 !important';
            }
            
            // Special case for border_radius #template_container
            if ($key === 'border_radius' && $selector == 'body #template_header') {
                $value = trim(str_replace('!important', '', $value));
                $value = trim(str_replace('px', '', $value));
                if((int) $value >5){
                $value = (int) $value -10 .'px';
                }else{
                    $value = (int) $value .'px';
                }
                $value = $value . '!important';
            }

            if ($key === 'border_radius' && $selector == 'body #wt_wrapper_table') {
                $value = trim(str_replace('!important', '', $value));
                $value = trim(str_replace('px', '', $value));
                if((int) $value >5){
                    $value = (int) $value +5 .'px';
                }
                $value = $value . '!important';
            }

            if ($key == 'social_links_align' && $selector == '#template_footer #wt_social_footer') {
                 $value = trim(str_replace('!important', '', $value));
                 if($value == 'center'){
                     $value = '0 auto 0 auto !important';
                 }elseif ($value == 'right') {
                    $value = '0 0 0 auto !important';
                } else {
                    $value = '0 auto 0 0 !important';
                }
            }
            
            if ($key == 'heading_text_shadow') {
                 $value = trim(str_replace('!important', '', $value));
                 $value = '0 1px 0 ' . $value . ' !important';
            }
            
            
            // Special case for email_padding #wrapper
            if ($key === 'email_padding' && $selector === '#wrapper') {
                // $value = trim(str_replace('!important', '', $value));
                $value = $value . ' 0 ' . $value . ' 0';
            }
            
              // Special case for email_padding #wrapper
            if ($key == 'footer_show' || $key === 'order_items_show' || $key == 'header_show' || $key === 'billing_address_show' || $key === 'shipping_address_show') {
                // $value = trim(str_replace('!important', '', $value));
                if($value == 'true' || $value == true || $value == 1){
                    $value = 'auto';
                }else{
                    if($selector === '#wt_header_wrapper' || $selector == '#wt_billing_address_wrap' || $selector == '#wt_shipping_address_wrap' || $selector == '#wt_order_items_table'){
                        if($property == 'overflow'){
                          $value = 'hidden;' ;
                        }else{
                          $value = '0px !important;' ;
                        }
                    }
                    if($selector == '#wt_wrapper_table #wt_template_footers'){
                        if($property == 'overflow'){
                          $value = 'hidden;' ;
                        }else{
                          $value = '0px !important;' ;
                        }
                    }
                }
            }

            // Special case for footer_padding #template_footer #credit
            if ($key === 'footer_padding' && $selector === '#template_footer #credit') {
                $value = '0 ' . $value . ' ' . $value . ' ' . $value;
            }

            if ($key === 'header_image_maxwidth' && $selector === '#template_header_image') {
               $wt_custom_style = self::$wt_template_type;
                if (empty($wt_custom_style)) {
                    $wt_custom_style = self::wt_get_current_template();
                }
                // Get raw value
               $stored_value = RP_Decorator_Customizer::get_stored_value('email_width', RP_Decorator_Settings::get_default_value('email_width'), $wt_custom_style, '');
               $value = trim(str_replace('!important', '', $value));
                $value = trim(str_replace('px', '', $value));
                if($value < $stored_value){
               $value = $stored_value .'px' ;
               }
            }

            // Special case for shadow
            if ($key === 'shadow') {
                $value = '0 ' . ($value > 0 ? 1 : 0) . 'px ' . ($value * 4) . 'px ' . $value . 'px rgba(0,0,0,0.1) !important';
            }

            // Font family
            if (substr($key, -11) === 'font_family') {
                $value = isset(RP_Decorator_Settings::$font_family_mapping[$value]) ? RP_Decorator_Settings::$font_family_mapping[$value] : $value;
            }

            if ($key === 'font_family' && ($selector === '#body_content_inner h2' || $selector === '.td') ||$selector == 'button') {
                $value = trim(str_replace('!important', '', $value));
                $value = $value . ' !important';
            }

            if ($key === 'text_color' && $selector === '#body_content_inner h2' ||$selector == 'button') {
                $value = trim(str_replace('!important', '', $value));
                $value = $value . ' !important';
            }

            if ($key == 'button_color' && ($selector == 'a.wt_template_button'||$selector == 'a.button' ||$selector == 'button')) {
                $value = trim(str_replace('!important', '', $value));
                $value = $value . ' !important';
            }
            
             if ($key == 'button_bg_color' && $selector == 'a.button' ||$selector == 'button') {
                $value = trim(str_replace('!important', '', $value));
                $value = $value . ' !important';
            }
             if ($key === 'border_color') {
                $value = trim(str_replace('!important', '', $value));
                $value = $value . ' !important';
            }

            if ($key == 'footer_background_color' ) {
                $value = trim(str_replace('!important', '', $value));
                $value = $value . ' !important';
            }
            
             if ($key == 'header_image_maxheight' ) {
                $value = trim(str_replace('!important', '', $value));
                if(strstr($value, 'auto')){
                    $value = trim(str_replace('px', '', $value)); 
                }
            }
            // Return prepared value
            return $value;
        }

        /**
         * Get CSS suffix by key or all CSS suffixes
         *
         * @access public
         * @param string $key
         * @return mixed
         */
        public static function get_css_suffix($key = null) {
            // Define CSS suffixes
            if (self::$css_suffixes === null) {
                self::$css_suffixes = array(
                    'email_padding' => 'px',
                    'content_padding_top' => 'px',
                    'content_padding_bottom' => 'px',
                    'content_padding_left' => 'px',
                    'content_padding_right' => 'px',
                    'email_width' => 'px !important',
                    'border_width' => 'px',
                    'border_radius' => 'px !important',
                    'header_image_maxwidth' => 'px !important',
                    'header_image_maxheight' => 'px !important',
                    'header_image_padding_top_bottom' => 'px !important',
                    'header_padding_top_bottom' => 'px',
                    'header_padding_left_right' => 'px',
                    'heading_font_size' => 'px',
                    'footer_padding' => 'px',
                    'footer_top_bottom_padding' => 'px',
                    'footer_left_right_padding' => 'px !important',
                    'footer_font_size' => 'px',
                    'h1_font_size' => 'px',
                    'h2_font_size' => 'px',
                    'h3_font_size' => 'px',
                    'h4_font_size' => 'px',
                    'h5_font_size' => 'px',
                    'h6_font_size' => 'px',
                    'h1_separator_width' => 'px',
                    'h2_separator_width' => 'px',
                    'h3_separator_width' => 'px',
                    'h4_separator_width' => 'px',
                    'h5_separator_width' => 'px',
                    'h6_separator_width' => 'px',
                    'font_size' => 'px',
                    'items_table_border_width' => 'px',
                    'items_table_separator_width' => 'px',
                    'items_table_padding' => 'px',
                    'subtitle_font_size' => 'px !important',
                    'address_box_border_width' => 'px !important',
                    'address_box_padding_left_right' => 'px !important',
                    'address_box_padding_top_bottom' => 'px !important',
                    'button_border_width' => 'px !important',
                    'button_border_radius' => 'px !important',
                    'button_left_right_padding' => 'px !important',
                    'button_top_bottom_padding' => 'px !important',
                    'button_size' => 'px !important',
                    'social_links_title_size' => 'px !important',
                    'social_links_top_padding' => 'px !important',
                    'social_links_bottom_padding' => 'px !important',
                    'social_links_left_padding' => 'px !important',
                    'social_links_right_padding' => 'px !important',
                    'subtitle_right_padding' => 'px !important',
                    'subtitle_left_padding' => 'px !important',
                    'subtitle_bottom_padding' => 'px !important',
                    'subtitle_top_padding' => 'px !important',
                    'container_border_width' => 'px !important',
                );
            }

            // Return single suffix
            if (isset($key)) {
                return isset(self::$css_suffixes[$key]) ? self::$css_suffixes[$key] : '';
            }
            // Return all suffixes for use in Javascript
            else {
                return self::$css_suffixes;
            }
        }

        /**
         * Reset to default values via Ajax request
         *
         * @access public
         * @return void
         */
        public function ajax_reset() {
            // Check request
            if (empty($_REQUEST['wp_customize']) || $_REQUEST['wp_customize'] !== 'on' || empty($_REQUEST['action']) || $_REQUEST['action'] !== 'rp_decorator_reset') {
                exit;
            }

            if( ! wp_verify_nonce((isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : ''), 'wt_rp_admin_nonce') ) {
                exit;
            }

            // Check if user is allowed to reset values
            if (!RP_Decorator::is_admin()) {
                exit;
            }
            $email_type = isset($_REQUEST['email_type']) && !empty($_REQUEST['email_type']) ? $_REQUEST['email_type'] : '';
            // Reset to default values
            RP_Decorator_Customizer::reset($email_type);

            exit;
        }
        
        /**
         * Get status of each template
         *
         * @access public
         * @return array
         */

        public function wt_decorator_button_text() {
            // Check request
            if (empty($_REQUEST['action']) || $_REQUEST['action'] !== 'rp_decorator_button_text') {
                exit;
            }

            if( ! wp_verify_nonce((isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : ''), 'wt_rp_admin_nonce') ) {
                exit;
            }
            // Check if user is allowed to reset values
            if (!RP_Decorator::is_admin()) {
                exit;
            }
            $wt_custom_data = (array) get_option('wt_decorator_custom_styles_in_draft', array());
            $wt_custom_data_scheduled = (array) get_option('wt_decorator_custom_styles_scheduled', array());
            $status = array();
            $status['drafted'] = array_keys($wt_custom_data);
            $status['scheduled'] = array_keys($wt_custom_data_scheduled);

            return wp_send_json_success($status);
        }
        
         /**
         * Checkbox action to set all template has same style
         *
         * @access public
         * @return void
         */

        public function wt_decorator_set_as_default() {
            // Check request
            if (empty($_REQUEST['action']) || $_REQUEST['action'] !== 'rp_decorator_set_as_default') {
                exit;
            }
            @delete_option('wt_decorator_old_custom_styles');

            if( ! wp_verify_nonce((isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : ''), 'wt_rp_admin_nonce') ) {
                exit;
            }

            // Check if user is allowed to reset values
            if (!RP_Decorator::is_admin()) {
                exit;
            }
            $wt_default_temp_arr = array();
            $wt_scheduled_data = array();
            $wt_custom_data_scheduled = (array) get_option('wt_decorator_custom_styles_scheduled', array());
            $wt_custom_data = (array) get_option('wt_decorator_custom_styles_in_draft', array());
            $status = array();
            $status['drafted'] = array_keys($wt_custom_data);
            $status['scheduled'] = array_keys($wt_custom_data_scheduled);
            if($wt_custom_data_scheduled){
                foreach ($wt_custom_data_scheduled as $c_key => $c_value) {
                    $wt_scheduled_data[$c_key] = $c_value['date_gmt'];
                }
            }
            $status['scheduled_data'] = $wt_scheduled_data;
            $wt_default_temp_arr['save_btn_text'] = isset($_POST['save_btn_text']) && !empty($_POST['save_btn_text']) ? $_POST['save_btn_text'] : 'Publish';
            $wt_default_temp_arr['email_type'] = isset($_POST['email_type']) && !empty($_POST['email_type']) ? $_POST['email_type'] : 'new_order';
            if (isset($_POST['template_default_value']) && $_POST['template_default_value'] == 'on') {
                update_option('wt_decorator_default_template_value', $wt_default_temp_arr);
            } else {
                update_option('wt_decorator_default_template_value', '');
            }

            return wp_send_json_success($status);
        }

        /**
         * Reset autosaved posts when template switched
         *
         * @access public
         * @return void
         */
        
        public function wt_decorator_delete_autosave_post() {
            // Check request
            if (empty($_REQUEST['action']) || $_REQUEST['action'] !== 'rp_decorator_delete_autosave_post') {
                exit;
            }

            if( ! wp_verify_nonce((isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : ''), 'wt_rp_admin_nonce') ) {
                exit;
            }

            // Check if user is allowed to reset values
            if (!RP_Decorator::is_admin()) {
                exit;
            }
            RP_Decorator_WC::remove_decorator_draft();
            $wt_custom_style = wc_clean($_REQUEST['current_email_type']);
            update_option('wt_decorator_last_selected_template', $wt_custom_style);
            // Get all stored values
            $stored_values = (array) get_option('wt_decorator_custom_styles', array());
            if (isset($stored_values[$wt_custom_style]) && !empty($stored_values[$wt_custom_style])) {
                $stored = $stored_values[$wt_custom_style];
            } 
            $drafted_values = get_option('wt_decorator_custom_styles_in_draft', array());
            $scheduled_values = get_option('wt_decorator_custom_styles_scheduled', array());
            if (isset($drafted_values[$wt_custom_style]) && !empty($drafted_values[$wt_custom_style])) {
                $stored = $drafted_values[$wt_custom_style];
            } elseif (isset($scheduled_values[$wt_custom_style]) && !empty($scheduled_values[$wt_custom_style]) ) {
                $stored = $scheduled_values[$wt_custom_style];
            }
            // Check if value exists in stored values array
            if (isset($stored['preview_order_id']) && !empty($stored)) {                
                $data =$stored['preview_order_id'];
            }else{
                $data = 'mockup';
            }

            return wp_send_json_success($data);
        }

        /**
         * Reset to default values
         *
         * @access private
         * @return void
         */
        public static function reset($email_type) {
            global $wpdb;
            $wt_custom_data_draft = (array) get_option('wt_decorator_custom_styles_in_draft', array());
            $wt_custom_data_scheduled = (array) get_option('wt_decorator_custom_styles_scheduled', array());
            $wt_stored = (array) get_option('wt_decorator_custom_styles', array());

            if (array_key_exists($email_type, $wt_stored)) {
                unset($wt_stored[$email_type]);
                update_option('wt_decorator_custom_styles', $wt_stored);
            } elseif (isset($wt_stored['']) && $email_type == 'new_order') {
                unset($wt_stored['']);
                update_option('wt_decorator_custom_styles', $wt_stored);
            }

            if (array_key_exists($email_type, $wt_custom_data_draft)) {
                unset($wt_custom_data_draft[$email_type]);
                update_option('wt_decorator_custom_styles_in_draft', $wt_custom_data_draft);
            } elseif (isset($wt_custom_data_draft['']) && $email_type == 'new_order') {
                unset($wt_custom_data_draft['']);
                update_option('wt_decorator_custom_styles_in_draft', $wt_custom_data_draft);
            }

            if (array_key_exists($email_type, $wt_custom_data_scheduled)) {
                unset($wt_custom_data_scheduled[$email_type]);
                update_option('wt_decorator_custom_styles_scheduled', $wt_custom_data_scheduled);
            } elseif (isset($wt_custom_data_scheduled['']) && $email_type == 'new_order') {
                unset($wt_custom_data_scheduled['']);
                update_option('wt_decorator_custom_styles_scheduled', $wt_custom_data_scheduled);
            }
            update_option('rp_decorator', array());
            $drafted_template = $wpdb->get_results('SELECT id,post_content FROM ' . $wpdb->prefix . "posts WHERE post_type = 'customize_changeset'", ARRAY_A);
            foreach ($drafted_template as $key => $drafted_template_data) {
                $template_post_id = $drafted_template_data['id'];
                $drafted_content = $drafted_template_data['post_content'];
                if (isset($drafted_content) && !empty($drafted_content) && strstr($drafted_content, 'rp_decorator')) {
                    wp_delete_post($template_post_id);
                }
            }

            $rest_arr = array('social_links_enable', 'wt_decorator_' . $email_type . '_image_link_btn_switch', 'footer_social_repeater', 'woocommerce_' . $email_type . '_settings', 'rp_decorator_' . $email_type . '_subtitle', 'rp_decorator_' . $email_type . '_btn_switch', 'rp_decorator_' . $email_type . '_body_full', 'rp_decorator_' . $email_type . '_body_partial', 'rp_decorator_' . $email_type . '_btn_switch',
                'rp_decorator_' . $email_type . '_body', 'rp_decorator_' . $email_type . '_body_failed','rp_decorator_' . $email_type . '_billing_address_subtitle','rp_decorator_' . $email_type . '_shipping_address_subtitle','social_links_icon_color');
            foreach ($rest_arr as $rest_key => $rest_value) {
                delete_option($rest_value);
            }
        }

        /**
         * Maybe add custom styles to default WooCommerce styles
         *
         * @access public
         * @param string $styles
         * @return string
         */
        public function maybe_add_custom_styles($styles) {
            // Check if custom styles need to be applied
            if (RP_Decorator_WC::overwrite_options()) {

                // Add custom styles
                $styles .= RP_Decorator_Customizer::get_styles_string();

                // Static styles
                $styles .= RP_Decorator_Customizer::get_static_styles();
            }
            // Otherwise apply some fixes for Customizer Preview
            else if (RP_Decorator::is_own_preview_request()) {
                $styles .= 'body { background-color: ' . get_option('woocommerce_email_background_color') . '; }';
                $styles .= RP_Decorator_Customizer::get_static_styles();
            }

            // Return styles
            return $styles;
        }

        public function maybe_add_custom_temp_style($emailheading, $email) {
            //self::$wt_template_object = isset($data) ? $data : '';
            self::$wt_template_type = isset($email->id) ? $email->id : 'new_order';
            return $emailheading;
        }

        public function wt_email_header_before($emailheading, $email) {

            self::$wt_template_object = $email;
            return $emailheading;
        }

        /**
         * Get static styles
         *
         * @access public
         * @return string
         */
        public static function get_static_styles() {
            return "
            #body_content_inner > table {
                border-collapse: collapse;
            }
            #body_content_inner > table.td > tbody {
                border-bottom-style: solid;
            }
        ";
        }

        /**
         * send test mail
         *
         * @access public
         * @return mail
         */
        
        public function wt_ajax_send_user_email() {
            // Check request
            if (empty($_REQUEST['wp_customize']) || $_REQUEST['wp_customize'] !== 'on' || empty($_REQUEST['action']) || $_REQUEST['action'] !== 'wt_send_test_email' || empty($_REQUEST['recipients'])) {
                exit;
            }

            if( ! wp_verify_nonce((isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : ''), 'wt_rp_admin_nonce') ) {
                exit;
            }

            // Check if user is allowed to reset values
            if (!RP_Decorator::is_admin()) {
                exit;
            }

            $recipients = wc_clean($_REQUEST['recipients']);
            $email_type = wc_clean($_REQUEST['email_type']);
            $preview_order_id = wc_clean($_REQUEST['preview_order_id']);
            update_option('wt_test_mail_recipients', $recipients);
            $recipients = explode(",", $recipients);
            foreach ($recipients as $key => $recipient) {
                $content = RP_Decorator_Preview::print_preview_email(true, trim($recipient), $email_type, $preview_order_id);
                echo $content;
            }
        }

         /**
         * send test mail
         *
         * @access public
         * @return mail
         */
        
        public function wt_apply_prebult_template() {
            // Check request
            if (empty($_REQUEST['wp_customize']) || $_REQUEST['wp_customize'] !== 'on' || empty($_REQUEST['action']) || $_REQUEST['action'] !== 'wt_apply_prebult_template' || empty($_REQUEST['template'])) {
                exit;
            }

            if( ! wp_verify_nonce((isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : ''), 'wt_rp_admin_nonce') ) {
                exit;
            }

            // Check if user is allowed to reset values
            if (!RP_Decorator::is_admin()) {
                exit;
            }
            $wt_decorator_custom_styles = get_option('wt_decorator_custom_styles');
            $old_data = array();
            $old_data['data']= $wt_decorator_custom_styles;
            $template = wc_clean($_REQUEST['template']);
            $wt_email_type = wc_clean($_REQUEST['email_type']);
            $old_data['prebuilt_template_preview'] = 'enable';
            if($template == 'light_blue'){

                    $social_links_enable = get_option('social_links_enable','disabled');
                    
                    $old_data['social_links_enable']= $social_links_enable !== 'disabled' ? $social_links_enable : 'normal';
                    if($social_links_enable !== 'disabled'){
                      update_option('social_links_enable', 'above');
                    }else{
                       add_option('social_links_enable', 'above'); 
                    }

                    $social_links_color = get_option('social_links_icon_color','disabled');
                    $old_data['social_links_icon_color']= $social_links_color !== 'disabled' ? $social_links_color : 'default';

                    if($social_links_color !== 'disabled'){
                      update_option('social_links_icon_color', 'gray');
                    }else{
                      add_option('social_links_icon_color', 'gray');
                    }


                    $social_links_data = get_option('footer_social_repeater');
                    $old_data['footer_social_repeater']= !empty($social_links_data) ? $social_links_data : '' ;

                    $footer_data = '[{"text":"undefined","link":"","text2":"undefined","choice":"fa-facebook","title":"","subtitle":"undefined","social_repeater":"undefined","id":"social-repeater-62663bef0b966","shortcode":"undefined"},{"text":"undefined","link":"","text2":"undefined","choice":"fa-instagram","title":"","subtitle":"undefined","social_repeater":"undefined","id":"customizer-repeater-62663c209ca8d","shortcode":"undefined"},{"text":"undefined","link":"","text2":"undefined","choice":"fa-twitter","title":"","subtitle":"undefined","social_repeater":"undefined","id":"customizer-repeater-62663c29a93a6","shortcode":"undefined"},{"text":"undefined","link":"","text2":"undefined","choice":"fa-linkedin","title":"","subtitle":"undefined","social_repeater":"undefined","id":"customizer-repeater-62663c33fbe94","shortcode":"undefined"}]';
                    if(!empty($social_links_data)){
                      update_option('footer_social_repeater', $footer_data);
                    }else{
                        add_option('footer_social_repeater', $footer_data);
                    }

                    update_option('footer_social_repeater', $footer_data);

                    $data = Array
                            (
                                'heading_font_family' => 'helvetica',
                                'background_color' => '#eeeeee',
                                'header_background_color' => '#00b9cd',
                                'email_background_color' => '#f0fdff',
                                'footer_background_color' => '#ffffff',
                                'header_image_maxwidth' => '600',
                                'header_image_maxheight' => '300',
                                'header_image_padding_top_bottom' => '29',
                                'items_table_background_color' => '#ffffff',
                                'items_table_padding' => '15',
                                'items_table_border_width' => '1',
                                'items_table_border_color' => '#dddddd',
                                'order_items_image' => 'show',
                                'order_items_image_size' => '50x50',
                                'preview_order_id' => 'mockup',
                                'h2_color'=>'#73aa1c',
                                'custom_css' => '#wt_order_items_table > div > table > tfoot > tr > th{
                                                              text-align: right !important;
                                                            }

                                                    #wt_order_items_table > div > table,
                                                    #wt_order_items_table > div > table  td,
                                                    #wt_order_items_table > div > table  th
                                                    {
                                                        border: none !important;
                                                    }
                                                    #wt_order_items_table > div > table > tbody > tr >td {
                                                             border-bottom: 1px solid #eeeeee !important;
                                                            }

                                                    #wt_order_items_table > div > table > tbody > tr:first-child td {
                                                        border-top: 1px solid #eee !important;
                                                    }

                                                    #wt_billing_address, #wt_shipping_address {
                                                    color: black !important;
                                                    margin-left: 10px !important;
                                                    }

                                                    .address{
                                                    border: none !important;}

                                                    #addresses >  tbody > tr:first-child td {
                                                        border-bottom: 1px solid #eee !important;
                                                    padding-bottom: 15px !important;
                                                    }
                                                    #template_header_image > p > img {
                                                      padding: 0px !important;
                                                    }

                                                    #wt_template_footers{
                                                    border-bottom-right-radius: 16px !important;
                                                        border-bottom-left-radius: 16px !important;
                                                    }',
                                'footer_content_text' => 'Copyright &#169; 2018 WooCommerce Emails, All rights reserved.',
                                'border_radius' => '0',
                                'shadow' => '0',
                            );
                            $data['header_image']   = RP_DECORATOR_PLUGIN_URL .'/'.'assets/images/Banner.png';
            
            }elseif ($template == 'white_theme') {
                            $social_links_enable = get_option('social_links_enable','disabled');
                    
                            $old_data['social_links_enable']= $social_links_enable !== 'disabled' ? $social_links_enable : 'normal';
                            if($social_links_enable !== 'disabled'){
                              update_option('social_links_enable', 'above');
                            }else{
                               add_option('social_links_enable', 'above'); 
                            }

                             $social_links_color = get_option('social_links_icon_color','disabled');
                            $old_data['social_links_icon_color']= $social_links_color !== 'disabled' ? $social_links_color : 'default';

                            if($social_links_color !== 'disabled'){
                              update_option('social_links_icon_color', 'gray');
                            }else{
                              add_option('social_links_icon_color', 'gray');
                            }


                            $social_links_data = get_option('footer_social_repeater');
                            $old_data['footer_social_repeater']= !empty($social_links_data) ? $social_links_data : '' ;

                            $footer_data = '[{"text":"undefined","link":"","text2":"undefined","choice":"fa-facebook","title":"","subtitle":"undefined","social_repeater":"undefined","id":"social-repeater-62663bef0b966","shortcode":"undefined"},{"text":"undefined","link":"","text2":"undefined","choice":"fa-instagram","title":"","subtitle":"undefined","social_repeater":"undefined","id":"customizer-repeater-62663c209ca8d","shortcode":"undefined"},{"text":"undefined","link":"","text2":"undefined","choice":"fa-twitter","title":"","subtitle":"undefined","social_repeater":"undefined","id":"customizer-repeater-62663c29a93a6","shortcode":"undefined"},{"text":"undefined","link":"","text2":"undefined","choice":"fa-linkedin","title":"","subtitle":"undefined","social_repeater":"undefined","id":"customizer-repeater-62663c33fbe94","shortcode":"undefined"}]';
                            if(!empty($social_links_data)){
                              update_option('footer_social_repeater', $footer_data);
                            }else{
                                add_option('footer_social_repeater', $footer_data);
                            }

                            update_option('footer_social_repeater', $footer_data);

                            $data =  Array
                                            (
                                                'heading_font_family' => 'helvetica',
                                                'header_image_background_color' => '#ffffff',
                                                'header_image_placement' => 'inside',
                                                'header_image_padding_top_bottom' => '35',
                                                'border_radius' => '16',
                                                'shadow' => '0',
                                                'header_background_color' => '#ffffff',
                                                'h1_color' => '#fc9612',
                                                'heading_color' => '#fc9612',
                                                'h2_color'=>'#fc9612',
                                                'custom_css' => '#template_header_image{
                                                                            border-bottom: 1px solid #fc9612 !important;
                                                                }

                                                                #wt_order_items_table > div > table, #wt_order_items_table > div > table  td, #wt_order_items_table > div > table  th{
                                                                            border: none !important;
                                                                }
                                                               #wt_order_items_table > div > table  td, #wt_order_items_table > div > table  th{
                                                                            border-top: 1px solid #eeeeee !important; 
                                                                }
                                                                .address{
                                                                            border: none !important;
                                                                }

                                                                #addresses >  tbody > tr:first-child td {
                                                                           border-bottom: 1px solid #eee !important;
                                                                           padding-bottom: 15px !important;
                                                                }
                                                                #wt_billing_address, #wt_shipping_address {
                                                                            color: black !important;
                                                                            margin-left: 10px !important;
                                                                }

                                                                #header_wrapper{
                                                                            padding-bottom: 0px !important;;
                                                                }
                                                                #body_content > table > tbody > tr:first-child td {
                                                                            padding-top: 30px;
                                                                }

                                                                #template_header_image{
                                                                            border-top-left-radius: 16px !important;
                                                                            border-top-right-radius: 16px !important;
                                                                }',

                                                'preview_order_id' => 'mockup',
                                                'footer_background_color' => '#f3f3f3',
                                                'background_color' => '#eeeeee',
                                                'footer_content_text' => 'Copyright &#169; 2018 WooCommerce Emails, All rights reserved.',
                                            );
                            
                             $data['header_image']   = RP_DECORATOR_PLUGIN_URL .'/'.'assets/images/webtoffee-logo_small.png';

            }elseif ($template == 'rose_theme') {
                            $social_links_enable = get_option('social_links_enable','disabled');
                    
                            $old_data['social_links_enable']= $social_links_enable !== 'disabled' ? $social_links_enable : 'normal';
                            if($social_links_enable !== 'disabled'){
                              update_option('social_links_enable', 'above');
                            }else{
                               add_option('social_links_enable', 'above'); 
                            }

                            $social_links_color = get_option('social_links_icon_color','disabled');
                            $old_data['social_links_icon_color']= $social_links_color !== 'disabled' ? $social_links_color : 'default';

                            if($social_links_color !== 'disabled'){
                              update_option('social_links_icon_color', 'gray');
                            }else{
                              add_option('social_links_icon_color', 'gray');
                            }


                            $social_links_data = get_option('footer_social_repeater');
                            $old_data['footer_social_repeater']= !empty($social_links_data) ? $social_links_data : '' ;

                            $footer_data = '[{"text":"undefined","link":"","text2":"undefined","choice":"fa-facebook","title":"","subtitle":"undefined","social_repeater":"undefined","id":"social-repeater-62663bef0b966","shortcode":"undefined"},{"text":"undefined","link":"","text2":"undefined","choice":"fa-instagram","title":"","subtitle":"undefined","social_repeater":"undefined","id":"customizer-repeater-62663c209ca8d","shortcode":"undefined"},{"text":"undefined","link":"","text2":"undefined","choice":"fa-twitter","title":"","subtitle":"undefined","social_repeater":"undefined","id":"customizer-repeater-62663c29a93a6","shortcode":"undefined"},{"text":"undefined","link":"","text2":"undefined","choice":"fa-linkedin","title":"","subtitle":"undefined","social_repeater":"undefined","id":"customizer-repeater-62663c33fbe94","shortcode":"undefined"}]';
                            if(!empty($social_links_data)){
                              update_option('footer_social_repeater', $footer_data);
                            }else{
                                add_option('footer_social_repeater', $footer_data);
                            }

                            update_option('footer_social_repeater', $footer_data);

                           $data =  Array
                                        (
                                            'heading_font_family' => 'helvetica',
                                            'background_color' => '#feebeb',
                                            'header_background_color' => '#ffa7a7',
                                            'header_image_padding_top_bottom' => '18',
                                            'h2_color'=>'#ffa7a7',
                                            'header_text_align' => 'center',
                                            'header_image_align' => 'center',
                                            'custom_css' => '#body_content_inner{
                                                                            text-align: center !important;
                                                            }
                                                            #wt_order_items_table > h2 > a , #wt_order_items_table > h2{
                                                                            text-align: center !important;
                                                            }
                                                            #wt_order_items_table > div > table, #wt_order_items_table > div > table  td, #wt_order_items_table > div > table  th{
                                                                            border: none !important;
                                                            }
                                                            #wt_order_items_table > div > table  td, #wt_order_items_table > div > table  th{
                                                                            border-left: 1px solid #eeeeee !important; 
                                                                             border-top: 1px solid #eeeeee !important; 
                                                            }
                                                            #wt_order_items_table > div > table > tfoot > tr:last-child td,#wt_order_items_table > div > table > tfoot > tr:last-child th {
                                                                           border-bottom: 1px solid #eeeeee !important; 
                                                            }

                                                            #wt_order_items_table > div > table  td:first-child, #wt_order_items_table > div > table  th:first-child{
                                                                            border-left: none !important;  
                                                            }

                                                            #wt_billing_address,.address,#wt_shipping_address{
                                                                            text-align: center !important;
                                                            }

                                                            .address{
                                                                        border: none !important;
                                                            }

                                                            #addresses >  tbody > tr:first-child td {
                                                                       border-bottom: 1px solid #eee !important;
                                                                       padding-bottom: 15px !important;
                                                            }
                                                            #wt_billing_address_wrap > .address{
                                                            border-right: 1px solid #eee !important;
                                                            }',
                                            'header_image_maxwidth' => '220',
                                            'preview_order_id' => 'mockup',
                                            'footer_background_color' => '#f3f3f3',
                                            'footer_content_text' => 'Copyright &#169; 2018 WooCommerce Emails, All rights reserved.',
                                        );
                            $data['header_image']   = RP_DECORATOR_PLUGIN_URL .'/'.'assets/images/webtoffee-logo_small.png';
                               
            }
           $template_type =  RP_Decorator_Preview::get_email_types();
           
           $new_value = get_option('wt_decorator_custom_styles',array());
           if(empty($new_value) && !is_array($new_value)){
              $new_value= array(); 
           }
           $new_value[$wt_email_type] = $data;

           $old_template_value = get_option('wt_decorator_old_custom_styles');
           if(empty($old_template_value)){
            add_option('wt_decorator_old_custom_styles', $old_data);
           }else{
               $old_template_value['prebuilt_template_preview_loop'] = 'enable';
               update_option('wt_decorator_old_custom_styles', $old_template_value);
           }
//            if(empty($wt_decorator_custom_styles)){
//              update_option('wt_decorator_custom_styles', $data);
//            }else{
//               add_option('wt_decorator_old_custom_styles', $old_data);
//            }
            update_option('wt_decorator_custom_styles', $new_value);
             $wt_customizer_url = add_query_arg(array(
                 'rp-decorator-customize' => '1',
                 'url' => urlencode(add_query_arg(array('rp-decorator-preview' => '1','pre_built_template'=>$template), site_url('/'))),
                 'return' => urlencode(RP_Decorator_WC::get_email_settings_page_url()),
                     ), admin_url('customize.php'));
            
            return wp_send_json_success($wt_customizer_url);

        }
        
           /**
         * wt_ajax_wt_send_reset_slider
         *
         * @access public
         * @return mail
         */
        
        public function wt_ajax_wt_send_reset_slider() {
            // Check request
            if (empty($_REQUEST['wp_customize']) || $_REQUEST['wp_customize'] !== 'on' || empty($_REQUEST['action']) || $_REQUEST['action'] !== 'wt_send_reset_slider' ) {
                exit;
            }

            if( ! wp_verify_nonce((isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : ''), 'wt_rp_admin_nonce') ) {
                exit;
            }

            // Check if user is allowed to reset values
            if (!RP_Decorator::is_admin()) {
                exit;
            }
            $selector = wc_clean($_REQUEST['selector']);
            $deafault =    RP_Decorator_Settings::get_default_value($selector);
            return wp_send_json_success($deafault);
        }
        
        /**
         * Replace shortcodes in subtitle
         *
         * @access public
         * @return object
         */
        public static function wt_subtitle_shortcode_replace($subtitle, $email) {
            // Check for placeholders.
            $subtitle = str_replace('{site_title}', get_bloginfo('name', 'display'), $subtitle);
            $subtitle = str_replace('{site_address}', wp_parse_url(home_url(), PHP_URL_HOST), $subtitle);
            $subtitle = str_replace('{site_url}', wp_parse_url(home_url(), PHP_URL_HOST), $subtitle);
            
            if(empty($email->object)) {
                return $subtitle;
            } 

            if (is_a($email->object, 'WP_User')) {
                $first_name = get_user_meta($email->object->ID, 'billing_first_name', true);
                if (empty($first_name)) {
                    // Fall back to user display name.
                    $first_name = $email->object->display_name;
                }

                $last_name = get_user_meta($email->object->ID, 'billing_last_name', true);
                if (empty($last_name)) {
                    // Fall back to user display name.
                    $last_name = $email->object->display_name;
                }

                $full_name = get_user_meta($email->object->ID, 'formatted_billing_full_name', true);
                if (empty($full_name)) {
                    // Fall back to user display name.
                    $full_name = $email->object->display_name;
                }
                $subtitle = str_replace('{customer_first_name}', $first_name, $subtitle);
                $subtitle = str_replace('{customer_last_name}', $last_name, $subtitle);
                $subtitle = str_replace('{customer_full_name}', $full_name, $subtitle);
                $subtitle = str_replace('{customer_username}', $email->user_login, $subtitle);
                $subtitle = str_replace('{customer_email}', $email->object->user_email, $subtitle);
            } elseif (is_a($email->object, 'WC_Order')) {
            
                if (0 === ( $user_id = (int) RP_Decorator_WC::get_order_meta($email->object->get_id(), '_customer_user') )) {
                    $user_id = 'guest';
                }
                $subtitle = str_replace('{order_date}', wc_format_datetime($email->object->get_date_created()), $subtitle);
                $subtitle = str_replace('{order_number}', $email->object->get_order_number(), $subtitle);
                $subtitle = str_replace('{customer_first_name}', $email->object->get_billing_first_name(), $subtitle);
                $subtitle = str_replace('{customer_last_name}', $email->object->get_billing_last_name(), $subtitle);
                $subtitle = str_replace('{customer_full_name}', $email->object->get_formatted_billing_full_name(), $subtitle);
                $subtitle = str_replace('{customer_company}', $email->object->get_billing_company(), $subtitle);
                $subtitle = str_replace('{customer_email}', $email->object->get_billing_email(), $subtitle);
                
                // Webtoffee Quote Plugin
                if(class_exists('Wt_Woo_Request_Quote') )
                {
                    switch($email->id)
                    {
                        case 'wtwraq_new_quote_request_email' : 
                        case 'wtwraq_quote_accepted_email' :    
                        case 'wtwraq_quote_declined_email' :
                        case 'wtwraq_quote_expired_email' :
                        case 'wtwraq_quote_reminder_email' : 
                        case 'wtwraq_quote_received_email' :
                        case 'wtwraq_quote_request_submitted_email' :                        
                        case 'wtwraq_quote_expiry_reminder_email' :

                            $subtitle = str_replace('{quote_id}', $email->object->get_order_number(), $subtitle);
                            $subtitle = str_replace('{quote_send_date}', esc_html( get_date_from_gmt( wtwraq_get_date( $email->object, '_wtwraq_quote_sent_date' ), get_option( 'date_format' ) ) ), $subtitle);
                            $subtitle = str_replace('{quote_requested_date}', esc_html( get_date_from_gmt( wtwraq_get_date( $email->object, '_wtwraq_quote_requested_date' ), get_option( 'date_format' ) ) ), $subtitle);
                            $subtitle = str_replace('{quote_expiry_date}', esc_html( get_date_from_gmt( wtwraq_get_date( $email->object, '_wtwraq_quote_expiry_date' ), get_option( 'date_format' ) ) ), $subtitle);
                            $subtitle = str_replace('{quote_accepted_date}', esc_html( get_date_from_gmt( wtwraq_get_date( $email->object, '_wtwraq_quote_accepted_date' ), get_option( 'date_format' ) ) ), $subtitle);
                            break;
                    }
                }

            } elseif (is_a($email->object, 'WC_Product')) {
                $subtitle = str_replace('{product_title}', $email->object->get_title(), $subtitle);
            }

            return $subtitle;
        }

    }

    RP_Decorator_Customizer::get_instance();
}
