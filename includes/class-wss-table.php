<?php
/**
 * WSS table
 */


/*Required*/
if (!class_exists('WP_List_Table')) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class wss_table extends WP_List_Table {

	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Ticket', 'wss' ), //singular name of the listed records
			'plural'   => __( 'Tickets', 'wss' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?

		] );

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
	 * Restituisce tutti i ticket presenti nel db
	 * @return array
	 */
	public static function get_tickets($per_page = 5, $page_number = 1) {
		global $wpdb;
		$query = "
			SELECT * FROM " . $wpdb->prefix . "wss_support_tickets ORDER BY status ASC LIMIT $per_page OFFSET " . ($page_number - 1) * $per_page 
		;
		$tickets = $wpdb->get_results($query, 'ARRAY_A');

		return $tickets;
	}


	/**
	 * Returns the count of tickets in the database.
	 */
	public static function record_count() {
		global $wpdb;

		$sql = "
			SELECT COUNT(*) FROM " . $wpdb->prefix ."wss_support_tickets
		";

		return $wpdb->get_var($sql);
	}


	/** 
	 * Text displayed when no customer data is available
	 * @return string
	 */
	public function no_items() {
		echo __( 'It seems like therea are no support tickets opened at the moment.', 'wss' );
	}


	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
		case 'product_id':
			return get_the_post_thumbnail($item['product_id'], array(40,40));
			break;
		case 'status':
			return $this->get_ticket_status_label($item['status']);
			break;
		//   return $item[ $column_name ];
		default:
		  return $item[$column_name];
		  // return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 */
	function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
		);
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb' => '<input type="checkbox" />',
			'id' => __('ID', 'wss'),
			'title' => __('Title', 'wss'),
			'user_id' => __('User id', 'wss'),
			'user_name' => __('User name', 'wss'),
			'user_email' => __('User email', 'wss'),
			'product_id' => __('Product', 'wss'),
			'status' => __('Status', 'wss'),
			'create_time' => __('Create time', 'wss'),
			'update_time' => __('Update time', 'wss')
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'id' => array('id', true),
			'title' => array('title', true),
			'user_id' => array('user_id', true),
			'user_name' => array('user_name', true),
			'user_email' => array('user_email', true),
			'product_id' => array('product_id', true),
			'status' => array('status', true),
			'create_time' => array('create_time', true),
			'update_time' => array('update_time', true)
		);

		return $sortable_columns;
	}


	/**
	* Handles data query and filter, sorting, and pagination.
	*/
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		// $this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'tickets_per_page', 5 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = self::get_tickets( $per_page, $current_page );

	}

}