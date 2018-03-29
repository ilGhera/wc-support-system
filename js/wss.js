/**
 * A product must be selected for opening a ticket
 */
var check_ticket_product = function(){
	jQuery(function($){
		$('.send-new-ticket').on('click', function(event){
			var product_id = $('.product-id').val();
			if(product_id == 'null') {
				event.preventDefault();

				var data = {
					'action': 'product-select-warning'
				}
				$.post(ajaxurl, data, function(response){
					$('.product-alert').html(response);						
				})
			} 
		})
	})
}


/**
 * Clicking on a ticket, all his threads are shown and all the other tickets hidden
 */
var get_ticket_content = function() {
	jQuery(function($){
		$('.ticket-toggle').on('click', function(){
			var ticket_id = $(this).data('ticket-id');

			/*Nascondo gli altri ticket*/

			/*Front-end*/
			$('.support-tickets-table tbody tr').removeClass('opened').hide()
;
			/*Back-end*/
			$('.wp-list-table.tickets tbody tr').removeClass('opened').hide();

			$('.ticket-' + ticket_id).addClass('opened').show();


			/*Nascondo il pulsante per New ticket*/
			$('.button.new-ticket').hide();

			$('.thread-tools').show();

			var data = {
				'action': 'get_ticket_content',
				'ticket_id': ticket_id
			}
			$.post(ajaxurl, data, function(response){
				$('.single-ticket-content').html(response);
			})
		})
	})
}


/**
 * If set in the plugin options, the ticket is reopened after a new thread was sent
 * @param  {int} ticket_id
 */
var auto_open_ticket = function(ticket_id){
	jQuery(function($){
		setTimeout(function(){
			$('tr.ticket-' + ticket_id + ' .ticket-toggle').trigger('click');
		}, 1000)
	})
}



/**
 * Avoids to send the same ticket/ thread on page reload
 */
var avoid_resend = function(){
	jQuery(function($){
		setTimeout(function(){
		    var url = window.location.href + '?sent=1';
		    window.history.pushState({}, 'support', url);
		}, 1000);
	})
}


/**
 * Fires the delete of a specific ticket with all his threads - back-end
 */
var delete_single_ticket = function(alert_message){
	jQuery(function($){
		$(document).on('click', '.column-delete img', function(){
			var confirmed = confirm(alert_message);
			if(confirmed) {
				var ticket_id = $(this).data('ticket-id');
				var data = {
					'action': 'delete-ticket',
					'ticket_id': ticket_id
				}
				$.post(ajaxurl, data, function(response){
					$('.ticket-' + ticket_id).hide('slow');
				})				
			}
		})
	})
}


/**
 * Fires the delete of a specific thread - back-end
 */
var delete_single_thread = function(alert_message){
	jQuery(function($){
		$(document).on('click', '.delete-thread', function(){
			var confirmed = confirm(alert_message);
			if(confirmed) {
				var thread_id = $(this).data('thread-id');
				var data = {
					'action': 'delete-thread',
					'thread_id': thread_id
				}
				$.post(ajaxurl, data, function(response){
					$('.thread-' + thread_id).hide('slow');
				})
			}
		})
	})
}


/**
 * Send the new ticket status for being saved in the db
 * @param  {int} ticket_id
 */
var change_ticket_status = function(ticket_id, update_time){
	jQuery(function($){
		$('.status-selector .label').off('click').on('click', function(event){

			var status = $(this).data('status');

			var data = {
				'action': 'change-ticket-status',
				'ticket_id': ticket_id,
				'update_time': update_time,
				'new_status': status
			}
			$.post(ajaxurl, data, function(response){
				$('tr.ticket-' + ticket_id + ' td.status').html(response);
			})
		})
	})
}


/**
 * Show the modal window for changing the tickets status in back-end
 */
var modal_change_ticket_status = function(){
	jQuery(function($){
		$(document).on('click', '.column-status .label.toggle', function(e){

			var tr 			= $(this).closest('tr');
			var ticket_id   = $('.ticket-toggle', tr).data('ticket-id');
			var status_id   = $(this).data('status');
			var update_time = $('.update_time', tr).text();

			/*Modal window changes*/
			$('#ticket-status-modal').attr('data-ticket-id', ticket_id);
			$('.modal-title span').html(' #' + ticket_id);
			$('.status-selector div').removeClass('active');

			/*Show the current status in the modal window*/
			$('.status-selector .status-' + status_id).addClass('active');

			if(ticket_id) {
				console.log(ticket_id);
				change_ticket_status(ticket_id, update_time);
			}	

		})
	})
}


jQuery(document).ready(function($){
	
	/*New ticket form*/
	$('.new-ticket').on('click', function(){
		$('.wss-ticket-container').show();
		$(this).hide();
		$('.ticket-cancel').show();
	})	

	$('.ticket-cancel').on('click', function(){
		$('.wss-ticket-container').hide();		
		$(this).hide();
		$('.new-ticket').show();
	})

	/*New thread form*/
	$('.new-thread').on('click', function(){
		var ticket_id = $('.opened .ticket-toggle').data('ticket-id');
		$('.wss-thread-container input.ticket-id').attr('value', ticket_id);

		$('.wss-thread-container').show();
		$(this).hide();
		$('.thread-cancel').show();
	})	

	$('.thread-cancel').on('click', function(){
		$('.wss-thread-container').hide();		
		$(this).hide();
		$('.new-thread').show();
	})

	/*A product must be selected alert*/
	check_ticket_product();
	$('.product-id').on('change', function(){
		if($(this).val() != 'null') {
			$('.alert.alert-warning').remove();
		}
	})

	/*Close the single ticket and go back to the list*/
	$('.back-to-tickets').on('click', function(){
		$('.support-tickets-table tbody tr').removeClass('opened').show();
		$('.wp-list-table.tickets tbody tr').removeClass('opened').show();
		$('.thread-tools').hide();
		$('.single-ticket-content').html('');
		$('.button.new-ticket').show();
		$('.new-thread').hide();
		$('.wss-thread-container').hide();		
		$('.thread-cancel').hide();
	})

	/*Support exit button for not logged in users*/
	$('.page.type-page').css('position', 'relative');
	$('.support-exit-button').prependTo('.page.type-page').show();
	$('.support-exit-button').on('click', function(){
		document.cookie = "wss-support-access=; expires=expires=Thu, 01 Jan 1970 00:00:00 UTC;";
		document.cookie = "wss-guest-name=; expires=expires=Thu, 01 Jan 1970 00:00:00 UTC;";
		document.cookie = "wss-guest-email=; expires=expires=Thu, 01 Jan 1970 00:00:00 UTC;";
		document.cookie = "wss-order-id=; expires=expires=Thu, 01 Jan 1970 00:00:00 UTC;";
	    var url = window.location.href;
	    window.location.href = url;
	})

	/*If the ticket is closed the new thread button is not available*/
	$('.ticket-toggle').each(function(){
		$(this).on('click', function(){
			var ticket = $(this).closest('tr');
			var status = $('td.status', ticket).data('status-id');
			if(status != 3) {
				$('.new-thread').show();
			}
		})
	})

	/*Show the create support page field in the plugin settings page*/
	$('#support-page').on('change', function(){
		if($(this).val() == 'new') {
			$('.create-support-page').fadeIn();
		}
	})

	/*Add support email if notifications are selected*/
	if( $('.user-notification').attr('checked') == 'checked' || $('.admin-notification').attr('checked') == 'checked' ) {
		$('.support-email-fields').show();
		$('.support-email').attr('required', 'required');
		$('.support-email-name').attr('required', 'required');
	}

	/*Show/ Hide support email fields on single notification change*/
	$('.user-notification, .admin-notification').on('change', function(){
		var other = $(this).hasClass('user-notification') ? $('.admin-notification') : $('.user-notification');  
		if( $(this).attr('checked') == 'checked' || $(other).attr('checked') == 'checked' ) {
			$('.support-email-fields').show();
			$('.support-email').attr('required', 'required');
			$('.support-email-name').attr('required', 'required');
		} else {
			$('.support-email-fields').fadeOut();
			$('.support-email').removeAttr('required');		
			$('.support-email-name').removeAttr('required');		
		}	
	})

	/*Show auto close fields if activated*/
	if( $('.auto-close-tickets').attr('checked') == 'checked' ) {
		$('.auto-close-fields').show();
		$('.auto-close-notice-text').attr('required', 'required');
	}

	/*Show/ Hide auto close ticket on change*/
	$('.auto-close-tickets').on('change', function(){
		if( $(this).attr('checked') == 'checked' ) {
			$('.auto-close-fields').fadeIn();
		} else {
			$('.auto-close-fields').fadeOut();
		}
	})

	/*Define the color field only in back-end*/
	var field = $('.wss-color-field');
	if(typeof field.wpColorPicker == 'function') { 
		$('.wss-color-field').wpColorPicker();
	}

})