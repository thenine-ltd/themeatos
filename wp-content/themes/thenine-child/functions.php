<?php
// Do NOT include the opening php tag.
// Place in your theme's functions.php file
	
add_filter( 'cfw_get_billing_checkout_fields', 'remove_checkout_fields', 100 );

function remove_checkout_fields( $fields ) {
	unset( $fields['billing_company'] );
	unset( $fields['billing']['billing_city'] );
	unset( $fields['billing_postcode'] );
	unset( $fields['billing_country'] );
	unset( $fields['billing_state'] );
	unset( $fields['billing_address_1'] );
	unset( $fields['billing_address_2'] );
	return $fields;
}

// Set billing address fields to not required
add_filter( 'woocommerce_checkout_fields', 'unrequire_checkout_fields' );

function unrequire_checkout_fields( $fields ) {
	$fields['billing']['billing_company']['required']   = false;
	$fields['billing']['billing_city']['required']      = false;
	$fields['billing']['billing_postcode']['required']  = false;
	$fields['billing']['billing_country']['required']   = false;
	$fields['billing']['billing_state']['required']     = false;
	$fields['billing']['billing_address_1']['required'] = false;
	$fields['billing']['billing_address_2']['required'] = false;
	return $fields;
}


/**
 * Attribute shortcode callback.
 */
function so_39394127_singular_attribute_shortcode( $atts ) {

    global $product;

    if( ! is_object( $product ) || ! $product->has_attributes() ){
        return;
    }

    // parse the shortcode attributes
    $args = shortcode_atts( array(
        'attribute' => ''
    ), $atts );

    // start with a null string because shortcodes need to return not echo a value
    $html = '';

    if( $args['attribute'] ){

        // get the WC-standard attribute taxonomy name
        $taxonomy = strpos( $args['attribute'], 'pa_' ) === false ? wc_attribute_taxonomy_name( $args['attribute'] ) : $args['attribute'];

        if( taxonomy_is_product_attribute( $taxonomy ) ){

            // Get the attribute label.
            $attribute_label = wc_attribute_label( $taxonomy );

            // Build the html string with the label followed by a clickable list of terms.
            // Updated for WC3.0 to use getters instead of directly accessing property.
            $html .= get_the_term_list( $product->get_id(), $taxonomy, $attribute_label . ': ' , ', ', '' );   
        }

    }

    return $html;
}
add_shortcode( 'display_attribute', 'so_39394127_singular_attribute_shortcode' );

function custom_product_description($atts){
    global $product;

    try {
        if( is_a($product, 'WC_Product') ) {
            return wc_format_content( $product->get_description("shortcode") );
        }

        return "Product description shortcode run outside of product context";
    } catch (Exception $e) {
        return "Product description shortcode encountered an exception";
    }
}
add_shortcode( 'custom_product_description', 'custom_product_description' );

function custom_remove_all_quantity_fields( $return, $product ) {return true;}
add_filter( 'woocommerce_is_sold_individually','custom_remove_all_quantity_fields', 10, 2 );

add_filter( 'woocommerce_product_add_to_cart_text', 'custom_add_to_cart_price', 20, 2 ); // Shop and other archives pages
add_filter( 'woocommerce_product_single_add_to_cart_text', 'custom_add_to_cart_price', 20, 2 ); // Single product pages
function custom_add_to_cart_price( $button_text, $product ) {
    // Variable products
    if( $product->is_type('variable') ) {
        // shop and archives
        if( ! is_product() ){
            $product_price = wc_price( wc_get_price_to_display( $product, array( 'price' => $product->get_variation_price() ) ) );
            return $button_text . '' . strip_tags( $product_price );
        } 
        // Single product pages
        else {
            return $button_text;
        }
    } 
    // All other product types
    else {
        $product_price = wc_price( wc_get_price_to_display( $product ) );
        return $button_text . ' - Just ' . strip_tags( $product_price );
    }
}

// Apply Sort By Last Modified
add_filter( 'woocommerce_get_catalog_ordering_args', 'woo_add_postmeta_ordering_args' );
function woo_add_postmeta_ordering_args( $args_sort ) {

  $orderby_value = isset( $_GET['orderby'] ) ? wc_clean( $_GET['orderby'] ) : '';

  switch( $orderby_value ) {
     case 'last_modified':
        $args_sort['orderby']  = 'modified';
        $args_sort['order']    = 'DESC';
     break;
  }
  return $args_sort;
}

// Add "Sort By Last Modified" option in dropdown
add_filter( 'woocommerce_default_catalog_orderby_options', 'woo_add_new_postmeta_orderby' );
add_filter( 'woocommerce_catalog_orderby', 'woo_add_new_postmeta_orderby' );

function woo_add_new_postmeta_orderby( $sortby ) {
    $sortby['last_modified'] = __( 'Sort By Last Modified', 'woocommerce' );
    return $sortby;
}