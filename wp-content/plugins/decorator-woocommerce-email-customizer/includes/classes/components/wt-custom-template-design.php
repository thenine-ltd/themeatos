<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) {
	exit;
}

if ( class_exists( 'WP_Customize_Control' ) && ! class_exists( 'WP_Customize_Wtloadtemplate_Control' ) ) {
	class WP_Customize_Wtloadtemplate_Control extends WP_Customize_Control {
		public $type = 'wtloadtemplate';
                public function enqueue() {
                    wp_enqueue_style( 'customizer-pre-built-template-css', RP_DECORATOR_PLUGIN_URL . '/assets/css/customizer-pre-built-template-css.css', array(), RP_DECORATOR_VERSION );

		}

		public function render_content() {

			$name = 'kt-woomail-prebuilt-template';
			?>

			<div style="padding-bottom: 20px;">
				<span style="color:#0e9cd1"><strong>NEW!</strong></span>
                        </div>
			<span class="customize-control-title">
				<?php _e( 'Load Template', 'decorator-woocommerce-email-customizer' ); ?>
			</span>
			<div>
				<?php foreach ( $this->choices as $value => $label ) : ?>                                 
                                     <label class="img-btn wt_template_container">
                                        <img src="<?php echo esc_url( RP_DECORATOR_PLUGIN_URL .'/'.  $label ); ?>" alt="<?php echo esc_attr( $value ); ?>" title="<?php echo esc_attr( $value ); ?>">
                                        <div class="wt_template_button wt-prebult-template-button" ><a href="#"> <?php esc_attr_e( 'Load Template', 'decorator-woocommerce-email-customizer' ); ?> </a></div>
                                        <input type="hidden" class="pre-built-template" name="pre-built-template" value="<?php echo esc_attr( $value ); ?>">
                                     </label>
				<?php endforeach; ?>
			</div>
			<?php
		}
	}
}