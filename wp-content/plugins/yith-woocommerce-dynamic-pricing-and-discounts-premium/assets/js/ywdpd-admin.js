/*
 * @package YITH WooCommerce Dynamic Pricing and Discounts Premium
 * @since   1.0.0
 * @author  YITH
 */

jQuery(function ($) {
  "use strict";
  var wrapper = $(document).find('.ywdpd-sections-group'),
    container = wrapper.find('.ywdpd-section'),
    eventType = container.find('.yith-ywdpd-eventype-select'),
    del_msg = (typeof yith_ywdpd_admin !== 'undefined') ? yith_ywdpd_admin.del_msg : false,
    ajax_url = yith_ywdpd_admin.ajaxurl + '?action=ywdpd_admin_action';
  /****
   * Add a row pricing rules
   ****/
  $(document).on('click', '.ywdpd_new_rule', function (e) {
    e.preventDefault();

    var $t = $(this),
      table = $t.parent().parent().find('.discount-rules'),
      rows = table.find('.discount-table-row'),
      max_index = 1;
    rows.each(function () {
      var index = $(this).data('index');
      if (index > max_index) {
        max_index = index;
      }
    });

    var new_index = max_index + 1,
      template = '';

    if ('rules-container' === table.parent().parent().parent().attr('id')) {
      template = wp.template('ywdpd-quantity-discount-row');
    } else {
      template = wp.template('ywdpd-quantity-category-discount-row');
    }

    var new_row = $(template({index: new_index}));


    new_row.appendTo(table);

    $(document.body).trigger('wc-enhanced-select-init');
    $(document.body).trigger('yith-framework-enhanced-select-init');


  });
  $(document).on('click', '#ywdpd_new_cart_rule', function (e) {
    e.preventDefault();
    var $t = $(this),
      table = $t.parent().parent().find('#cart-rules'),
      rows = table.find('.cart-rule-row'),
      max_index = 1;
    rows.each(function () {
      var index = $(this).data('index');
      if (index > max_index) {
        max_index = index;
      }
    });

    var new_index = max_index + 1,
      template = wp.template('ywdpd-cart-discount-row');


    var new_row = $(template({index: new_index}));

    show_item(new_row.find('.ywdpd_rule_type'));
    new_row.appendTo(table);

    $(document.body).trigger('wc-enhanced-select-init');
    $(document.body).trigger('yith-framework-enhanced-select-init');


  });
  $(document).on('change', '.discount-table-row .ywdpd_qty_discount', function (e) {

    var t = $(this),
      row = t.closest('.discount-table-row'),
      symbol = row.find('span.ywdpd_symbol'),
      value = t.val();


    if ('percentage' === value) {
      symbol.html(yith_ywdpd_admin.percent_symbol);
    } else {
      symbol.html(yith_ywdpd_admin.currency_symbol);

    }
  });
  $(document).on('change', '#_discount_rule-container select', function (e) {

    var t = $(this),
      row = t.parent().parent().parent(),
      symbol = row.find('span.ywdpd_symbol'),
      value = t.val();

    if ('percentage' === value) {
      symbol.html(yith_ywdpd_admin.percent_symbol);
    } else {
      symbol.html(yith_ywdpd_admin.currency_symbol);

    }
  });
  $(document).on('change', '#_simple_whole_discount_discount_mode', function (e) {
    var value = $(this).val(),
      symbol = $(this).parent().parent().parent().find('.ywdpd_symbol');

    if ('percentage' === value) {
      symbol.html(yith_ywdpd_admin.percent_symbol);
    } else {
      symbol.html(yith_ywdpd_admin.currency_symbol);

    }
  });

  var whole_discount_mode = $('#_simple_whole_discount_discount_mode'),
    whole_discount_mode_val = whole_discount_mode.val(),
    symbol = whole_discount_mode.parent().parent().parent().find('.ywdpd_symbol');

  if ('percentage' === whole_discount_mode_val) {
    symbol.html(yith_ywdpd_admin.percent_symbol);
  } else {
    symbol.html(yith_ywdpd_admin.currency_symbol);
  }

  $(document).on('change', '#_rule_for input[type="radio"], #_rule_apply_adjustment_discount_for input[type="radio"]', function (e) {
    disable_unnecessary_option($(this));
  });

  $(document).on('change', '#_user_rules input[type="radio"]', function (e) {
    disable_unnecessary_user_option($(this));
  });

  var disable_unnecessary_option = function (radio_field) {
      var t = radio_field,
        id = t.parent().parent().attr('id'),
        value = t.val();


      if ('_rule_for' === id) {
        var enable_exclude_field = $('#_active_exclude').parents('.the-metabox');

        if ('specific_products' === value) {
          if (enable_exclude_field.find('input[type="checkbox"]').is(':checked')) {
            enable_exclude_field.find('input[type="checkbox"]').val('no').click();
          }
          show_or_hide_field(enable_exclude_field, false);
        } else if ('specific_categories' === value) {

          show_or_hide_field(enable_exclude_field, true);
          show_or_hide_field($('#_exclude_rule_for-specific_products').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-specific_tag').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-specific_categories').parent(), false);
          show_or_hide_field($('#_exclude_rule_for-vendor_list_excluded').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-brand_list_excluded').parent(), true);
        } else if ('specific_tag' === value) {
          show_or_hide_field(enable_exclude_field, true);
          show_or_hide_field($('#_exclude_rule_for-specific_products').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-specific_tag').parent(), false);
          show_or_hide_field($('#_exclude_rule_for-specific_categories').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-vendor_list_excluded').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-brand_list_excluded').parent(), true);
        } else if ('vendor_list' === value) {
          show_or_hide_field(enable_exclude_field, true);
          show_or_hide_field($('#_exclude_rule_for-specific_products').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-specific_tag').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-specific_categories').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-vendor_list_excluded').parent(), false);
          show_or_hide_field($('#_exclude_rule_for-brand_list_excluded').parent(), true);
        } else if ('specific_brands' === value) {
          show_or_hide_field(enable_exclude_field, true);
          show_or_hide_field($('#_exclude_rule_for-specific_products').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-specific_tag').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-specific_categories').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-vendor_list_excluded').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-brand_list_excluded').parent(), false);
        } else {
          show_or_hide_field(enable_exclude_field, true);
          show_or_hide_field($('#_exclude_rule_for-specific_products').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-specific_tag').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-specific_categories').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-vendor_list_excluded').parent(), true);
          show_or_hide_field($('#_exclude_rule_for-brand_list_excluded').parent(), true);
        }
      } else if ('_rule_apply_adjustment_discount_for' === id) {
        var enable_apply_adjust_exclude = $('#_active_apply_adjustment_to_exclude').parents('.the-metabox');

        if ('specific_products' === value) {

          if (enable_apply_adjust_exclude.find('input[type="checkbox"]').is(':checked')) {
            enable_apply_adjust_exclude.find('input[type="checkbox"]').val('no').click();
          }
          show_or_hide_field(enable_apply_adjust_exclude, false);
        } else if ('specific_categories' === value) {
          show_or_hide_field(enable_apply_adjust_exclude, true);
          show_or_hide_field($('#_exclude_apply_adjustment_rule_for-specific_products').parent(), true);
          show_or_hide_field($('#_exclude_apply_adjustment_rule_for-specific_tag').parent(), true);
          show_or_hide_field($('#_exclude_apply_adjustment_rule_for-specific_categories').parent(), false);

        } else if ('specific_tag' === value) {
          show_or_hide_field(enable_apply_adjust_exclude, true);
          show_or_hide_field($('#_exclude_apply_adjustment_rule_for-specific_products').parent(), true);
          show_or_hide_field($('#_exclude_apply_adjustment_rule_for-specific_tag').parent(), false);
          show_or_hide_field($('#_exclude_apply_adjustment_rule_for-specific_categories').parent(), true);
        } else {
          show_or_hide_field(enable_apply_adjust_exclude, true);
          show_or_hide_field($('#_exclude_apply_adjustment_rule_for-specific_products').parent(), true);
          show_or_hide_field($('#_exclude_apply_adjustment_rule_for-specific_tag').parent(), true);
          show_or_hide_field($('#_exclude_apply_adjustment_rule_for-specific_categories').parent(), true);
        }
      }
    },
    disable_unnecessary_user_option = function (radio_field) {
      var t = radio_field,
        id = t.parent().parent().attr('id'),
        value = t.val();


      if ('_user_rules' === id) {
        var enable_exclude_field = $('#_enable_user_rule_exclude').parents('.the-metabox'),
          radio_role_field = $('#_user_rule_exclude-specific_roles'),
          radio_role_container = radio_role_field.parent(),
          radio_user_field = $('#_user_rule_exclude-specific_customers'),
          radio_user_container = radio_user_field.parent(),
          radio_membership_field = $('#_user_rule_exclude-specific_membership'),
          radio_membership_container = radio_membership_field.parent();


        if ('customers_list' === value) {
          if (enable_exclude_field.find('input[type="checkbox"]').is(':checked')) {
            enable_exclude_field.find('input[type="checkbox"]').val('no').click();
          }
          show_or_hide_field(enable_exclude_field, false);
        } else if ('role_list' === value) {

          show_or_hide_field(enable_exclude_field, true);
          show_or_hide_field(radio_role_container, false);
          show_or_hide_field(radio_user_container, true);
          show_or_hide_field(radio_membership_container, true);
          radio_user_field.click();

        } else if ('specific_membership' === value) {
          show_or_hide_field(enable_exclude_field, true);
          show_or_hide_field(radio_role_container, true);
          show_or_hide_field(radio_user_container, true);
          show_or_hide_field(radio_membership_container, false);
          radio_user_field.click();
        } else {
          show_or_hide_field(enable_exclude_field, true);
          show_or_hide_field(radio_role_container, true);
          show_or_hide_field(radio_user_container, true);
          show_or_hide_field(radio_membership_container, true);

        }
      }
    },
    show_or_hide_field = function (field, show) {

      if (field.length) {
        if (show) {
          field.show();
          field.fadeTo("slow", 1).addClass('fade-in');
        } else {
          if (!field.hasClass('fade-in')) {
            field.hide();
            field.css({'opacity': '0'});
          } else {
            field.fadeTo("slow", 0, function () {
              $(this).hide().removeClass('fade-in');
            });
          }
        }
      }
    }
  $(document).on('change', '#special_offer_purchase_discount', function (e) {
    var t = $(this),
      row = t.parent().parent().parent(),
      symbol = row.find('span.ywdpd_symbol'),
      value = t.val();

    if ('percentage' === value) {
      symbol.html(yith_ywdpd_admin.percent_symbol);
    } else {
      symbol.html(yith_ywdpd_admin.currency_symbol);
    }
  });

  /****
   * remove a row pricing rules
   ****/
  $(document).on('click', '.yith-icon.yith-icon-trash', function () {
    var $t = $(this),
      current_row = $t.closest('div.discount-table-row');

    if (!current_row.length) {
      current_row = $t.closest('div.cart-rule-row');
    }
    current_row.remove();
  });

  // init

  $('.schedule_rules').find('.datepicker').each(function () {
    $(this).prop('placeholder', 'YYYY-MM-DD HH:mm')
  });

  $('#_schedule_from').datetimepicker({
    timeFormat: 'HH:mm',
    minDate: new Date(),
    dateFormat: 'yy-mm-dd',
    onSelect:function(selectedDateTime){
    $('#_schedule_to').datetimepicker('option', 'minDate', $(this).datetimepicker('getDate') );
  }

});
$('#_schedule_to').datetimepicker({
  timeFormat: 'HH:mm',
  minDate: new Date(),
  dateFormat: 'yy-mm-dd',
  onSelect:function(selectedDateTime){
  $('#_schedule_from').datetimepicker('option', 'maxDate', $(this).datetimepicker('getDate') );
}

});
  if ($('#_discount_type').length) {
    var std = $('#_discount_type').data('std'),
      href = $('.page-title-action').attr('href');
    $('#_discount_type').attr('value', std);
    $('.page-title-action').attr('href', href + '&ywdpd_discount_type=' + std);
  }

  $('#_schedule_from, #_schedule_to').each(function () {
    $(this).prop('placeholder', 'YYYY-MM-DD HH:mm')
  }).datetimepicker({
    timeFormat: 'HH:mm',
    defaultDate: '',
    dateFormat: 'yy-mm-dd',
    numberOfMonths: 1,
  });

  $('.post-type-ywdpd_discount table.wp-list-table').sortable({
    items: 'tbody tr:not(.inline-edit-row)',
    cursor: 'move',
    handle: '.priority.column-priority',
    axis: 'y',
    forcePlaceholderSize: true,
    helper: 'clone',
    opacity: 0.65,
    start: function (event, ui) {
      ui.item.css('background-color', '#f6f6f6');
    },
    stop: function (event, ui) {
      ui.item.removeAttr('style');
      var roleid = ui.item.find('.check-column input').val(); // this post id
      var previd = ui.item.prev().find('.check-column input').val();
      var nextid = ui.item.next().find('.check-column input').val();

      $.post(ajax_url, {
        ywdpd_action: 'table_order_section',
        type: $(document).find('body').hasClass('ywdpd-discount-type-cart') ? 'cart' : 'pricing',
        roleid: roleid,
        previd: previd,
        nextid: nextid
      }, function (resp) {
        console.log(resp);
      });
    }
  });

  $('#ywdpd-discount-list-table').on('submit', function () {
    var $t = $(this),

      bulk = $t.find('#bulk-action-selector-top').val();

    if (bulk == 'delete') {
      var confirm = window.confirm(del_msg);
      if (confirm == true) {
        return true;
      } else {
        return false;
      }
    }

  });

  /**
   * Register toggle enabled
   */
  $(document).on('change', '.ywdpd-toggle-enabled input', function () {
    var enabled = $(this).val() === 'yes' ? 'yes' : 'no',
      container = $(this).closest('.ywdpd-toggle-enabled'),
      discountID = container.data('discount-id'),
      security = container.data('security');

    $.ajax({
      type: 'POST',
      data: {
        ywdpd_action: 'discount_toggle_enabled',
        id: discountID,
        enabled: enabled,
        security: security
      },
      url: ajax_url,
      success: function (response) {
        if (typeof response.error !== 'undefined') {
          alert(response.error);
        }
      }

    });
  });


  /**
   * Added discount type to links
   */
  if ($(document).find('body').hasClass('post-type-ywdpd_discount')) {
    var linkList = $('.subsubsub').find('li a'),
      rowAction = $('.row-actions'),
      type = '';

    if ($(document).find('body').hasClass('ywdpd-discount-type-cart')) {
      type = 'cart';
    }

    if ($(document).find('body').hasClass('ywdpd-discount-type-pricing')) {
      type = 'pricing';
    }

    if ($(document).find('#ywdpd_discount_type').length > 0) {
      type = $(document).find('#ywdpd_discount_type').val();
    }

    $.each(linkList, function () {
      var $t = $(this),
        link = $t.attr('href');

      link += '&ywdpd_discount_type=' + type;

      $t.attr('href', link);
    });

    $.each(rowAction, function () {
      var $t = $(this),
        postLinks = $t.find('a');

      $.each(postLinks, function () {
        var $tt = $(this),
          link = $tt.attr('href');

        link += '&ywdpd_discount_type=' + type;

        $tt.attr('href', link);
      });

    });

    var $pageAction = $(document).find('a.page-title-action');

    if ($pageAction.length > 0) {
      $.each($pageAction, function () {
        var $t = $(this),
          link = $t.attr('href');

        link += '&ywdpd_discount_type=' + type;

        $t.attr('href', link);
      });
    }

    var filter_form = $(document).find('#posts-filter');
    $('<input>').attr({
      type: 'hidden',
      value: type,
      name: 'ywdpd_discount_type'
    }).appendTo(filter_form);
  }


  $(document).find('.ui-datepicker').addClass('yith-plugin-fw-datepicker-div');


  //Handle multi-dependencies
  function multi_dependencies_handler(id, deps, values, first) {
    var result = true;

    for (var i = 0; i < deps.length; i++) {

      if (deps[i].substr(0, 6) == ':radio') {
        deps[i] = deps[i] + ':checked';
      }

      var val = $(deps[i]).val();

      if ($(deps[i]).attr('type') == 'checkbox') {
        var thisCheck = $(deps[i]);
        if (thisCheck.is(':checked')) {
          val = 'yes';
        } else {
          val = 'no';
        }
      }
      if (result && (val == values[i])) {
        result = true;
      } else {
        result = false;
        break;
      }
    }

    if (!result) {
      $(id + '-container').parent().hide();
    } else {
      $(id + '-container').parent().show();
    }
  }

  function isArray(myArray) {
    return myArray.constructor.toString().indexOf('Array') > -1;
  }


  //metaboxes
  $('.metaboxes-tab [data-dep-target]').each(function () {
    var t = $(this);

    var deps = t.data('dep-id').split(',');

    if (isArray(deps) && deps.length > 1) {
      var field = '#' + t.data('dep-target');
      var values = t.data('dep-value').split(',');
      multi_dependencies_handler(field, deps, values, true);
      for (var i = 0; i < deps.length; i++) {
        deps[i] = '#' + deps[i];
      }

      for (var i = 0; i < deps.length; i++)
        $(deps[i]).on('change', function () {
          multi_dependencies_handler(field, deps, values, false);
        }).change();
    }
  });

  $(document).on('change', '#schedule_mode input[type="radio"]', function (e) {

    var t = $(this);

    if ('no_schedule' === t.val()) {
      $(document).find('#_schedule_discount_mode-container .yith-plugin-fw-field-schedule').hide();
    } else {
      $(document).find('#_schedule_discount_mode-container .yith-plugin-fw-field-schedule').show();
    }
  });

  var change_label = function (value) {
    var $rule_for = $(document).find('#_rule_for-container label:first-child'),
      $quantity_based = $(document).find('#_quantity_based-container label:first-child'),
      $user_rules = $(document).find('#_user_rules-container label:first-child'),
      $schedule_discount_mode = $(document).find('#_schedule_discount_mode-container').closest('label'),
      $table_note_apply_to = $(document).find('#_table_note_apply_to-container label:first-child'),
      $table_note_apply_to_desc = $(document).find('#_table_note_apply_to-container span.description'),
      $exclude_user_label = $(document).find('#_enable_user_rule_exclude-container label:first-child'),
      $exclude_user_desc = $(document).find('#_enable_user_rule_exclude-container span.description');

    if ('exclude_items' === value) {
      value = 'bulk';
    }

    if (typeof yith_ywdpd_admin.labels.rule_type[value] !== 'undefined') {
      var field = yith_ywdpd_admin.labels.rule_type[value];

      if (typeof field.rule_for !== 'undefined') {
        $rule_for.html(field.rule_for.label);
      }
      if (typeof field.quantity_based !== 'undefined') {
        $quantity_based.html(field.quantity_based.label);
      }
      if (typeof field.user_rules !== 'undefined') {
        $user_rules.html(field.user_rules.label);
      }
      if (typeof field.schedule_discount_mode !== 'undefined') {
        $schedule_discount_mode.html(field.schedule_discount_mode.label);
      }
      if (typeof field.table_note_apply_to !== 'undefined') {
        $table_note_apply_to.html(field.table_note_apply_to.label);
        $table_note_apply_to_desc.html(field.table_note_apply_to.desc);
      }

      if (typeof field.enable_user_rule_exclude !== 'undefined') {
        $exclude_user_label.html(field.enable_user_rule_exclude.label);
        $exclude_user_desc.html(field.enable_user_rule_exclude.desc);
      }
    }
  };
  $(document).on('change', '#_discount_mode-container input[type="radio"]', function (e) {

    var value = $(this).val()

    change_label(value);

    if ('discount_whole' === value || 'category_discount' === value) {
      $('#_rule_for-all_products').click();
    }

    if ('category_discount' === value) {
      show_or_hide_field($('#_exclude_rule_for-specific_categories').parent(), false);
    } else {
      show_or_hide_field($('#_exclude_rule_for-specific_categories').parent(), true);
    }

  });


  //CART RULES

  var show_right_options = function (toggle_element, add_new) {

      var class_to_toggle = '';

      if (add_new) {
        class_to_toggle = '.yith-add-box-row';
      } else {
        class_to_toggle = '.yith-toggle-content-row';
      }

      var rows_to_toggle = toggle_element.find(class_to_toggle).not('.ywdpd_general_rule'),
        toggle_type = toggle_element.find(class_to_toggle).find('.ywdpd_condition_for').val(),
        rows_to_hide = rows_to_toggle.not('.' + toggle_type),
        rows_to_show = rows_to_toggle.filter('.' + toggle_type),
        single_row = '';


      rows_to_hide.addClass('hide_row');


      if ('customers' === toggle_type) {

        single_row = rows_to_show.find('.user_discount_to').parents(class_to_toggle);
        show_right_user_options(single_row, rows_to_show, class_to_toggle);
        single_row.removeClass('hide_row');
      } else if ('cart_items' === toggle_type) {
        single_row = rows_to_show.find('.ywdpd_cart_item_qty_type').parents(class_to_toggle);
        show_right_cart_item_options(single_row, rows_to_show, class_to_toggle);

        single_row.removeClass('hide_row');
      } else if ('product' === toggle_type) {
        single_row = rows_to_show.find('.ywdpd_product_type').parents(class_to_toggle);
        show_right_product_options(single_row, rows_to_show, class_to_toggle);
        single_row.removeClass('hide_row');
      } else {
        rows_to_show.removeClass('hide_row');
      }

    },
    show_right_user_options = function (element, rows, class_toggle) {

      var users_type = rows.find('.user_discount_to input[type="radio"]:checked').val(),
        sub_rows_to_show = rows.filter('.' + users_type),
        sub_row_to_hide = rows.not('.' + users_type);


      sub_row_to_hide.addClass('hide_row');

      if ('all' === users_type) {
        var single_sub_row = sub_rows_to_show.find('.ywdpd_enable_exclude_users').parents(class_toggle);
        show_right_exclude_user_options(sub_rows_to_show);
        single_sub_row.removeClass('hide_row');
      } else {
        sub_rows_to_show.removeClass('hide_row');
      }

    },
    show_right_exclude_user_options = function (rows) {
      var show = rows.find('.ywdpd_enable_exclude_users input[type="checkbox"]').is(':checked');

      if (show) {
        rows.filter('.customers_list_excluded').removeClass('hide_row');
      } else {

        rows.filter('.customers_list_excluded').addClass('hide_row');
      }

    },
    show_right_cart_item_options = function (element, rows, class_toggle) {
      var cart_item_type = rows.find('.ywdpd_cart_item_qty_type input[type="radio"]:checked').val(),
        sub_rows_to_show = rows.filter('.' + cart_item_type),
        sub_row_to_hide = rows.not('.' + cart_item_type);

      sub_rows_to_show.removeClass('hide_row');
      sub_row_to_hide.addClass('hide_row');

    },
    show_right_product_options = function (element, rows, class_toggle) {
      var type = rows.find('.ywdpd_product_type input[type="radio"]:checked').val(),
        sub_rows_to_show = rows.filter('.' + type),
        sub_rows_to_hide = rows.not('.' + type);

      sub_rows_to_hide.addClass('hide_row');
      if ('require_product' === type) {

        var s1 = sub_rows_to_show.find('.ywdpd_enable_require_product').parents(class_toggle),
          s2 = sub_rows_to_show.find('.ywdpd_enable_require_product_categories').parents(class_toggle),
          s3 = sub_rows_to_show.find('.ywdpd_enable_require_product_tag').parents(class_toggle),
          s4 = sub_rows_to_show.find('.ywdpd_enable_require_product_vendors').parents(class_toggle),
          s5 = sub_rows_to_show.find('.ywdpd_enable_require_product_brands').parents(class_toggle);

        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_require_product', '.enable_require_product_list');
        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_require_product_categories', '.enable_require_product_category_list');
        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_require_product_tag', '.enable_require_product_tag_list');
        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_require_product_vendors', '.enable_require_product_vendors_list');
        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_require_product_brands', '.enable_require_product_brands_list');
        s1.removeClass('hide_row');
        s2.removeClass('hide_row');
        s3.removeClass('hide_row');
        s4.removeClass('hide_row');
        s5.removeClass('hide_row');
      } else if ('exclude_product' === type) {
        var s1 = sub_rows_to_show.find('.ywdpd_enable_exclude_require_product').parents(class_toggle),
          s2 = sub_rows_to_show.find('.ywdpd_enable_exclude_on_sale_product').parents(class_toggle),
          s3 = sub_rows_to_show.find('.ywdpd_enable_exclude_product_categories').parents(class_toggle),
          s4 = sub_rows_to_show.find('.ywdpd_enable_exclude_product_tag').parents(class_toggle),
          s5 = sub_rows_to_show.find('.ywdpd_enable_exclude_product_vendors').parents(class_toggle),
          s6 = sub_rows_to_show.find('.ywdpd_enable_exclude_product_brands').parents(class_toggle);

        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_exclude_require_product', '.enable_exclude_product_list');
        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_exclude_product_categories', '.enable_exclude_product_category_list');
        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_exclude_product_tag', '.enable_exclude_product_tag_list');
        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_exclude_product_vendors', '.enable_exclude_product_vendors_list');
        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_exclude_product_brands', '.enable_exclude_product_brands_list');

        s1.removeClass('hide_row');
        s2.removeClass('hide_row');
        s3.removeClass('hide_row');
        s4.removeClass('hide_row');
        s5.removeClass('hide_row');
        s6.removeClass('hide_row');

      } else if ('disable_product' === type) {
        var s1 = sub_rows_to_show.find('.ywdpd_enable_disable_product').parents(class_toggle),
          s2 = sub_rows_to_show.find('.ywdpd_enable_disable_product_categories').parents(class_toggle),
          s3 = sub_rows_to_show.find('.ywdpd_enable_disable_product_tag').parents(class_toggle),
          s4 = sub_rows_to_show.find('.ywdpd_enable_disable_product_brands').parents(class_toggle);

        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_disable_product', '.enable_disable_product_list');
        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_disable_product_categories', '.enable_disable_product_category_list');
        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_disable_product_tag', '.enable_disable_product_tag_list');
        show_right_product_list_options(sub_rows_to_show, '.ywdpd_enable_disable_product_brands', '.enable_disable_product_brands_list');


        s1.removeClass('hide_row');
        s2.removeClass('hide_row');
        s3.removeClass('hide_row');
        s4.removeClass('hide_row');

      }
    },
    show_right_product_list_options = function (rows, field_id_to_check, field_to_hide) {
      var show = rows.find(field_id_to_check + ' input[type="checkbox"]').is(':checked');


      if (show) {
        rows.filter(field_to_hide).removeClass('hide_row');
      } else {

        rows.filter(field_to_hide).addClass('hide_row');
      }
    };

  $(document).on('yith-add-box-button-toggle', function (e, element) {

    var single_add_toggle = $(document).find('#_cart_discount_rules_add_box');
    show_right_options(single_add_toggle, true);

  });

  $(document).on('change', '.ywdpd_condition_for', function (e) {

    var single_row = $(this).parent().parent().parent(),
      toggle_element = '',
      is_new = false;

    if (single_row.hasClass('yith-add-box-row')) {
      toggle_element = $('#_cart_discount_rules_add_box');
      is_new = true;
    } else {
      toggle_element = $(this).closest('.yith-toggle-content');
    }
    show_right_options(toggle_element, is_new);
  });

  $(document).on('change', '.user_discount_to input[type="radio"]', function (e) {

    var toggle_element = $(this).closest('#_cart_discount_rules_add_box'),
      is_new = true;

    if (!toggle_element.length) {
      toggle_element = $(this).closest('.yith-toggle-content');
      is_new = false;
    }
    show_right_options(toggle_element, is_new);
  });

  $(document).on('change', '#_cart_discount_rules input[type="checkbox"]', function (e) {

    var toggle_element = $(this).closest('#_cart_discount_rules_add_box'),
      is_new = true;

    if (!toggle_element.length) {
      toggle_element = $(this).closest('.yith-toggle-content');
      is_new = false;
    }
    show_right_options(toggle_element, is_new);
  });

  $(document).on('change', '.ywdpd_cart_item_qty_type input[type="radio"], .ywdpd_product_type input[type="radio"]', function (e) {

    var toggle_element = $(this).closest('#_cart_discount_rules_add_box'),
      is_new = true;

    if (!toggle_element.length) {
      toggle_element = $(this).closest('.yith-toggle-content');
      is_new = false;
    }
    show_right_options(toggle_element, is_new);
  });

  $(document).on('yith-toggle-element-item-before-add', function (e, add_box, toggle_el, form_is_valid) {
    show_right_options(toggle_el, false);

  });

  $(document).on('yith-toggle-change-counter', function (e, hidden_obj, add_box) {

    if ('_cart_discount_rules_add_box' === add_box.attr('id')) {
      var toggle_element = add_box.parents('.toggle-element').find('.yith-toggle-row'),
        max_index = 0;

      toggle_element.each(function () {

        var current_index = $(this).data('item_key');

        if (max_index < current_index) {
          max_index = current_index;
        }
      });

      hidden_obj.val(max_index + 1);
    }
  });

  $(document).on('ywdpd-init-fields', function (e) {

    //init price rule labels
    var current_option = $(document).find('#_discount_mode-container input[type="radio"]:checked').val();
    change_label(current_option);

    if ('discount_whole' === current_option || 'category_discount' === current_option) {
      $('#_rule_for-all_products').click();
    }


    //init schedule fields
    var schedule_opt = $(document).find('#schedule_mode input[type="radio"]:checked').val();
    if ('no_schedule' === schedule_opt) {
      $(document).find('#_schedule_discount_mode-container .yith-plugin-fw-field-schedule').hide();
    } else {
      $(document).find('#_schedule_discount_mode-container .yith-plugin-fw-field-schedule').show();
    }

    //init exclude items

    disable_unnecessary_option($(document).find('#_rule_for input[type="radio"]:checked'));
    disable_unnecessary_option($(document).find('#_rule_apply_adjustment_discount_for input[type="radio"]:checked'));
    disable_unnecessary_user_option($(document).find('#_user_rules input[type="radio"]:checked'));
    //init toggle elements for cart rules
    var toggle_elements = $(document).find('#_cart_discount_rules .yith-toggle-content');

    toggle_elements.each(function () {

      show_right_options($(this), false);
    });

    if ('category_discount' === current_option) {
      show_or_hide_field($('#_exclude_rule_for-specific_categories').parent(), false);
    } else {
      show_or_hide_field($('#_exclude_rule_for-specific_categories').parent(), true);
    }

  }).trigger('ywdpd-init-fields');

  if ($(document).find('body.ywdpd-discount-type-pricing').length) {

    var div_html = $('<div>');

    div_html.attr('id', 'ywdpd_error_message');
    div_html.attr('title', yith_ywdpd_admin.message_alert.title);
    div_html.append('<p>' + yith_ywdpd_admin.message_alert.desc + '</p>')
    $(document).find('.wp-list-table').before(div_html);

    $('#ywdpd_error_message').on('dialogcreate',function( event, ui ) {
      $('.ui-dialog-title').before('<span class="alert-rule"></span>');
    });
    var dialog = $('#ywdpd_error_message').dialog({
      resizable: false,
      autoOpen: false,
      height: "auto",
      width: 400,
      modal: true
    });



    $(document).on('click','.ywdpd-alert-rule',function (e){
        e.preventDefault();
        dialog.dialog({
          position: { my: "left center", at: "right center", of: $(this) }
        });

      dialog.dialog("open");
    });
  }
});
