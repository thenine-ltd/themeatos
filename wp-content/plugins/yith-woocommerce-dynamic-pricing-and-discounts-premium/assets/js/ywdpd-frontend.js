/*
 * @package YITH WooCommerce Dynamic Pricing and Discounts Premium
 * @since   1.1.7
 * @author  YITH
 */

jQuery(function ($) {
  "use strict";

  var default_price_html = $(ywdpd_qty_args.column_product_info_class).find(ywdpd_qty_args.product_price_classes).html(),
    select_default_qty = function () {

      if ('yes' == ywdpd_qty_args.is_default_qty_enabled) {
        var table = $(document).find('#ywdpd-table-discounts'),
          td = false;

        if (ywdpd_qty_args.show_minimum_price === 'yes') {

          td = table.find('td.qty-price-info').last();
        } else {
          td = table.find('td.qty-price-info').first();
        }

        td.click();
      }
    },
    update_price_html = function (price_html, sale_price, discount_price) {

      var prices = price_html.find('.woocommerce-Price-amount.amount'),
        sale_price = $(sale_price).html(),
        discount_price = $(discount_price).html();
      
      if (! $(ywdpd_qty_args.column_product_info_class).find('.product-type-variable').length) {
       
        if (prices.length) {
          prices.each(function () {
            if ($(this).parent('del').length) {
              $(this).html(sale_price);
            } else {

              $(this).html(discount_price);

            }
          });
        }
      } else {
        var variations_price =  $(ywdpd_qty_args.column_product_info_class).find('.woocommerce-variation-price');
        prices = variations_price.find('.woocommerce-Price-amount.amount');
        if (prices.length) {
          prices.each(function () {
            if ($(this).parent('del').length) {
              $(this).html(sale_price);
            } else {

              $(this).html(discount_price);

            }
          });
        }
      }
    },
    select_right_price_info = function ($t) {
      var span_price_html = $t.html(),
        qty = ($t.data('qtymax') != '*' && !ywdpd_qty_args.select_minimum_quantity) ? $t.data('qtymax') : $t.data('qtymin'),
        qty_field = $t.closest(ywdpd_qty_args.column_product_info_class).find(ywdpd_qty_args.product_qty_classes),
        price = $t.closest(ywdpd_qty_args.column_product_info_class).find(ywdpd_qty_args.product_price_classes),
        sale_price = price.find('del').length ? price.find('del').html() : '',
        index = $t.index(),
        td_price_info = false;

      $('td').removeClass('ywdpd_qty_active');
      if (ywdpd_qty_args.template === 'horizontal') {
        td_price_info = $(document).find('#ywdpd-table-discounts td.qty-info').get(index - 1);
        td_price_info = $(td_price_info);
      } else {

        td_price_info = $t.parent().find('td.qty-info');
      }

      $t.addClass('ywdpd_qty_active');

      if (td_price_info) {
        $(td_price_info).addClass('ywdpd_qty_active');
      }

      qty_field.val(qty);
      update_price_html(price, sale_price, span_price_html);
      //price.html('<del>' + sale_price + '</del> ' + span_price_html);
    },
    select_right_qty_info = function ($t) {
      var index = $t.index();
      $('td.qty-info').removeClass('ywdpd_qty_active');
      $t.addClass('ywdpd_qty_active');

      var td_price_info = false;

      if (ywdpd_qty_args.template === 'horizontal') {
        td_price_info = $(document).find('#ywdpd-table-discounts td.qty-price-info').get(index - 1);

        td_price_info = $(td_price_info);
      } else {
        td_price_info = $t.parent().find('td.qty-price-info')
      }
      if (td_price_info) {
        select_right_price_info(td_price_info);
      }

    };

  $(document).on('click', '#ywdpd-table-discounts td.qty-price-info', function (e) {
    var $t = $(this);
    select_right_price_info($t);
  });
  $(document).on('click', '#ywdpd-table-discounts td.qty-info', function (e) {

    var $t = $(this);
    select_right_qty_info($t);
  });
  $(document).on('change', 'form.cart .qty', function (e) {

    if ($(document).find('#ywdpd-table-discounts').length && 'yes' == ywdpd_qty_args.is_change_qty_enabled) {
      var qty = $(this).val(),
        td_qty_range_info = false,
        td_price_info = false;

      if (parseInt(qty) > 0) {

        $('#ywdpd-table-discounts td.qty-info').removeClass('ywdpd_qty_active');
        $('#ywdpd-table-discounts td.qty-price-info').removeClass('ywdpd_qty_active');

        if (ywdpd_qty_args.template === 'horizontal') {
          td_qty_range_info = $('#ywdpd-table-discounts').find('td.qty-info').filter(function () {
            var max = $(this).data('qtymax');
            if (max !== '*') {
              return $(this).data('qtymin') <= qty && $(this).data('qtymax') >= qty;
            } else {
              return $(this).data('qtymin') <= qty;
            }
          });
          if (td_qty_range_info.length) {
            var index = td_qty_range_info.index(),
              td_price_info = $('#ywdpd-table-discounts td.qty-price-info').get(index - 1);
            td_price_info = $(td_price_info);
          } else {
            td_qty_range_info = false;
          }
        } else {
          td_price_info = $('#ywdpd-table-discounts').find('td.qty-price-info').filter(function () {
            var max = $(this).data('qtymax');
            if (max !== '*') {
              return $(this).data('qtymin') <= qty && $(this).data('qtymax') >= qty;
            } else {
              return $(this).data('qtymin') <= qty;
            }
          });

          if (td_price_info.length) {
            td_qty_range_info = td_price_info.parent().find('td.qty-info');
          } else {
            td_qty_range_info = false;
          }
        }

        if (td_qty_range_info) {
          td_qty_range_info.addClass('ywdpd_qty_active');
          td_price_info.addClass('ywdpd_qty_active');

          var price = td_price_info.closest(ywdpd_qty_args.column_product_info_class).find(ywdpd_qty_args.product_price_classes),
            sale_price = price.find('del').length ? price.find('del').html() : '',
            span_price_html = td_price_info.html();

          update_price_html(price, sale_price, span_price_html);

        } else {
         
          $('ywdpd-table-discounts').find('tr,td').removeClass('ywdpd_qty_active');
          $(ywdpd_qty_args.column_product_info_class).find(ywdpd_qty_args.product_price_classes).html(default_price_html);
          
          var variation_price =  $('.ywdpd-table-discounts-wrapper').data('default_variation_price_html');

          if( ( '' !== variation_price && typeof variation_price !== 'undefined' ) &&  $(ywdpd_qty_args.column_product_info_class).find('.woocommerce-variation-price').length ) {
            $(ywdpd_qty_args.column_product_info_class).find('.woocommerce-variation-price').html( variation_price );
          }
        }
      }
    }
  });


  var $product_id = $('[name|="product_id"]'),
    product_id = $product_id.val(),
    $variation_id = $('[name|="variation_id"]'),
    form = $product_id.closest('form'),
    $table = false;

    if( $('.ywdpd-table-discounts-wrapper-sh').length ){
      $table = $('.ywdpd-table-discounts-wrapper-sh');

    } else {
      $table = $('.ywdpd-table-discounts-wrapper');

    }
    
  $(ywdpd_qty_args.variation_form_class).on('found_variation', form, function (event, variation) {
   
    if( $('.ywdpd-table-discounts-wrapper-sh').length ){
      $('.ywdpd-table-discounts-wrapper-sh').replaceWith(variation.table_price);
    }else{
    $('.ywdpd-table-discounts-wrapper').replaceWith(variation.table_price);
    }
    $('.ywdpd-table-discounts-wrapper').data('default_variation_price_html', variation.price_html );
   
    select_default_qty();
  });

  if (!$variation_id.length) {
    select_default_qty();
    return false;
  }

  $variation_id.on('change', function () {
    if ($(this).val() == '') {
      if( $('.ywdpd-table-discounts-wrapper-sh').length ){
        $('.ywdpd-table-discounts-wrapper-sh').replaceWith($table);
      }else{
      $('.ywdpd-table-discounts-wrapper').replaceWith($table);
      }

      $('.ywdpd-table-discounts-wrapper').data('default_variation_price_html', '' );
    }
  });


});
