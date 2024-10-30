var GFBillingoToggleItems;

jQuery(document).ready(function($) {
  var $gfbillingo_unit_name = $('#gaddon-setting-row-single_item_name');

  GFBillingoToggleItems = function(e) {
    var gfbillingo_unit_type = $('input[name="_gaddon_setting_items"]:checked').val();
    if(gfbillingo_unit_type == 'single_total') {
      $gfbillingo_unit_name.show();
    } else {
      $gfbillingo_unit_name.hide();
    }
  }

  GFBillingoToggleItems();

  function GFBillingoBlockMetabox(button, color) {
    button.block({
      message: null,
      overlayCSS: {
        background: color+' url(' + gravityformsbillingo_admin_js_strings.loading + ') no-repeat center',
        backgroundSize: '16px 16px',
        opacity: 0.6
      }
    });
  }

  function GFBillingoGenerateResponse(responseText, button) {
    //Remove old messages
    $('.gf-szamlazz-message').remove();

    //Generate the error/success messages
    if (responseText.data.error) {
      button.before('<div class="gf-billingo-error error gf-billingo-message"></div>');
    } else {
      button.before('<div class="gf-billingo-success updated gf-billingo-message"></div>');
    }

    //Get the error messages
    var ul = $('<ul>');
    $.each(responseText.data.messages, function(i, value) {
      var li = $('<li>')
      li.append(value);
      ul.append(li);
    });
    $('.gf-billingo-message').append(ul);
  }

  $('#gf_billingo_generate').click(function(e) {
    e.preventDefault();
    var r = confirm("Biztosan létrehozod a számlát?");
    if (r != true) {
      return false;
    }
    var nonce = $(this).data('nonce');
    var order = $(this).data('order');
    var button = $('#gf-billingo-generate-button');
    var note = $('#gf_billingo_invoice_note').val();
    var deadline = $('#gf_billingo_invoice_deadline').val();
    var completed = $('#gf_billingo_invoice_completed').val();
    var request = $('#gf_billingo_invoice_request').is(':checked');
    if (request) {
      request = 'on';
    } else {
      request = 'off';
    }

    var data = {
      action: 'gf_billingo_generate_invoice',
      nonce: nonce,
      order: order,
      note: note,
      deadline: deadline,
      completed: completed,
      request: request
    };

    GFBillingoBlockMetabox(button, '#fff');

    $.post(ajaxurl, data, function(responseText) {

      GFBillingoGenerateResponse(responseText, button);

      //If success, hide the button
      if (!responseText.data.error) {
        button.slideUp();
        button.before(responseText.data.link);
        if(responseText.data.link_delivery) {
          button.before(responseText.data.link_delivery);
        }
      }

      button.unblock();

    });
  });

  $('#gf_billingo_options').click(function() {
    $('#gf_billingo_options_form').slideToggle();
    return false;
  });

  //Teljesítettnek jelölés
  $('#gf_billingo_generate_complete').click(function(e) {
    e.preventDefault();
    var r = confirm("Biztosan teljesítve lett?");
    if (r != true) {
      return false;
    }

    var nonce = $(this).data('nonce');
    var order = $(this).data('order');
    var form = $('#gf-billingo-generate-button');
    var button = $(this);

    var data = {
      action: 'gf_billingo_complete',
      nonce: nonce,
      order: order
    };

    GFBillingoBlockMetabox(form, '#fff');

    $.post(ajaxurl, data, function(responseText) {

      GFBillingoGenerateResponse(responseText, form);

      //If success, hide the button
      if (!responseText.data.error) {
        button.slideUp();
        button.before(responseText.data.link);
      }

      form.unblock();

    });
  });

  //Teljesítettnek jelölés
  $('#gf_billingo_generate_void').click(function(e) {
    e.preventDefault();
    var r = confirm("Biztosan sztornózva lesz?");
    if (r != true) {
      return false;
    }

    var nonce = $(this).data('nonce');
    var order = $(this).data('order');
    var form = $('#gf-billingo-generate-button');
    var button = $(this);

    var data = {
      action: 'gf_billingo_void',
      nonce: nonce,
      order: order
    };

    GFBillingoBlockMetabox(form, '#fff');

    $.post(ajaxurl, data, function(responseText) {

      GFBillingoGenerateResponse(responseText, form);

      //If success, hide the button
      if (!responseText.data.error) {
        button.slideUp();
        button.before(responseText.data.link);
        $('#gf-billingo-generated-data').slideUp();
      }

      form.unblock();

    });
  });

  // Hide notice
	$( '.gf-billingo-notice .gf-billingo-hide-notice').on('click', function(e) {
		e.preventDefault();
		var el = $(this).closest('.gf-billingo-notice');
		$(el).find('.gf-billingo-wait').remove();
		$(el).append('<div class="gf-billingo-wait"></div>');
		if ( $('.gf-billingo-notice.updating').length > 0 ) {
			var button = $(this);
			setTimeout(function(){
				button.triggerHandler( 'click' );
			}, 100);
			return false;
		}
		$(el).addClass('updating');
		$.post( ajaxurl, {
				action: 	'gf_billingo_hide_notice',
				security: 	$(this).data('nonce'),
				notice: 	$(this).data('notice'),
				remind: 	$(this).hasClass( 'remind-later' ) ? 'yes' : 'no'
		}, function(){
			$(el).removeClass('updating');
			$(el).fadeOut(100);
		});
	});

	$('#gf_billingo_wc_billingo_pro_key_activate').click(function(e){
    e.preventDefault();

    var key = $('#gf_billingo_pro_key').val();
    var email = $('#gf_billingo_pro_email').val();
    var button = $(this);
    var form = button.parents('.gform-settings-panel__content');

    var data = {
      action: 'gf_billingo_pro_check',
      key: key,
      email: email,
			nonce: button.data('nonce')
    };

    GFBillingoBlockMetabox(form, '#F6FBFD');

    form.find('.notice').hide();

    $.post(ajaxurl, data, function(response) {
      //Remove old messages
      if(response.success) {
        window.location.reload();
        return;
      } else {
        form.find('.alert_red p').html(response.data.message);
        form.find('.alert_red').show();
      }
      form.unblock();
    });

		return false;

  });

  $('#gf_billingo_wc_billingo_pro_key_deactivate').click(function(e){
    e.preventDefault();

    var button = $(this);
    var form = button.parents('.gform-settings-panel__content');

    var data = {
      action: 'gf_billingo_pro_deactivate',
			nonce: button.data('nonce')
    };

    GFBillingoBlockMetabox(form, '#fff');

    form.find('.notice').hide();

    $.post(ajaxurl, data, function(response) {
      //Remove old messages
      if(response.success) {
        window.location.reload();
        return;
      } else {
        form.find('.alert_red p').html(response.data.message);
        form.find('.alert_red').show();
      }
      form.unblock();
    });

  });

});
