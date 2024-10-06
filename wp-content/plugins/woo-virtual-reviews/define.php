<?php
/**
 * Created by PhpStorm.
 * User: toido
 * Date: 11/1/2018
 * Time: 11:09 AM
 */

namespace WooVR;

defined( 'ABSPATH' ) || exit();

$plugin_url = plugins_url( '', __FILE__ );

define( 'WVR_PLUGIN_DIR_PATH', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . "woo-virtual-reviews" . DIRECTORY_SEPARATOR );
define( 'WVR_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'WVR_PLUGIN_URL', plugins_url() . "/woo-virtual-reviews" );

define( 'WVR_OPTION', "wvr_data" );

define( 'WVR_INCLUDES', WVR_PLUGIN_DIR_PATH . "includes" . DIRECTORY_SEPARATOR );

define( 'WVR_CSS_URL', $plugin_url . "/assets/css/" );
define( 'WVR_CSS_DIR', WVR_PLUGIN_DIR_PATH . "assets" . DIRECTORY_SEPARATOR . "css" . DIRECTORY_SEPARATOR );

define( 'WVR_JS_URL', $plugin_url . "/assets/js/" );
define( 'WVR_JS_DIR', WVR_PLUGIN_DIR_PATH . "assets" . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR );

define( 'WVR_IMAGES_URL', $plugin_url . "/assets/img/" );


if ( ! function_exists( 'WooVR\get_pro_button' ) ) {
	function get_pro_button() {
		?>
        <a href="https://1.envato.market/jW36P0" class="button vi-ui yellow small" target="_blank">
            Upgrade to open this feature
        </a>
		<?php
	}
}

class Vi_Auto_Load_Class {

	function __construct() {
		spl_autoload_register( array( $this, "auto_load_classes" ) );
	}


	public function auto_load_classes( $class ) {
		$this->auto_load_class_folder( $class, 'includes' );
	}

	public function auto_load_class_folder( $class, $folder ) {
		$prefix = __NAMESPACE__;
		// base directory for the namespace prefix

		// does the class use the namespace prefix?
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			// no, move to the next registered autoloader
			return;
		}

		// get the relative class name
		$relative_class = strtolower( substr( $class, $len ) );

		$relative_class = str_replace( '_', '-', $relative_class );

		$file = __DIR__ . '/' . $folder . str_replace( '\\', '/', $relative_class ) . '.php';
//		check($file);die;

		// if the file exists, require it
		if ( file_exists( $file ) ) {
			require $file;
		} else {
			return;
		}
	}

}

new Vi_Auto_Load_Class();

Admin_Settings::instance();
Add_Multi_Reviews::instance();

if ( ! is_admin() ) {
	Display_Comment::instance();
} else {

	if ( is_file( WVR_INCLUDES . 'support.php' ) ) {
		include_once WVR_INCLUDES . 'support.php';
	}

	if ( class_exists( '\VillaTheme_Support' ) ) {
		new \VillaTheme_Support(
			array(
				'support'   => 'https://wordpress.org/support/plugin/woo-virtual-reviews/',
				'docs'      => 'http://docs.villatheme.com/woocommerce-virtual-reviews/',
				'review'    => 'https://wordpress.org/support/plugin/woo-virtual-reviews/reviews/?rate=5#rate-response',
				'pro_url'   => 'https://1.envato.market/jW36P0',
				'css'       => WVR_PLUGIN_URL . "/assets/css/",
				'image'     => WVR_PLUGIN_URL . "/assets/img/",
				'slug'      => 'woo-virtual-reviews',
				'menu_slug' => 'virtual-reviews',
				'version'   => VI_WOO_VIRTUAL_REVIEWS_VERSION,
				'survey_url' => 'https://script.google.com/macros/s/AKfycbyT-3Cv6myUnrWn8lxVqsdNr0__BVP01RctNZkrnLZqf1hAs52nODz7EUlolPM39266QA/exec'
			)
		);
	}
}



