<?php
/**
 * Servizio di supporto
 */

class wc_support_system {

	public $tickets_obj;

	public function __construct() {
		
		add_action('wss_cron_tickets_action', array($this, 'wss_cron_tickets'));

		add_action('admin_init', array($this, 'wss_tables'));
		add_action('admin_init', array($this, 'add_support_page'));
		add_action('admin_init', array($this, 'wss_save_settings'));
		add_action('admin_menu', array($this, 'register_wss_admin'));

		add_action('admin_enqueue_scripts', array($this, 'wss_admin_scripts'));

		add_action('wp_enqueue_scripts', array($this, 'wss_scripts'));

		add_action('admin_footer', array($this, 'ajax_admin_get_ticket_content'));
		add_action('admin_footer', array($this, 'ajax_delete_single_ticket'));
		add_action('admin_footer', array($this, 'ajax_delete_single_thread'));
		add_action('admin_footer', array($this, 'modal_change_ticket_status'));

		add_action('init', array($this, 'premium_support_access_validation'));
		add_action('init', array($this, 'save_new_premium_ticket'));
		add_action('init', array($this, 'save_new_premium_thread'));
		add_action('init', array($this, 'wss_avoid_resend'));

		add_action('wp_ajax_delete-ticket', array($this, 'delete_single_ticket_callback'));
		add_action('wp_ajax_delete-thread', array($this, 'delete_single_thread_callback'));
		add_action('wp_ajax_get-current-status', array($this, 'get_current_status_callback'));
		add_action('wp_ajax_change-ticket-status', array($this, 'change_ticket_status_callback'));
		add_action('wp_ajax_get_ticket_content', array($this, 'get_ticket_content_callback'));
		add_action('wp_ajax_nopriv_get_ticket_content', array($this, 'get_ticket_content_callback'));

		add_action('wp_footer', array($this, 'ajax_get_ticket_content'));

		add_shortcode('support-tickets-table', array($this, 'support_tickets_table'));
		add_filter('the_content', array($this, 'page_class_instance'));

		add_filter('set-screen-option', array($this, 'set_screen'), 10, 3);

	}


	/**
	 * Add the tickets table to the support page
	 * @param  string $content the content of the page
	 * @return mixed           the filtered content
	 */
	public function page_class_instance($content) {
		if(is_page('Support')) {
			$wss = new self();
			$output = do_shortcode('[support-tickets-table]') . $content;
			return $output;
		} else {
			return $content;
		}
	}


	/**
	 * Fogli di stile e script della classe per il back-end
	 */
	public function wss_admin_scripts() {
		$admin_page = get_current_screen();
		if($admin_page->base == 'toplevel_page_wc-support-system') {
		    wp_enqueue_script('bootstrap-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js');
			wp_enqueue_script('wss-script', plugin_dir_url(__DIR__) . '/js/wss.js', array('jquery'));			
		    wp_enqueue_style('bootstrap-iso', plugin_dir_url(__DIR__) . '/css/bootstrap-iso.css');    
		    wp_enqueue_style('wss-admin-style', plugin_dir_url(__DIR__) . '/css/wss-admin-style.css');    
		}
	}


	/**
	 * Script front-end
	 */
	public function wss_scripts() {
		if(is_page('support')) {
			wp_enqueue_script('wss-script', plugin_dir_url(__DIR__) . '/js/wss.js', array('jquery'));			
		    wp_enqueue_style('wss-style', plugin_dir_url(__DIR__) . '/css/wss-style.css');
		    wp_enqueue_style('bootstrap-iso', plugin_dir_url(__DIR__) . '/css/bootstrap-iso.css');    
		}
	}


	/**
	 * Create the plugin db tables
	 */
	public function wss_tables() {
		global $wpdb;
		$wss_tickets = $wpdb->prefix . 'wss_support_tickets';
		$wss_threads = $wpdb->prefix . 'wss_support_threads';

		if($wpdb->get_var("SHOW TABLES LIKE '$wss_tickets'") != $wss_tickets) {

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $wss_tickets (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				title varchar(200) NOT NULL,
				user_id bigint(20) NOT NULL,
				user_name varchar(20) NOT NULL,
				user_email varchar(100) NOT NULL,
				product_id bigint(20) NOT NULL,
				status int(11) NOT NULL,
				create_time datetime NOT NULL,
				update_time datetime NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";
		
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
			dbDelta( $sql );
		
		}

		if($wpdb->get_var("SHOW TABLES LIKE '$wss_threads'") != $wss_threads) {

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $wss_threads (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				ticket_id bigint(20),
				content text NOT NULL,
				create_time datetime NOT NULL,
				user_id bigint(20) NOT NULL,
				user_name varchar(20) NOT NULL,
				user_email varchar(100) NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			
			dbDelta( $sql );
			
		}

	}	


	/**
	 * Create the Support page
	 */
	public function add_support_page() {
		if(!get_page_by_title('Support')) {
			wp_insert_post(
				array(
					'post_title'  => 'Support',
					'post_name'   => 'support',
					'post_content'=> '',
					'post_status' => 'publish',
					'post_type'   => 'page',

				)
			);	
		}
	}

	

	/**
	 * Dati dell'utente, loggato e  non
	 * @return array
	 */
	public function user_data() {
		$output = array();
		if(is_user_logged_in()) {
			$userdata = get_userdata(get_current_user_id());
			$output['id'] = $userdata->ID;
			$output['name'] = $userdata->display_name;
			$output['email'] = $userdata->user_email; 

			$output['admin'] = false;
			$roles = $userdata->roles;
			if(in_array('administrator', $roles)) {
				$output['admin'] = true;				
			}

			$output['order_id'] = null;

		} else {
			$id = 0;

			$name = null;
			if(isset($_COOKIE['wss-guest-name'])) {
				$name = sanitize_text_field($_COOKIE['wss-guest-name']);
			} elseif(isset($_POST['wss-guest-name'])) {
				$name = sanitize_text_field($_POST['wss-guest-name']);
			}

			$email = null;
			if(isset($_COOKIE['wss-guest-email'])) {
				$email = sanitize_text_field($_COOKIE['wss-guest-email']);
			} elseif(isset($_POST['wss-guest-email'])) {
				$email = sanitize_text_field($_POST['wss-guest-email']);
			}

			$order_id = null;
			if(isset($_COOKIE['wss-order-id'])) {
				$order_id = sanitize_text_field($_COOKIE['wss-order-id']);
			} elseif(isset($_POST['wss-order-id'])) {
				$order_id = sanitize_text_field($_POST['wss-order-id']);
			}

			$output['id'] = $id;
			$output['name'] = $name;
			$output['email'] = $email;				
			$output['admin'] = false;
			$output['order_id'] = $order_id;
		}

		return $output;
	}

	
	/**
	 * Restituisce i prodotti acquistati dall'utente specificato
	 * @return array        			gli id dei prodotti  
	 */
	public function get_user_products($order_id='', $user_email='') {

		$output = null;

		if(is_user_logged_in()) {
		 	$current_user = wp_get_current_user();
			$args = array(
			    'post_type'       => 'product',
			    'post_status'     => 'publish',
			    'meta_query'      => array(
			        array(
			            'key'     => '_visibility',
			            'value'   => array('catalog', 'visible'),
			            'compare' => 'IN'
			        )
			    )
			);

			$loop = new WP_Query($args);
			$output = null;
				 
			while($loop->have_posts()) : $loop->the_post();
				
				$bought = wc_customer_bought_product($current_user->user_email, $current_user->ID, get_the_ID());
				
				if($bought) {
					$output[] = get_the_ID(); 
				}

			endwhile; 
			wp_reset_query();

		} elseif($order_id && $user_email) {
			if(get_post_meta($order_id, '_billing_email', true) == $user_email) {
				$order = new WC_Order($order_id);
				$items = $order->get_items();			
				foreach ($items as $item) {
					$item_data = $item->get_data();
					$output[] = $item_data['product_id'];
				}
			}
		}
		 
		return $output;

	}

	
	/**
	 * Verifica i dati inseriti dall'utente (premium key e email) perchè possa accedere al supporto premium
	 * L'accesso avviene anche con premium key scaduta, l'utente non potrà pubblicare.
	 * @return bool
	 */
	public function premium_support_access_validation($setcookie=null) {
		
		if(isset($_POST['wss-support-access'])) {
		    //USER DATA
			$guest_name = $_POST['wss-guest-name'];
		    $email      = $_POST['wss-guest-email'];
		    $order_id 	= $_POST['wss-order-id'];
		    $validation = false;
		    $setcookie = $setcookie ? $setcookie : true;

		    $products = $this->get_user_products($order_id, $email);

		    if($products) {
		    	if($setcookie) {
			    	setcookie('wss-support-access', 1);
			    	setcookie('wss-guest-name', $guest_name);
			    	setcookie('wss-guest-email', $email);
			    	setcookie('wss-order-id', $order_id);
					
					exit;
		    	}
		    	
		    	$validation = true;
		    }	    

		    return $validation;
	    }
	}


	/**
	 * Verifica che l'utente loggato abbia diritto al supporto premium, in base agli acquisti fatti
	 * @return bool
	 */
	public function logged_in_user_support_access_validation() {
		$validation = false;

		if(is_user_logged_in()) {
			$userdata = $this->user_data(get_current_user_id());
			if( $this->get_user_products() ) {
				$validation = true;
			}
		}

		return $validation;
	}


	/**
	 * Restituisce tutti i ticket presenti nel db
	 * @return array
	 */
	public function get_tickets() {
		global $wpdb;
		$query = "
			SELECT * FROM " . $wpdb->prefix . "wss_support_tickets ORDER BY status ASC
		";
		$tickets = $wpdb->get_results($query);

		return $tickets;
	}


	/**
	 * Restituisce il numero di tickets in attesa di essere letti
	 * @return int
	 */
	public function get_awaiting_tickets() {
		global $wpdb;
		$query = "
			SELECT * FROM " . $wpdb->prefix . "wss_support_tickets WHERE status = 1
		";
		$tickets = $wpdb->get_results($query);

		return count($tickets);
	}


	public function wss_cron_tickets() {
		global $wpdb;
		$query = "
			SELECT * FROM " . $wpdb->prefix . "wss_support_tickets WHERE status = 2
		";
		$tickets = $wpdb->get_results($query);

		foreach ($tickets as $ticket) {
			$last_update = strtotime($ticket->update_time);
			$now  = strtotime('now');

			if( ($now - $last_update) >= 604800) {

				$message = '
					Hi, we have not heard back from you in a few days.<br>
					Do you need anything else from us for the support case: ' . $ticket->title . ' (case #' . $ticket->id . ')? If yes, please update the ticket on ilghera.com will get back to you asap.<br>
					If your questions have been answered, please disregard this message and we will mark this case as resolved.<br>
					Thanks!
				';

				/*Aggiornamento stato del ticket*/
				$date = date('Y-m-d H:i:s');
				$this->update_premium_ticket($ticket->id, $date, 3);

				/*Mail all'utente*/
				$this->support_premium_notification($ticket->id, 'ilGhera Support', $message, $ticket->user_email);

			}

		}

		// return $tickets;
	}


	/**
	 * Restituisce i ticket legati all'utente
	 * @param  int 	  $user_id    l'id dell'utente, 0 se non loggato
	 * @param  string $user_email la mail dell'utente				
	 * @return array
	 */
	public function get_user_tickets($user_id, $user_email) {
		global $wpdb;
		$query = "
			SELECT * FROM " . $wpdb->prefix . "wss_support_tickets WHERE user_id = '$user_id' AND user_email = '$user_email' ORDER BY create_time DESC
		";
		$tickets = $wpdb->get_results($query);

		return $tickets;
	}

	
	/**
	 * Form per la pubblicazione di un nuovo ticket
	 * @param  string $user_email la mail dell'utente
	 */
	public function create_new_ticket($order_id, $user_email) {
		?>
		<div class="premium-ticket-container" style="display: none;">
			<form method="POST" class="create-new-ticket" action="">
				<select name="product-id" class="product-id" style="margin: 1rem 0;">
					<?php 
					$products = $this->get_user_products($order_id, $user_email);
					if($products) {
						echo '<option value="null">Select a product</option>';
						foreach($products as $product) {
							if($product) {
								echo '<option value="' . $product . '">' . get_the_title($product) . '</option>';								
							}
						}
					}
					?>
				</select>
				<input type="text" name="title" placeholder="Ticket subject" required="required">
				<?php wp_editor('', 'premium-ticket'); ?>
				<input type="hidden" name="ticket-sent" value="1">
				<input type="submit" class="send-new-ticket" value="Send" style="margin-top: 1rem;">
			</form>
			<div class="bootstrap-iso product-alert"></div>
		</div>
		<a class="button new-ticket">New ticket</a>
		<a class="button ticket-cancel" style="display: none;">Cancel</a>
		<?php
	}


	/**
	 * Form per la pubblicazione di un nuovo thread
	 */
	public function create_new_thread() {
		?>
		<div class="premium-thread-container" style="display: none;">
			<form method="POST" action="">
				<?php 
				wp_editor('', 'premium-thread'); 
				?>
				<input type="hidden" class="ticket-id" name="ticket-id" value="">
				<input type="hidden" name="thread-sent" value="1">
				<input type="submit" class="send-new-thread button-primary" value="Send" style="margin-top: 1rem;">
			</form>
			<div class="bootstrap-iso"></div>
		</div>
		<div class="thread-tools">
			<a class="button back-to-tickets">Back to tickets</a>
			<a class="button new-thread button-primary" style="display: none;">New thread</a>
			<a class="button thread-cancel" style="display: none;">Cancel</a>
		</div>
		<?php	
	}


	/**
	 * Restituisce lo stato del ticket dato
	 * @param  int $ticket_id l'id del ticket
	 * @return int            l'id di stato del ticket di supporto
	 */
	public function get_ticket_status($ticket_id) {
		global $wpdb;
		$query = "
			SELECT status FROM " . $wpdb->prefix . "wss_support_tickets WHERE id = '$ticket_id'
		";
		$results = $wpdb->get_results($query);
		$output = $results ? $results[0]->status : null;
		return $output;
	}

	/**
	 * Restituisce la label dello stato del ticket
	 * @param  int $status_id l'id dello stato
	 * @return string
	 */
	public function get_ticket_status_label($status_id) {
		$output = null;
		switch ($status_id) {
			case 1:
				$output = '<span class="label label-danger toggle" data-toggle="modal" data-target="#ticket-status-modal">Open</span>'; 
				break;
			case 2:
				$output = '<span class="label label-warning toggle" data-toggle="modal" data-target="#ticket-status-modal">Pending</span>';
				break;
			case 3:
				$output = '<span class="label label-success toggle" data-toggle="modal" data-target="#ticket-status-modal">Closed</span>';
				break;			
		}
		
		return $output != null ? '<div class="bootstrap-iso">' . $output . '</div>' : $output;
	}


	/**
	 * Restituisce i threads di un dato ticket
	 * @param  int $ticket_id l'id del ticket
	 * @return array
	 */
	public function get_ticket_threads($ticket_id) {
		global $wpdb;
		$query = "
			SELECT * FROM " . $wpdb->prefix . "wss_support_threads WHERE ticket_id = '$ticket_id' ORDER BY id DESC
		";
		$results = $wpdb->get_results($query);

		return $results;
	}


	/**
	 * Ajax - Si attiva al click del singolo ticket
	 * Back-end
	 */
	public function ajax_admin_get_ticket_content() {
		$admin_page = get_current_screen();
		if($admin_page->base == 'toplevel_page_wc-support-system') {
			?>
			<script>
			jQuery(document).ready(function($){
				get_ticket_content();	
			})		
			</script>
			<?php
		}
	}


	/**
	 * Ajax - Si attiva al click del singolo ticket
	 */
	public function ajax_get_ticket_content() {
		if(is_page('support')) {
			?>
			<script>
			jQuery(document).ready(function($){
				get_ticket_content();	
			})		
			</script>
			<?php
		}
	}

	
	/**
	 * Callback - restituisce tutti i thread di un ticket e li mostra all'utente
	 * @return [type] [description]
	 */
	public function get_ticket_content_callback() {

		$ticket_id = $_POST['ticket_id'];

		global $wpdb;
		$query = "
			SELECT * FROM " . $wpdb->prefix . "wss_support_tickets WHERE id = '$ticket_id'
		";
		$results = $wpdb->get_results($query);

		if($results) {
			$ticket = $results[0];
			echo '<div id="premium-ticket" class="ticket-' . $ticket_id . '">';
				// echo '<div class="ticket-title">' . $ticket->title . '</div>';

				$threads = $this->get_ticket_threads($ticket_id);
				if($threads) {
					foreach ($threads as $thread) {
						echo '<div class="single-thread thread-' . $thread->id . (user_can($thread->user_id, 'administrator') ? ' answer' : '') . '">';
							echo '<div class="thread-header">';
								echo '<div class="left">' . get_avatar($thread->user_id, 50) . '</div>';
								echo '<div class="right">' . $thread->user_name . '<br><span class="date">' . date('d-m-Y H:i:s', strtotime($thread->create_time)) . '</span></div>';
								echo '<div class="clear"></div>';
								echo '<img class="delete-thread" data-thread-id="' . $thread->id . '" src="' . plugin_dir_url(__DIR__) . '/images/dustbin.png">';									
							echo '</div>';
							echo '<div class="thread-content">' . html_entity_decode(nl2br(wp_unslash($thread->content))) . '</div>';
						echo '</div>';
					}
				}

			echo '</div>';
		}
		exit;
	}


	/**
	 * Pulsante che consente all'utente non loggato, che abbia effettuato l'accesso al supporto premium, di uscire (cancellazione cookie)
	 */
	public function support_exit_button() {
		if(isset($_COOKIE['wss-support-access']) || $this->premium_support_access_validation(false)) {
			echo '<button type="button" class="btn btn-default support-exit-button"><img src="' . plugin_dir_url(__DIR__) . '/images/exit.png">Exit</button>';
		}
	}


	/**
	 * Tabella con l'elenco dei ticket dell'utente
	 */
	public function support_tickets_table() {
		if(isset($_COOKIE['wss-support-access']) || $this->premium_support_access_validation(false) || $this->logged_in_user_support_access_validation()) :
			$userdata = $this->user_data();
			$user_id = $userdata['id'];
			$user_email = $userdata['email'];
			$order_id = $userdata['order_id'];

			$this->support_exit_button();

			$tickets = $this->get_user_tickets($user_id, $user_email);
			if($tickets) {
				?>
				<table class="table support-tickets-table">
					<thead>
						<th style="padding-right: 2rem;">ID</th>
						<th>Subject</th>
						<th class="create-time">Creation time</th>
						<th class="update-time">Update time</th>
						<th>Product</th>
						<th>Status</th>
					</thead>
					<tbody>
					<?php
					foreach ($tickets as $ticket) {
						echo '<tr class="ticket-' . $ticket->id . '">';
							echo '<td>#' . $ticket->id . '</td>';
							echo '<td class="ticket-toggle" data-ticket-id="' . $ticket->id . '">' . $ticket->title . '</td>';
							echo '<td class="create-time">' . ($ticket->create_time ? date('d-m-Y H:i:s', strtotime($ticket->create_time)) : '') . '</td>';
							echo '<td class="update-time">' . ($ticket->update_time ? date('d-m-Y H:i:s', strtotime($ticket->update_time)) : '') . '</td>';
							echo '<td class="product">' . get_the_post_thumbnail($ticket->product_id, array(50,50)) . '</td>';
							echo '<td class="status" data-status-id="' . $ticket->status . '">' . $this->get_ticket_status_label($ticket->status) . '</td>';
						echo '</tr>';
					}
					?>
					</tbody>
				</table>
				<?php $this->create_new_thread(); ?>
				<div class="single-ticket-content"></div>
				<?php
			} else {
				echo '<div class="bootstrap-iso">';
					echo '<div class="alert alert-info">It seems like you have no support tickets opened at the moment.</div>';
				echo '</div>';
			}
			$this->create_new_ticket($order_id, $user_email); 
		elseif(is_user_logged_in()) :
			echo '<div class="bootstrap-iso">';
				echo '<div class="alert alert-danger">It seems like you haven\'t bought any productat the moment.</div>';
			echo '</div>';
		else :
			?>
			<form id="wes-support-access" method="POST" action="">
				<input type="text" name="wss-guest-name" id="wss-guest-name" placeholder="Your name" required="required">
				<input type="email" name="wss-guest-email" id="wss-guest-email" placeholder="Email (used for the order)" required="required">
				<input type="text" name="wss-order-id" id="wss-order-id" placeholder="The order id" required="required">
				<input type="hidden" name="wss-support-access" value="1">
				<input type="submit" value="Access">
			</form>
			<?php
		endif;
	}


	/**
	 * Ajax - Finestra modale che consente di modificare manualmente lo stato del ticket di supporto
	 * In asincrono richiede lo status del ticket
	 */
	public function modal_change_ticket_status() {
		$admin_page = get_current_screen();
		if($admin_page->base == 'toplevel_page_wc-support-system') {
			?>
			<script>
				jQuery(document).ready(function($){
					modal_change_ticket_status();
				})
			</script>
			<div class="bootstrap-iso">
				<div id="ticket-status-modal" class="modal fade" data-ticket-id="" role="dialog">
					<div class="modal-dialog">
						<!-- Modal content-->
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal">&times;</button>
								<h4 class="modal-title">Modifica lo stato del ticket<span></span></h4>
							</div>
							<div class="modal-body">
								<div class="row status-selector">
									<div class="col-xs-4 status status-1" data-status="1"><?php echo $this->get_ticket_status_label(1); ?></div>
									<div class="col-xs-4 status status-2" data-status="2"><?php echo $this->get_ticket_status_label(2); ?></div>
									<div class="col-xs-4 status status-3" data-status="3"><?php echo $this->get_ticket_status_label(3); ?></div>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}


	/**
	 * Callback - Restituisce lo stato attuale del ticket
	 * @return int l'id dello stato
	 */
	public function get_current_status_callback() {
		$ticket_id = isset($_POST['ticket_id']) ? $_POST['ticket_id'] : '';
		if($ticket_id) {
			$status = $this->get_ticket_status($ticket_id);
			echo $status;
		}
		exit;
	}


	/**
	 * Aggiorna il ticket a seguito dell'aggiunta di un nuovo thread
	 * @param  int 		$ticket_id l'id del ticket da aggiornare
	 * @param  string 	$date      la data del nuovo thread che coincide con quella di ultima modifica del ticket
	 */
	public function update_premium_ticket($ticket_id, $date, $status=1) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'wss_support_tickets',
			array(
				'status'	  => $status, 
				'update_time' => $date
			),
			array(
				'id' => $ticket_id,
			),
			array(
				'%d',
				'%s'
			)
		);
	}


	/**
	 * Callback - Aggiorna lo stato del ticket nel db dopo l'azione dell'amministratore.
	 */
	public function change_ticket_status_callback() {
		$ticket_id = isset($_POST['ticket_id']) ? $_POST['ticket_id'] : '';
		$new_status = isset($_POST['new_status']) ? $_POST['new_status'] : '';
		$date = date('Y-m-d H:i:s');
		if($ticket_id && $new_status) {
			$this->update_premium_ticket($ticket_id, $date, $new_status);
			$new_label = $this->get_ticket_status_label($new_status);
			echo $new_label;
		}
		exit;
	}


	/**
	 * Invia notifica all'amministratore alla pubblicazione di un nuovo thread
	 * @param  int 	  $ticket_id l'id del ticket a cui il thread è legato
	 * @param  string $user_name il nome dell'utente che ha pubblicato il thread
	 * @param  string $content   il contenuto del thread
	 * @param  string $to 		 l'indirizzo del destinatario	
	 */
	public function support_premium_notification($ticket_id, $user_name, $content, $to='support@ilghera.com') {
		$subject = $user_name . ' - Update ticket #' . $ticket_id;
		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		$headers[] = 'From: ilGhera | Wordpress Development <support@ilghera.com>';
		$message  = '<style>img {display: block; margin: 1rem 0; max-width: 700px; height: auto;}</style>';
		$message .= html_entity_decode(nl2br($content));
		$message .= '<p style="display: block; margin-top: 1.5rem; font-size: 12px; color: #666;">';
		$message .= 'Don\'t reply to this message, you can read all threads and update the ticket going to <a href="' . home_url() . '/premium-support"><b>Premium support</b></a>.</p>';
		$message .= '<b>ilGhera</b> | Wordpress Development';
		wp_mail($to, $subject, $message, $headers);
	}


	/**
	 * Inserisce nel db il nuovo thread
	 * @param  int 		$ticket_id  l'id del ticket d'assistenza
	 * @param  string 	$content    il testo
	 * @param  date 	$date       data della richiesta
	 * @param  int 		$user_id 	l'id dell'utente
	 * @param  string 	$user_name  nome dell'utente
	 * @param  string 	$user_email la mail
	 * @param  int 		$status     indica lo stato del ticket dopo l'aggiornamento, default 1 (open), 2 se a pubblicare il thread è l'amministratore 
	 */
	public function save_new_premium_ticket_thread($ticket_id, $content, $date, $user_id, $user_name, $user_email, $status=1) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'wss_support_threads',
			array(
				'ticket_id'   => $ticket_id,
				'content' 	  => $content,
				'create_time' => $date,
				'user_id'	  => $user_id,
				'user_name'   => $user_name,
				'user_email'  => $user_email 
			),
			array(
				'%d',
				'%s',
				'%s',
				'%d',
				'%s',
				'%s'
			)
		);

		$this->update_premium_ticket($ticket_id, $date, $status);

		if(user_can($user_id, 'administrator')) {
			$this->support_premium_notification($ticket_id, 'ilGhera Support', $content, $user_email);	
		} else {
			$this->support_premium_notification($ticket_id, $user_name, $content);	
		}
		add_action('wp_footer', array($this, 'wss_avoid_resend_footer_script'));
	}


	/**
	 * Richiama nel footer lo script per modificare l'url di pagine, utile in caso di reload
	 */
	public function wss_avoid_resend_footer_script() {
		?>
		<script>
			jQuery(document).ready(function($){
				avoid_resend();
			})
		</script>
		<?php
	}


	/**
	 * Se presente "sent" nell'url, indirizza nuovamente alla pagina di supporto evitando il resend delle informazioni
	 */
	public function wss_avoid_resend() {
		if(isset($_GET['sent'])) {
			header('Location: ' . home_url() . '/support');
			exit;
		}
	}


	/**
	 * Aggiunge un nuovo thread ad una richiesta di assistenza (ticket)
	 */
	public function save_new_premium_thread() {
		if(isset($_POST['thread-sent'])){
			$ticket_id = isset($_POST['ticket-id']) ? sanitize_text_field($_POST['ticket-id']) : '';

			$content 	= isset($_POST['premium-thread']) ? esc_html($_POST['premium-thread']) : '';
			$date = date('Y-m-d H:i:s');

			/*User info*/
			$user = $this->user_data();

			$ticket_status = user_can($user['id'], 'administrator') ? 2 : 1;
			$this->save_new_premium_ticket_thread($ticket_id, $content, $date, $user['id'], $user['name'], $user['email'], $ticket_status);

			if(user_can($user['id'], 'administrator') && get_option('wss-reopen-ticket')) {
				add_action('admin_head', array($this, 'auto_open_ticket'));
			} else {
				add_action('wp_footer', array($this, 'auto_open_ticket'));
			}
		}
	}


	/**
	 * Riapre il ticket dopo l'aggiunta di un thread
	 */
	public function auto_open_ticket() {
		$ticket_id = isset($_POST['ticket-id']) ? sanitize_text_field($_POST['ticket-id']) : '';
		?>
		<script>
			jQuery(document).ready(function($){
				var ticket_id = '<?php echo $ticket_id; ?>';
				auto_open_ticket(ticket_id);
			})
		</script>
		<?php
	}

	
	/**
	 * Aggiunge nel db il nuovo ticket
	 */
	public function save_new_premium_ticket(){

		if(isset($_POST['ticket-sent'])) {
			$title		= isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
			$product_id = isset($_POST['product-id']) ? sanitize_text_field($_POST['product-id']) : '';
			$content 	= isset($_POST['premium-ticket']) ? esc_html($_POST['premium-ticket']) : '';
			$date = date('Y-m-d H:i:s');

			/*User info*/
			$user = $this->user_data();

			global $wpdb;
			$wpdb->insert(
				$wpdb->prefix . 'wss_support_tickets',
				array(
					'title'		  => $title,
					'user_id' 	  => $user['id'],
					'user_name'   => $user['name'],
					'user_email'  => $user['email'],
					'product_id'  => $product_id,
					'status'	  => 1,
					'create_time' => $date,
					'update_time' => $date
				),
				array(
					'%s',
					'%d',
					'%s',
					'%s',
					'%d',
					'%d',
					'%s',
					'%s',
				)
			);

			$ticket_id = $wpdb->insert_id;

			$this->save_new_premium_ticket_thread($ticket_id, $content, $date, $user['id'], $user['name'], $user['email']);
		}
	}


	/**
	 * Add all plugin admin pages and menu items	
	 */
	public function register_wss_admin() {

		$unread_tickets = $this->get_awaiting_tickets();
		$bouble_count = '<span class="update-plugins count-' . $unread_tickets . '" title="' . $unread_tickets . '""><span class="update-count">' . $unread_tickets . '</span></span>';
	    
	    $menu_label = sprintf('WC Support %s', $bouble_count);

	    /*Main menu item*/
	    $hook = add_menu_page( 'WC Support', $menu_label, 'manage_options', 'wc-support-system', array($this, 'wss_admin'), 'dashicons-tickets-alt', 59);
	    
	    /*Tickets*/
	    add_submenu_page( 'wc-support-system', 'Tickets', 'Tickets', 'manage_options', 'wc-support-system');

		add_action( 'load-' . $hook, array($this, 'screen_options'));
	    
	    /*Options*/
	    add_submenu_page( 'wc-support-system', __('Settings', 'wss'), __('Settings', 'wss'), 'manage_options', 'settings', array($this, 'wss_settings'));

	}


	public function set_screen($status, $option, $value) {
		return $value;
	}


	/**
	* Screen options
	*/
	public function screen_options() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Tickets',
			'default' => 5,
			'option'  => 'tickets_per_page'
		];

		add_screen_option( $option, $args );

		$this->tickets_obj = new wss_table();
	}



	/**
	 * Pagina di amministrazione Premium Support
	 */
	public function wss_admin() {
	    ?>
		<div class="wrap">
			<h1>Woocommerce Support System</h1>

			<!-- <div id="poststuff"> -->
				<!-- <div id="post-body" class="metabox-holder columns-2"> -->
					<!-- <div id="post-body-content"> -->
						<!-- <div class="meta-box-sortables ui-sortable"> -->
							<!-- <form method="post"> -->
								<?php
								// $test = new wss_table();
								$this->tickets_obj->prepare_items();
								$this->tickets_obj->display(); 
								?>
							<!-- </form> -->
						<!-- </div> -->
					<!-- </div> -->
				<!-- </div> -->
				<!-- <br class="clear"> -->
			<!-- </div> -->
		</div>
	    <?php
	}


	/**
	 * Cancella dal db il thread dato
	 * @param  int $thread_id l'id del thread da cancellare
	 */
	public function delete_single_thread($thread_id) {
		global $wpdb;
		$wpdb->delete(
			$wpdb->prefix . 'wss_support_threads',
			array(
				'id' => $thread_id
			),
			array(
				'%d'
			)
		);
	} 


	/**
	 * Ajax - attiva la cancellazione di un singolo thread
	 */
	public function ajax_delete_single_thread() {
		$admin_page = get_current_screen();
		if($admin_page->base == 'toplevel_page_wc-support-system') {
			?>
			<script>
				jQuery(document).ready(function($){
					delete_single_thread();
				})
			</script>
			<?php
		}
	}


	/**
	 * Callback - chiama la funzione di eliminazione del thread dal db
	 * @return [type] [description]
	 */
	public function delete_single_thread_callback() {
		$thread_id = isset($_POST['thread_id']) ? $_POST['thread_id'] : $thread_id;
		if($thread_id) {
			$this->delete_single_thread($thread_id);
		}
		exit;
	}


	/**
	 * Ajax - attiva la cancellazione di un singolo ticket
	 */
	public function ajax_delete_single_ticket() {
		$admin_page = get_current_screen();
		if($admin_page->base == 'toplevel_page_wc-support-system') {
			?>
			<script>
				jQuery(document).ready(function($){
					delete_single_ticket();
				})
			</script>
			<?php
		}
	}


	/**
	 * Cancella dal db il singolo ticket e i relativi threads
	 */
	public function delete_single_ticket_callback() {
		$ticket_id = isset($_POST['ticket_id']) ? $_POST['ticket_id'] : '';
		if($ticket_id) {

			global $wpdb;

			/*Elimino tutti i threads del ticket*/
			$threads = $this->get_ticket_threads($ticket_id);
			foreach ($threads as $thread) {
				$this->delete_single_thread($thread->id);
			}

			/*Elimino il ticket*/
			$wpdb->delete(
				$wpdb->prefix . 'wss_support_tickets',
				array(
					'id' => $ticket_id
				),
				array(
					'%d'
				)
			);
		}
		exit;
	}


	/**
	 * WSS Options page
	 */
	public function wss_settings() {

		/*Get the options*/
		$reopen_ticket = get_option('wss-reopen-ticket');

	    echo '<div class="wrap">';
		    echo '<h1>Woocommerce Support System - ' . __('Settings', 'wss') . '</h1>';
		    echo '<form name="wss-options" class="wss-options" method="post" action="">';
		    	echo '<table class="form-table">';
		    		echo '<tr>';
		    			echo '<th scope="row">' . __('Reopen ticket', 'wss') . '</th>';
		    			echo '<td>';
			    			echo '<label for="reopen-ticket">';
		    				echo '<input type="checkbox" name="reopen-ticket" value="1"' . ($reopen_ticket == 1 ? ' checked="checked"' : '') . '>';
			    			echo __('After sending a new thread, the admin can choose to left the specific ticket open and see all the threads in there.');
			    			echo '</label>'; 
		    			echo '</td>';
		    		echo '</tr>';
		    	echo '</table>';
		    	echo '<input type="hidden" name="wss-options-hidden" value="1">';
		    	echo '<input type="submit" class="button button-primary" value="Save">';
		    echo '</form>';
		echo '</div>';
	}


	public function wss_save_settings() {
		if(isset($_POST['wss-options-hidden'])) {

			/*Reopen ticket*/
			$reopen_ticket = isset($_POST['reopen-ticket']) ? $_POST['reopen-ticket'] : 0;
			update_option('wss-reopen-ticket', $reopen_ticket);
		}
	}


}
new wc_support_system();