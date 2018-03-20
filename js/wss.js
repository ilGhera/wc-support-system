/**
 * Annulla l'invio del ticket se non è stato selezionato alcun prodotto
 */
	

/**
 * Impedisce l'invio di un ticket se non è stato selezionato un prodotto tra quelli acquistati dall'utente
 */
var check_ticket_product = function(){
	jQuery(function($){
		$('.send-new-ticket').on('click', function(event){
			var product_id = $('.product-id').val();
			if(product_id == 'null') {
				event.preventDefault();
				$('.product-alert').html('<div class="alert alert-warning">Please, choose a product for your support request.</div>');				
			} 
		})
	})
}


/**
 * Mostra tutti i threads del ticket selezionato, nascondendo tutti gli altri.
 */
var get_ticket_content = function() {
	jQuery(function($){
		$('.ticket-toggle').on('click', function(){
			var ticket_id = $(this).data('ticket-id');

			/*Nascondo gli altri ticket*/

			/*Front-end*/
			$('.support-tickets-table tbody tr').removeClass('opened').hide();

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
 * Apre il ticket dato dopo l'aggiunta di un nuovo thread
 * @param  {int} ticket_id l'id del ticket da aprire
 */
var auto_open_ticket = function(ticket_id){
	jQuery(function($){
		setTimeout(function(){
			$('tr.ticket-' + ticket_id + ' .ticket-toggle').trigger('click');
		}, 1000)
	})
}



/**
 * Modifica l'url a ticket/ thread inviato, utile in caso di reload di pagina
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
 * Cancellazione del singolo ticket e di tutti i thread ad esso appartenenti
 */
var delete_single_ticket = function(){
	jQuery(function($){
		$(document).on('click', '.delete-ticket', function(){
			var confirmed = confirm('Sicuro di voler eliminare il ticket e tutti i messaggi contenuti?');
			if(confirmed) {
				var ticket_id = $(this).data('ticket-id');
				var data = {
					'action': 'delete-ticket',
					'ticket_id': ticket_id
				}
				$.post(ajaxurl, data, function(response){
					$('.ticket-' + ticket_id).hide('slow');
					console.log(response);
				})				
			}
		})
	})
}


/**
 * Delete single thread in back-end
 * @param  {int} thread_id	l'id del thread da eliminare
 */
var delete_single_thread = function(){
	jQuery(function($){
		$(document).on('click', '.delete-thread', function(){
			var confirmed = confirm('Sicuro di voler eliminare il ticket e tutti i messaggi contenuti?');
			if(confirmed) {
				var thread_id = $(this).data('thread-id');
				var data = {
					'action': 'delete-thread',
					'thread_id': thread_id
				}
				$.post(ajaxurl, data, function(response){
					$('.thread-' + thread_id).hide('slow');
					console.log(response);
				})
			}
		})
	})
}


/**
 * Invia il nuovo stato del ticket perchè venga salvato nel db
 * @param  {int} ticket_id l'id del ticket da aggiornare
 */
var change_ticket_status = function(ticket_id){
	jQuery(function($){
		$(document).on('click', '.status-selector .label', function(){
			var status = $(this).closest('.status').data('status');
			var data = {
				'action': 'change-ticket-status',
				'ticket_id': ticket_id,
				'new_status': status
			}
			$.post(ajaxurl, data, function(response){
				$('tr.ticket-' + ticket_id + ' td.status').html(response);
			})
		})
	})
}


/**
 * Mostra la finestra modale per cambiare manualmente lo stato del ticket di supporto
 */
var modal_change_ticket_status = function(){
	jQuery(function($){
		$(document).on('click', '.label.toggle', function(){
			var tr = $(this).closest('tr');
			var ticket_id = $('.ticket-toggle', tr).data('ticket-id');

			$('#ticket-status-modal').attr('data-ticket-id', ticket_id);
			$('.modal-title span').html(' #' + ticket_id);
			$('.status-selector div').removeClass('active');

			var data = {
				'action': 'get-current-status',
				'ticket_id': ticket_id
			}

			$.post(ajaxurl, data, function(response){
				console.log(response);
				$('.status-selector .status-' + response).addClass('active');
				change_ticket_status(ticket_id);
			})
		
		})
	})
}


jQuery(document).ready(function($){
	
	/*Form invio nuovo ticket*/
	$('.new-ticket').on('click', function(){
		$('.premium-ticket-container').show();
		$(this).hide();
		$('.ticket-cancel').show();
	})	

	$('.ticket-cancel').on('click', function(){
		$('.premium-ticket-container').hide();		
		$(this).hide();
		$('.new-ticket').show();
	})

	/*Form invio nuovo thread*/
	$('.new-thread').on('click', function(){
		var ticket_id = $('.opened .ticket-toggle').data('ticket-id');
		$('.premium-thread-container form input.ticket-id').attr('value', ticket_id);

		$('.premium-thread-container').show();
		$(this).hide();
		$('.thread-cancel').show();
	})	

	$('.thread-cancel').on('click', function(){
		$('.premium-thread-container').hide();		
		$(this).hide();
		$('.new-thread').show();
	})

	/*Mostra un alert se non è stato selezionato un prodotto all'invio di un ticket*/
	check_ticket_product();
	$('.product-id').on('change', function(){
		if($(this).val() != 'null') {
			$('.alert.alert-warning').remove();
		}
	})

	/*Chiude il singolo ticket per tornare all'elenco principale*/
	$('.back-to-tickets').on('click', function(){
		$('.support-tickets-table tbody tr').removeClass('opened').show();
		$('.wp-list-table.tickets tbody tr').removeClass('opened').show();
		$('.thread-tools').hide();
		$('.single-ticket-content').html('');
		$('.button.new-ticket').show();
		$('.new-thread').hide();
		$('.premium-thread-container').hide();		
		$('.thread-cancel').hide();
	})

	/*Pulsante Exit*/
	$('.page.type-page').css('position', 'relative');
	$('.support-exit-button').prependTo('.page.type-page').show('slow');
	$('.support-exit-button').on('click', function(){
		document.cookie = "wss-support-access=; expires=expires=Thu, 01 Jan 1970 00:00:00 UTC;";
		document.cookie = "wss-guest-name=; expires=expires=Thu, 01 Jan 1970 00:00:00 UTC;";
		document.cookie = "wss-guest-email=; expires=expires=Thu, 01 Jan 1970 00:00:00 UTC;";
		document.cookie = "wss-order-id=; expires=expires=Thu, 01 Jan 1970 00:00:00 UTC;";
	    var url = window.location.href;
	    window.location.href = url;
	})

	/*Mostra il pulsante New thread solo se il ticket non è chiuso*/
	$('.ticket-toggle').each(function(){
		$(this).on('click', function(){
			var ticket = $(this).closest('tr');
			var status = $('td.status', ticket).data('status-id');
			if(status != 3) {
				$('.new-thread').show();
			}
		})
	})
})