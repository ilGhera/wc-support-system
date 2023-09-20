<?php
/**
 * Main plugin class
 *
 * @author ilGhera
 * @package wc-support-system-premium/includes
 *
 * @since 1.2.1
 */
class wc_support_system {

	public $tickets_obj;
	public $support_page;
	public $support_page_url;

	public function __construct() {

		add_action( 'admin_init', array( $this, 'wss_save_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_wss_admin' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'wss_admin_scripts' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'wss_scripts' ) );

		add_action( 'admin_footer', array( $this, 'ajax_admin_get_ticket_content' ) );
		add_action( 'admin_footer', array( $this, 'ajax_delete_single_ticket' ) );
		add_action( 'admin_footer', array( $this, 'ajax_delete_single_thread' ) );
		add_action( 'admin_footer', array( $this, 'modal_change_ticket_status' ) );

		add_action( 'init', array( $this, 'save_new_ticket' ) );
		add_action( 'init', array( $this, 'save_new_thread' ) );
		add_action( 'init', array( $this, 'wss_avoid_resend' ) );

		add_action( 'wp_ajax_delete-ticket', array( $this, 'delete_single_ticket_callback' ) );
		add_action( 'wp_ajax_delete-thread', array( $this, 'delete_single_thread_callback' ) );
		add_action( 'wp_ajax_change-ticket-status', array( $this, 'change_ticket_status_callback' ) );
		add_action( 'wp_ajax_get_ticket_content', array( $this, 'get_ticket_content_callback' ) );
		add_action( 'wp_ajax_nopriv_get_ticket_content', array( $this, 'get_ticket_content_callback' ) );
		add_action( 'wp_ajax_product-select-warning', array( $this, 'product_select_warning_callback' ) );
		add_action( 'wp_ajax_nopriv_product-select-warning', array( $this, 'product_select_warning_callback' ) );

		add_action( 'wp_footer', array( $this, 'ajax_get_ticket_content' ) );

		add_shortcode( 'support-tickets-table', array( $this, 'support_tickets_table' ) );
		add_filter( 'the_content', array( $this, 'page_class_instance' ), 999 );

		add_filter( 'set-screen-option', array( $this, 'set_screen' ), 10, 3 );
	}


	/**
	 * Add the tickets table to the support page
	 *
	 * @param  string $content the content of the page
	 * @return mixed           the filtered content
	 */
	public function page_class_instance( $content ) {

		$support_page = get_option( 'wss-page' );

		if ( $support_page && is_page( $support_page ) ) {

			/*Get the tickets table*/
			ob_start();
			do_shortcode( '[support-tickets-table]' );
			$tickets_table = ob_get_contents();
			ob_end_clean();

			/*Get the table position in the page*/
			$page_layout = get_option( 'wss-page-layout' );

			$output = $page_layout == 'after' ? $content . $tickets_table : $tickets_table . $content;
			return $output;

		} else {
			return $content;
		}

	}


	/**
	 * Back-end scripts and style
	 */
	public function wss_admin_scripts() {
		$admin_page = get_current_screen();

		if ( in_array( $admin_page->base, array( 'toplevel_page_wc-support-system', 'wc-support_page_wss-settings' ) ) ) {

			if ( 'wc-support_page_wss-settings' === $admin_page->base ) {

				wp_enqueue_style( 'tzcheckbox-style', plugin_dir_url( __DIR__ ) . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.css' );
				wp_enqueue_script( 'tzcheckbox', plugin_dir_url( __DIR__ ) . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.js', array( 'jquery' ) );
				wp_enqueue_script( 'tzcheckbox-script', plugin_dir_url( __DIR__ ) . 'js/tzCheckbox/js/script.js', array( 'jquery' ) );

			}

			/*js*/
			wp_enqueue_script( 'bootstrap-js', plugin_dir_url( __DIR__ ) . 'js/bootstrap.min.js' );
			wp_enqueue_script( 'wss-script', plugin_dir_url( __DIR__ ) . 'js/wss.js', array( 'jquery' ) );
			wp_enqueue_script( 'wp-color-picker', array( 'jquery' ), '', true );
			wp_enqueue_script( 'tagify-script', plugin_dir_url( __DIR__ ) . 'js/tagify/dist/jQuery.tagify.min.js', array( 'jquery' ) );
			wp_enqueue_script( 'chosen-script', plugin_dir_url( __DIR__ ) . '/vendor/harvesthq/chosen/chosen.jquery.min.js', array( 'jquery' ) );

			/* Pass user email to the script to be excluded from the additional recipients field */
			$user = wp_get_current_user();

			wp_localize_script( 'wss-script', 'data', array( 'userEmail' => $user->user_email ) );

			/*css*/
			wp_enqueue_style( 'bootstrap-iso', plugin_dir_url( __DIR__ ) . 'css/bootstrap-iso.css' );
			wp_enqueue_style( 'wss-admin-style', plugin_dir_url( __DIR__ ) . 'css/wss-admin-style.css' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'tagify-style', plugin_dir_url( __DIR__ ) . 'js/tagify/dist/tagify.css' );
			wp_enqueue_style( 'chosen-style', plugin_dir_url( __DIR__ ) . '/vendor/harvesthq/chosen/chosen.min.css' );
		}
	}


	/**
	 * Front-end scripts and style
	 */
	public function wss_scripts() {
		if ( is_page( $this->support_page ) ) {

			/*js*/
			wp_enqueue_script( 'wss-script', plugin_dir_url( __DIR__ ) . 'js/wss.js', array( 'jquery' ) );
			wp_enqueue_script( 'tagify-script', plugin_dir_url( __DIR__ ) . 'js/tagify/dist/jQuery.tagify.min.js', array( 'jquery' ) );
			wp_enqueue_script( 'chosen-script', plugin_dir_url( __DIR__ ) . '/vendor/harvesthq/chosen/chosen.jquery.min.js', array( 'jquery' ) );

			/* Pass user email to the script to be excluded from the additional recipients field */
			$user_data = wp_get_current_user();
			wp_localize_script( 'wss-script', 'data', array( 'userEmail' => $user_data->user_email ) );

			/*css*/
			wp_enqueue_style( 'wss-tinymce-style', includes_url() . 'css/editor.min.css' );
			wp_enqueue_style( 'wss-dashicons-style', includes_url() . 'css/dashicons.min.css' );
			wp_enqueue_style( 'wss-style', plugin_dir_url( __DIR__ ) . 'css/wss-style.css' );
			wp_enqueue_style( 'bootstrap-iso', plugin_dir_url( __DIR__ ) . 'css/bootstrap-iso.css' );
			wp_enqueue_style( 'tagify-style', plugin_dir_url( __DIR__ ) . 'js/tagify/dist/tagify.css' );
			wp_enqueue_style( 'chosen-style', plugin_dir_url( __DIR__ ) . '/vendor/harvesthq/chosen/chosen.min.css' );
		}
	}


	/**
	 * Create the plugin db tables
	 */
	public static function wss_tables() {
		global $wpdb;
		$wss_tickets = $wpdb->prefix . 'wss_support_tickets';
		$wss_threads = $wpdb->prefix . 'wss_support_threads';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$wss_tickets'" ) != $wss_tickets ) {

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $wss_tickets (
				id 			bigint(20) NOT NULL AUTO_INCREMENT,
				title 		varchar(200) NOT NULL,
				user_id 	bigint(20) NOT NULL,
				user_name 	varchar(60) NOT NULL,
				user_email 	varchar(100) NOT NULL,
				product_id 	bigint(20) NOT NULL,
				status 		int(11) NOT NULL,
				notified 	int(11) NOT NULL,
				create_time datetime NOT NULL,
				update_time datetime NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			dbDelta( $sql );

		} elseif ( '1.1.0' > get_option( 'wss-db-version' ) ) {

			if ( '1.0.2' > get_option( 'wss-db-version' ) ) {

				$sql = "ALTER TABLE $wss_tickets ADD notified INT(11) NOT NULL DEFAULT 0";

				$wpdb->query( $sql );

			}

			$sql = "ALTER TABLE $wss_tickets ADD recipients longtext";

			$wpdb->query( $sql );

			update_option( 'wss-db-version', '1.1.0' );

		}

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$wss_threads'" ) != $wss_threads ) {

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $wss_threads (
				id 			bigint(20) NOT NULL AUTO_INCREMENT,
				ticket_id 	bigint(20),
				content 	text NOT NULL,
				create_time datetime NOT NULL,
				user_id 	bigint(20) NOT NULL,
				user_name 	varchar(60) NOT NULL,
				user_email  varchar(100) NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			dbDelta( $sql );

		}

	}


	/**
	 * Create the Support page
	 *
	 * @param string $page_titel the page title indicateded by the admin
	 * @return int the page id
	 */
	public function add_support_page( $page_title ) {
		$output = wp_insert_post(
			array(
				'post_title'   => $page_title,
				'post_name'    => sanitize_title( $page_title ),
				'post_content' => '',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		return $output;
	}


	/**
	 * Get the user data
	 *
	 * @return array
	 */
	public function user_data() {
		$output = array();

		$userdata = get_userdata( get_current_user_id() );

		$output['id']    = $userdata->ID;
		$output['name']  = $userdata->display_name;
		$output['email'] = $userdata->user_email;
		$output['admin'] = false;
		$roles           = $userdata->roles;

		if ( in_array( 'administrator', $roles ) ) {
			$output['admin'] = true;
		}

		$output['order_id'] = null;

		return $output;
	}


	/**
	 * Get the products bought by the specific customer
	 *
	 * @param  string $order_id   the order id
	 * @param  string $user_email the user email
	 * @return array              the products ids
	 */
	public function get_user_products( $order_id = '', $user_email = '' ) {

		$output = null;

		/*Logged in user*/
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$args         = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			);

			$loop   = new WP_Query( $args );
			$output = null;

			while ( $loop->have_posts() ) :
				$loop->the_post();

				$bought = wc_customer_bought_product( $current_user->user_email, $current_user->ID, get_the_ID() );

				if ( $bought ) {
					$output[] = get_the_ID();
				}

			endwhile;
			wp_reset_query();

		} elseif ( $order_id && $user_email ) {

			/*User not logged in*/
			if ( get_post_meta( $order_id, '_billing_email', true ) == $user_email ) {
				$order = new WC_Order( $order_id );
				$items = $order->get_items();
				foreach ( $items as $item ) {
					$item_data = $item->get_data();
					$output[]  = $item_data['product_id'];
				}
			}
		}

		return $output;

	}


	/**
	 * Check if the logged in user can access to the support service, based on his purchases
	 *
	 * @return bool
	 */
	public function logged_in_user_support_access_validation() {
		$validation = false;

		if ( is_user_logged_in() ) {
			$userdata = $this->user_data( get_current_user_id() );
			if ( $this->get_user_products() ) {
				$validation = true;
			}
		}

		return $validation;
	}


	/**
	 * Get the tickets that requires an answer (open)
	 *
	 * @return int
	 */
	public function get_awaiting_tickets() {
		global $wpdb;
		$query   = '
			SELECT * FROM ' . $wpdb->prefix . 'wss_support_tickets WHERE status = 1
		';
		$tickets = $wpdb->get_results( $query );

		return count( $tickets );
	}


	/**
	 * Get the user's tickets
	 *
	 * @param  string $user_email the user email
	 * @return array
	 */
	public function get_user_tickets( $user_email ) {
		global $wpdb;
		$query   = '
			SELECT * FROM ' . $wpdb->prefix . "wss_support_tickets WHERE user_email = '$user_email' ORDER BY create_time DESC
		";
		$tickets = $wpdb->get_results( $query );

		return $tickets;
	}


	/**
	 * Opening a ticket, this alert is shown if a product is not selected
	 *
	 * @return mixed
	 */
	public function product_select_warning_callback() {
		echo '<div class="alert alert-warning">' . __( 'Please, choose a product for your support request.', 'wc-support-system' ) . '</div>';
		exit;
	}


	/**
	 * New ticket form
	 *
	 * @param  int    $order_id   the order id if the user is not logged in
	 * @param  string $user_email the user email
	 */
	public function create_new_ticket( $order_id, $user_email ) {

		?>
		<div class="wss-ticket-container" style="display: none;">
			<form method="POST" class="create-new-ticket" action="">
				<select name="product-id" class="product-id" style="margin: 1rem 0;">
					<?php
					$products = $this->get_user_products( $order_id, $user_email );
					if ( $products ) {
						echo '<option value="null">' . __( 'Select a product', 'wc-support-system' ) . '</option>';
						foreach ( $products as $product ) {
							if ( $product ) {
								echo '<option value="' . $product . '">' . get_the_title( $product ) . '</option>';
							}
						}
					}
					?>
				</select>
				<input type="text" name="additional-recipients" class="additional-recipients" data-blacklist="<?php echo esc_attr( $user_email ); ?>" placeholder="<?php echo __( 'Send notifications to other email addresses', 'wc-support-system' ); ?>">
				<input type="text" name="title" placeholder="<?php echo __( 'Ticket subject', 'wc-support-system' ); ?>" required="required">
				<?php wp_editor( '', 'wss-ticket' ); ?>
				<input type="hidden" name="ticket-sent" value="1">
				<input type="submit" class="send-new-ticket" value="<?php echo __( 'Send', 'wc-support-system' ); ?>" style="margin-top: 1rem;">
			</form>
			<div class="bootstrap-iso product-alert"></div>
		</div>
		<a class="button new-ticket"><?php echo __( 'New ticket', 'wc-support-system' ); ?></a>
		<a class="button ticket-cancel" style="display: none;"><?php echo __( 'Cancel', 'wc-support-system' ); ?></a>
		<?php
	}


	/**
	 * New thread form
	 *
	 * @param bool $is_admin admin area with true
	 *
	 * @return void
	 */
	public function create_new_thread( $is_admin = false ) {
		?>
		<div class="wss-thread-container" style="display: none;">
			<form method="POST" action="">
				<?php wp_editor( '', 'wss-thread' ); ?>
				<input type="hidden" class="ticket-id" name="ticket-id" value="">
				<input type="hidden" class="customer-email" name="customer-email" value="">
				<input type="hidden" name="thread-sent" value="1">
				<input type="hidden" class="close-ticket" name="close-ticket" value="0">
				<input type="submit" class="send-new-thread button-primary" value="<?php esc_attr_e( 'Send', 'wc-support-system' ); ?>" style="margin-top: 1rem;">
				<?php
				if ( $is_admin ) {
					echo '<input type="submit" class="send-new-thread-and-close button green" value="' . esc_attr__( 'Send and Close', 'wc-support-system' ) . '" style="margin-top: 1rem;">';
				}
				?>
			</form>
			<div class="bootstrap-iso"></div>
		</div>
		<div class="thread-tools">
			<a class="button back-to-tickets"><?php echo __( 'Back to tickets', 'wc-support-system' ); ?></a>
			<a class="button new-thread button-primary" style="display: none;"><?php echo __( 'New message', 'wc-support-system' ); ?></a>
			<a class="button thread-cancel" style="display: none;"><?php echo __( 'Cancel', 'wc-support-system' ); ?></a>
		</div>
		<?php
	}


	/**
	 * Get the ticket status
	 *
	 * @param  int    $ticket_id the ticket id
	 * @param  string $col       the column to retrieve from the row
	 * @return object the ticket data
	 */
	public static function get_ticket( $ticket_id, $col = '' ) {
		global $wpdb;

		$output = null;

		$data    = $col ? $col : '*';
		$query   = "
			SELECT $data FROM " . $wpdb->prefix . "wss_support_tickets WHERE id = '$ticket_id'
		";
		$results = $wpdb->get_results( $query );

		if ( isset( $results[0] ) ) {
			$output = $col ? $results[0]->$col : $results[0];
		}
		return $output;
	}


	/**
	 * Get the status label of the ticket from the specific status id
	 *
	 * @param  int $status_id th estatus id
	 * @return string
	 */
	public static function get_ticket_status_label( $status_id ) {
		$output = null;
		switch ( $status_id ) {
			case 1:
				$output = '<span class="label label-danger toggle" data-toggle="modal" data-status="' . $status_id . '" data-target="#ticket-status-modal">' . __( 'Open', 'wc-support-system' ) . '</span>';
				break;
			case 2:
				$output = '<span class="label label-warning toggle" data-toggle="modal" data-status="' . $status_id . '" data-target="#ticket-status-modal">' . __( 'Pending', 'wc-support-system' ) . '</span>';
				break;
			case 3:
				$output = '<span class="label label-success toggle" data-toggle="modal" data-status="' . $status_id . '" data-target="#ticket-status-modal">' . __( 'Closed', 'wc-support-system' ) . '</span>';
				break;
		}

		return $output != null ? '<div class="bootstrap-iso">' . $output . '</div>' : $output;
	}


	/**
	 * Get the ticket threads
	 *
	 * @param  int $ticket_id the ticket id
	 * @return array
	 */
	public static function get_ticket_threads( $ticket_id ) {
		global $wpdb;
		$query   = '
			SELECT * FROM ' . $wpdb->prefix . "wss_support_threads WHERE ticket_id = '$ticket_id' ORDER BY id DESC
		";
		$results = $wpdb->get_results( $query );

		return $results;
	}


	/**
	 * Ajax - Expand a single ticket in back-end
	 * Back-end
	 */
	public function ajax_admin_get_ticket_content() {
		$admin_page = get_current_screen();
		if ( $admin_page->base == 'toplevel_page_wc-support-system' ) {
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
	 * Ajax - Expand a single ticket in front-end
	 */
	public function ajax_get_ticket_content() {
		if ( is_page( $this->support_page ) ) {
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
	 * Callback - show all ticket threads to the user
	 *
	 * @return mixed
	 */
	public function get_ticket_content_callback() {

		$ticket_id = $_POST['ticket_id'];

		global $wpdb;
		$query   = '
			SELECT * FROM ' . $wpdb->prefix . "wss_support_tickets WHERE id = '$ticket_id'
		";
		$results = $wpdb->get_results( $query );

		if ( $results ) {
			$ticket = $results[0];

			echo '<div id="wss-ticket" class="ticket-' . $ticket_id . '">';

				/* Display additional recipients field only in back-end */
			if ( is_super_admin() ) {

				echo '<form>';
					echo '<label for="additional-recipients">' . esc_html__( 'Additional recipients', 'wss' ) . '</label>';
					$this->go_premium( true );
					echo '<p class="description">' . esc_html__( 'These email addresses will receive notifications about this ticket updates.', 'wss' ) . '</p>';
					echo '<input type="text" name="additional-recipients-' . $ticket_id . '" class="additional-recipients additional-recipients-' . $ticket_id . '" data-blacklist="' . esc_attr( $ticket->user_email ) . '" placeholder="' . __( 'Add one or more email addresses', 'wss' ) . '">';
				echo '</form>';

			}

				$threads = self::get_ticket_threads( $ticket_id );
			if ( $threads ) {
				foreach ( $threads as $thread ) {

					/*Colors*/
					if ( user_can( $thread->user_id, 'administrator' ) ) {
						$background_color = get_option( 'wss-admin-color-background' ) ? get_option( 'wss-admin-color-background' ) : '';
						$text_color       = get_option( 'wss-admin-color-text' ) ? get_option( 'wss-admin-color-text' ) : '';
					} else {
						$background_color = get_option( 'wss-user-color-background' ) ? get_option( 'wss-user-color-background' ) : '';
						$text_color       = get_option( 'wss-user-color-text' ) ? get_option( 'wss-user-color-text' ) : '';
					}

					echo '<div class="single-thread thread-' . $thread->id . ( user_can( $thread->user_id, 'administrator' ) ? ' answer' : '' ) . '"' . ( $background_color ? 'style="border: 1px solid ' . $background_color . '"' : '' ) . '>';
						echo '<div class="thread-header"' . ( $background_color ? 'style="background: ' . $background_color . '"' : '' ) . '>';
							echo '<div class="left">' . get_avatar( $thread->user_id, 50 ) . '</div>';
							echo '<div class="right"' . ( $text_color ? ' style="color: ' . $text_color . '"' : '' ) . '>' . $thread->user_name . '<br><span class="date">' . date( 'd-m-Y H:i:s', strtotime( $thread->create_time ) ) . '</span></div>';
							echo '<div class="clear"></div>';
							echo '<img class="delete-thread" data-thread-id="' . $thread->id . '" src="' . plugin_dir_url( __DIR__ ) . '/images/dustbin.png">';
						echo '</div>';
						echo '<div class="thread-content">' . nl2br( wp_kses_post( wp_unslash( $thread->content ) ) ) . '</div>';
					echo '</div>';
				}
			}

			echo '</div>';
		}
		exit;
	}


	/**
	 * User tickets table
	 */
	public function support_tickets_table() {

		/*The user has access to the support service*/
		if ( $this->logged_in_user_support_access_validation() ) :
			$userdata   = $this->user_data();
			$user_id    = $userdata['id'];
			$user_email = $userdata['email'];
			$order_id   = $userdata['order_id'];

			echo '<div id="support-tickets-container">';

				$tickets = $this->get_user_tickets( $user_email );

			if ( $tickets ) {
				?>
					<table class="table support-tickets-table">
						<thead>
							<th class="id" style="padding: 0.5em 1.5rem;"><?php echo __( 'ID', 'wc-support-system' ); ?></th>
							<th class="subject"><?php echo __( 'Subject', 'wc-support-system' ); ?></th>
							<th class="create-time"><?php echo __( 'Creation time', 'wc-support-system' ); ?></th>
							<th class="update-time"><?php echo __( 'Update time', 'wc-support-system' ); ?></th>
							<th><?php echo __( 'Product', 'wc-support-system' ); ?></th>
							<th><?php echo __( 'Status', 'wc-support-system' ); ?></th>
						</thead>
						<tbody>
					<?php
					foreach ( $tickets as $ticket ) {
						echo '<tr class="ticket-' . $ticket->id . '">';
							echo '<td class="id">#' . $ticket->id . '</td>';
							echo '<td class="subject ticket-toggle" data-ticket-id="' . $ticket->id . '">' . stripcslashes( $ticket->title ) . '</td>';
							echo '<td class="create-time">' . ( $ticket->create_time ? date( 'd-m-Y H:i:s', strtotime( $ticket->create_time ) ) : '' ) . '</td>';
							echo '<td class="update-time">' . ( $ticket->update_time ? date( 'd-m-Y H:i:s', strtotime( $ticket->update_time ) ) : '' ) . '</td>';

							/*Product image*/
							$thumbnail = get_the_post_thumbnail( $ticket->product_id, array( 50, 50 ) );
						if ( $thumbnail ) {
							$image = $thumbnail;
						} else {
							$image = '<img src="' . home_url() . '/wp-content/plugins/woocommerce/assets/images/placeholder.png">';
						}

							echo '<td class="product">' . $image . '</td>';
							echo '<td class="status" data-status-id="' . $ticket->status . '">' . self::get_ticket_status_label( $ticket->status ) . '</td>';
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
					echo '<div class="alert alert-info">' . __( 'It seems like you have no support tickets opened at the moment.', 'wc-support-system' ) . '</div>';
				echo '</div>';
			}
				$this->create_new_ticket( $order_id, $user_email );

				/*Logged in user but not a customer*/
				elseif ( is_user_logged_in() ) :
					echo '<div class="bootstrap-iso">';
						echo '<div class="alert alert-danger">' . __( 'It seems like you haven\'t bought any productat the moment.', 'wc-support-system' ) . '</div>';
					echo '</div>';
				else :
					echo '<div class="bootstrap-iso">';
						echo '<div class="alert alert-danger">' . __( 'You must be logged in to access support service.', 'wss' ) . '</div>';
					echo '</div>';
				endif;

				echo '</div>';
	}


	/**
	 * Ajax - Modal window with ticket change status functionality
	 */
	public function modal_change_ticket_status() {
		$admin_page = get_current_screen();
		if ( $admin_page->base == 'toplevel_page_wc-support-system' ) {
			?>
			<div class="bootstrap-iso">
				<div id="ticket-status-modal" class="modal fade" data-ticket-id="" role="dialog">
					<div class="modal-dialog">
						<!-- Modal content-->
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal">&times;</button>
								<h4 class="modal-title"><?php echo __( 'Change the ticket status', 'wc-support-system' ); ?><span></span></h4>
							</div>
							<div class="modal-body">
								<div class="row status-selector">
									<div class="col-xs-4 status status-1" data-status="1"><?php echo self::get_ticket_status_label( 1 ); ?></div>
									<div class="col-xs-4 status status-2" data-status="2"><?php echo self::get_ticket_status_label( 2 ); ?></div>
									<div class="col-xs-4 status status-3" data-status="3"><?php echo self::get_ticket_status_label( 3 ); ?></div>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __( 'Close', 'wc-support-system' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<script>
				jQuery(document).ready(function($){
					modal_change_ticket_status();
				})
			</script>
			<?php
		}
	}


	/**
	 * Update the ticket after a new thread
	 *
	 * @param  int    $ticket_id the ticket id to update
	 * @param  string $date      the new thread date will be the modified date of the ticket
	 */
	public function update_ticket( $ticket_id, $date = '', $status = 1 ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'wss_support_tickets',
			array(
				'status'      => $status,
				'update_time' => $date,
			),
			array(
				'id' => $ticket_id,
			),
			array(
				'%d',
				'%s',
			)
		);
	}


	/**
	 * Callback - Update the ticket status in the db after the admin action
	 */
	public function change_ticket_status_callback() {
		$ticket_id   = isset( $_POST['ticket_id'] ) ? $_POST['ticket_id'] : '';
		$update_time = isset( $_POST['update_time'] ) ? $_POST['update_time'] : '';
		$new_status  = isset( $_POST['new_status'] ) ? $_POST['new_status'] : '';

		if ( $ticket_id && $new_status ) {
			$this->update_ticket( $ticket_id, $update_time, $new_status );
			$new_label = self::get_ticket_status_label( $new_status );
			echo $new_label;
		}
		exit;
	}


	/**
	 * Register to the db that the notification was sent
	 *
	 * @param  int $ticket_id the ticket id
	 */
	public function notification_sent( $ticket_id ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'wss_support_tickets',
			array(
				'notified' => 1,
			),
			array(
				'id' => $ticket_id,
			),
			array(
				'%d',
			)
		);
	}


	/**
	 * New thread notification used both to user and admin
	 *
	 * @param  int    $ticket_id    ticket id of the new thread
	 * @param  string $content      thread content
	 * @param  string $user_name    if not specified, the support email name is used
	 * @param  string $to           if not specified, the support email is used
	 * @param  bool   $notification is it a notification before closing the ticket?
	 */
	public function support_notification( $ticket_id, $content, $user_name = '', $to = '', $notification = false ) {

		if ( ! $ticket_id ) {
			return;
		}

		$support_email        = get_option( 'wss-support-email' );
		$support_email_name   = get_option( 'wss-support-email-name' );
		$support_email_footer = get_option( 'wss-support-email-footer' );

		if ( $user_name == '' ) {
			$user_name = $support_email_name;
		}

		if ( $to == '' ) {
			$to = $support_email;
		}

		$subject   = sprintf( __( '%1$s - Update ticket #%2$d' ), $user_name, $ticket_id );
		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		$headers[] = 'From: ' . $support_email_name . ' <' . $support_email . '>';
		$message   = '<style>img {display: block; margin: 1rem 0; max-width: 700px; height: auto;}</style>';
		$message  .= nl2br( wp_kses( wp_unslash( $content ), 'post' ) );

		if ( $support_email_footer ) {
			$message     .= '<p style="display: block; margin-top: 1.5rem; font-size: 12px; color: #666;">';
				$message .= wp_unslash( $support_email_footer );
			$message     .= '</p>';
		}

		wp_mail( $to, $subject, $message, $headers );

		if ( $notification ) {
			$this->notification_sent( $ticket_id );
		}
	}


	/**
	 * Insert the new thread into the db
	 *
	 * @param  int    $ticket_id  the ticket id
	 * @param  string $content    the ticket content
	 * @param  date   $date
	 * @param  int    $user_id
	 * @param  string $user_name
	 * @param  string $user_email
	 * @param  string $recipients the customer email(s)
	 * @param  int    $status     the ticket status after the update
	 */
	public function save_new_ticket_thread( $ticket_id, $content, $date, $user_id, $user_name, $user_email, $recipients = null, $status = 1 ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'wss_support_threads',
			array(
				'ticket_id'   => $ticket_id,
				'content'     => $content,
				'create_time' => $date,
				'user_id'     => $user_id,
				'user_name'   => $user_name,
				'user_email'  => $user_email,
			),
			array(
				'%d',
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
			)
		);

		$this->update_ticket( $ticket_id, $date, $status );

		/*Notifications settings*/
		$user_notification  = get_option( 'wss-user-notification' );
		$admin_notification = get_option( 'wss-admin-notification' );

		if ( user_can( $user_id, 'administrator' ) ) {
			if ( $user_notification ) {
				$this->support_notification( $ticket_id, $content, null, $recipients );
			}
		} else {
			if ( $admin_notification ) {
				$this->support_notification( $ticket_id, $content, $user_name );
			}

			/* Send even user ticket update to the other recipients */
			if ( $user_notification && $recipients ) {
				$this->support_notification( $ticket_id, $content, $user_name, $recipients );
			}
		}
		add_action( 'wp_footer', array( $this, 'wss_avoid_resend_footer_script' ) );
	}


	/**
	 * Avoid resend script
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
	 * Avoid resend notifications on page reload
	 */
	public function wss_avoid_resend() {
		$this->support_page     = get_option( 'wss-page' );
		$this->support_page_url = get_the_permalink( $this->support_page );

		if ( isset( $_GET['sent'] ) ) {
			header( 'Location: ' . $this->support_page_url );
			exit;
		}
	}


	/**
	 * Add a new thread to a ticket
	 */
	public function save_new_thread() {

		if ( isset( $_POST['thread-sent'] ) ) {

			$ticket_id      = isset( $_POST['ticket-id'] ) ? sanitize_text_field( $_POST['ticket-id'] ) : '';
			$customer_email = isset( $_POST['customer-email'] ) ? sanitize_email( $_POST['customer-email'] ) : '';
			$close_ticket   = isset( $_POST['close-ticket'] ) ? sanitize_text_field( $_POST['close-ticket'] ) : '';
			$recipients     = null;

			if ( $recipients ) {

				/* Translator: 1: other recipients 2: customer email */
				$recipients = sprintf( '%1$s,%2$s', $recipients, $customer_email );

			} else {

				$recipients = $customer_email;

			}

			$content = isset( $_POST['wss-thread'] ) ? wp_filter_post_kses( $_POST['wss-thread'] ) : '';
			$date    = date( 'Y-m-d H:i:s' );

			/*User info*/
			$user = $this->user_data();

			$ticket_status = user_can( $user['id'], 'administrator' ) ? 2 : 1;

			/* Close the ticket if set by the admin */
			if ( $close_ticket ) {
				$ticket_status = 3;
			}

			$this->save_new_ticket_thread( $ticket_id, $content, $date, $user['id'], $user['name'], $user['email'], $recipients, $ticket_status );

		}
	}


	/**
	 * Insert a new ticket into the db
	 */
	public function save_new_ticket() {

		if ( isset( $_POST['ticket-sent'] ) ) {

			/*User info*/
			$user = $this->user_data();

			$title      = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
			$product_id = isset( $_POST['product-id'] ) ? sanitize_text_field( $_POST['product-id'] ) : '';
			$content    = isset( $_POST['wss-ticket'] ) ? wp_filter_post_kses( $_POST['wss-ticket'] ) : '';
			$recipients = null;
			$date       = date( 'Y-m-d H:i:s' );

			global $wpdb;
			$wpdb->insert(
				$wpdb->prefix . 'wss_support_tickets',
				array(
					'title'       => $title,
					'user_id'     => $user['id'],
					'user_name'   => $user['name'],
					'user_email'  => $user['email'],
					'product_id'  => $product_id,
					'status'      => 1,
					'create_time' => $date,
					'update_time' => $date,
					'recipients'  => $recipients,
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
					'%s',
				)
			);

			$ticket_id = $wpdb->insert_id;

			/* Save the new ticket thread */
			$this->save_new_ticket_thread( $ticket_id, $content, $date, $user['id'], $user['name'], $user['email'], $recipients );
		}
	}


	/**
	 * Add all plugin admin pages and menu items
	 */
	public function register_wss_admin() {

		$unread_tickets = $this->get_awaiting_tickets();
		$bouble_count   = '<span class="update-plugins count-' . $unread_tickets . '" title="' . $unread_tickets . '""><span class="update-count">' . $unread_tickets . '</span></span>';

		$menu_label = sprintf( 'WC Support %s', $bouble_count );

		/*Main menu item*/
		$hook = add_menu_page( 'WC Support', $menu_label, 'manage_options', 'wc-support-system', array( $this, 'wss_admin' ), 'dashicons-tickets-alt', 59 );

		/*Tickets*/
		add_submenu_page( 'wc-support-system', 'Tickets', 'Tickets', 'manage_options', 'wc-support-system' );

		add_action( 'load-' . $hook, array( $this, 'screen_options' ) );

		/*Options*/
		add_submenu_page( 'wc-support-system', __( 'Settings', 'wc-support-system' ), __( 'Settings', 'wc-support-system' ), 'manage_options', 'wss-settings', array( $this, 'wss_settings' ) );

	}


	/**
	 * Scren options used for posts per page
	 */
	public function screen_options() {

		$option = 'per_page';
		$args   = array(
			'label'   => 'Tickets',
			'default' => 20,
			'option'  => 'tickets_per_page',
		);

		add_screen_option( $option, $args );

		$this->tickets_obj = new wss_table();
	}


	/**
	 * Show the scren options
	 */
	public function set_screen( $status, $option, $value ) {
		return $value;
	}


	/**
	 * Woocommerce Suport System tickets table
	 */
	public function wss_admin() {
		?>
		<div class="wrap">
			<h1>Woocommerce Support System</h1>
			<form id="wss-support-tickets" name="wss-support-tickets" method="post">
				<?php
				$this->tickets_obj->prepare_items();
				$this->tickets_obj->search_box( __( 'Search', 'wc-support-system' ), 'wss-search' );
				$this->tickets_obj->display();
				$this->create_new_thread( true );
				?>
			</form>
			<div class="single-ticket-content"></div>
		</div>
		<?php
	}


	/**
	 * Delete the specific thread
	 *
	 * @param  int $thread_id
	 */
	public static function delete_single_thread( $thread_id ) {
		global $wpdb;
		$wpdb->delete(
			$wpdb->prefix . 'wss_support_threads',
			array(
				'id' => $thread_id,
			),
			array(
				'%d',
			)
		);
	}


	/**
	 * Ajax - fires the single thread delete
	 */
	public function ajax_delete_single_thread() {
		$admin_page = get_current_screen();
		if ( $admin_page->base == 'toplevel_page_wc-support-system' ) {
			$alert_message = __( 'Are you sure you want to delete this message?', 'wc-support-system' );
			?>
			<script>
				jQuery(document).ready(function($){
					var alert_message = '<?php echo $alert_message; ?>';
					delete_single_thread(alert_message);
				})
			</script>
			<?php
		}
	}


	/**
	 * Callback - call the delete_single_thread method
	 */
	public function delete_single_thread_callback() {
		$thread_id = isset( $_POST['thread_id'] ) ? $_POST['thread_id'] : $thread_id;
		if ( $thread_id ) {
			self::delete_single_thread( $thread_id );
		}
		exit;
	}


	/**
	 * Ajax - fires the single ticket delete
	 */
	public function ajax_delete_single_ticket() {
		$admin_page = get_current_screen();
		if ( $admin_page->base == 'toplevel_page_wc-support-system' ) {
			$alert_message = __( 'Are you sure you want to delete this ticket with all his messages?', 'wc-support-system' );
			?>
			<script>
				jQuery(document).ready(function($){
					var alert_message = '<?php echo $alert_message; ?>';
					delete_single_ticket(alert_message);
				})
			</script>
			<?php
		}
	}


	/**
	 * Delete the specific ticket and all his threads
	 */
	public static function delete_single_ticket( $ticket_id ) {

		global $wpdb;

		/*Elimino tutti i threads del ticket*/
		$threads = self::get_ticket_threads( $ticket_id );
		foreach ( $threads as $thread ) {
			self::delete_single_thread( $thread->id );
		}

		/*Elimino il ticket*/
		$wpdb->delete(
			$wpdb->prefix . 'wss_support_tickets',
			array(
				'id' => $ticket_id,
			),
			array(
				'%d',
			)
		);
	}


	/**
	 * Callback - call the delete_single_ticket method
	 */
	public function delete_single_ticket_callback() {
		$ticket_id = isset( $_POST['ticket_id'] ) ? $_POST['ticket_id'] : '';
		if ( $ticket_id ) {

			self::delete_single_ticket( $ticket_id );

		}
		exit;
	}


	/**
	 * Button premium call to action
	 *
	 * @param bool $inline inline with true.
	 *
	 * @return mixed
	 */
	public function go_premium( $inline = false ) {

		$class = $inline ? ' inline' : null;

		echo '<div class="bootstrap-iso' . esc_attr( $class ) . '">';
			echo '<span class="label label-warning premium"><a href="https://www.ilghera.com/product/woocommerce-support-system-premium" target="_blank">Premium</a></label>';
		echo '</div>';
	}


	/**
	 * WSS settings page
	 */
	public function wss_settings() {

		/*Get the options*/
		$premium_key            = get_option( 'wss-premium-key' );
		$support_page           = get_option( 'wss-page' );
		$page_layout            = get_option( 'wss-page-layout' );
		$admin_color_background = get_option( 'wss-admin-color-background' );
		$admin_color_text       = get_option( 'wss-admin-color-text' );
		$user_color_background  = get_option( 'wss-user-color-background' );
		$user_color_text        = get_option( 'wss-user-color-text' );
		$user_notification      = get_option( 'wss-user-notification' );
		$admin_notification     = get_option( 'wss-admin-notification' );
		$support_email          = get_option( 'wss-support-email' );
		$support_email_name     = get_option( 'wss-support-email-name' );
		$support_email_footer   = get_option( 'wss-support-email-footer' );

		echo '<div class="wrap">';
			echo '<div class="wrap-left">';
				echo '<h1>Woocommerce Support System - ' . __( 'Settings', 'wc-support-system' ) . '</h1>';
				echo '<form name="wss-options" class="wss-options" method="post" action="">';
					echo '<table class="form-table">';

						/*Choose the support page*/
						echo '<tr>';
							echo '<th scope="row">' . __( 'Support page', 'wc-support-system' ) . '</th>';
							echo '<td>';
								$pages = get_posts( 'post_type=page&posts_per_page=-1' );
								echo '<select id="support-page" class="wss-select" name="support-page">';
									echo '<option>-</option>';
		foreach ( $pages as $page ) {
			echo '<option name="' . $page->post_name . '" class="' . $page->post_name . '"';
			echo ' value="' . $page->ID . '"' . ( $support_page == $page->ID ? ' selected="selected"' : '' ) . '>';
			echo $page->post_title . '</option>';
		}
									echo '<option value="new">' . __( 'Create a new page', 'wc-support-system' ) . '</option>';
								echo '</select>';
								echo '<p class="description">' . __( 'Select a page for customer support or create a new one.', 'wc-support-system' ) . '</div>';
							echo '</td>';
						echo '</tr>';

						/*Create a new page*/
						echo '<tr class="create-support-page">';
							echo '<th scope="row">' . __( 'Page title', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<input type="text" name="support-page-title" value="">';
								echo '<p class="description">' . __( 'Chose a title for your support page.', 'wc-support-system' ) . '</p>';
							echo '</td>';
						echo '</tr>';

						/*Tickets table position in the page*/
						echo '<tr>';
							echo '<th scope="row">' . __( 'Page layout', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<select id="page-layout" class="wss-select" name="page-layout">';
									echo '<option name="after" value="after"' . ( $page_layout == 'after' ? ' selected="selected"' : '' ) . '>' . __( 'After', 'wc-support-system' ) . '</option>';
									echo '<option name="before" value="before"' . ( $page_layout == 'before' ? ' selected="selected"' : '' ) . '>' . __( 'Before', 'wc-support-system' ) . '</option>';
								echo '</select>';
								echo '<p class="description">' . __( 'Place the tickets table before or after the page content.', 'wc-support-system' ) . '</p>';
							echo '</td>';
						echo '</tr>';

						/*Admin threads color background*/
						echo '<tr>';
							echo '<th scope="row">' . __( 'Admin messages colors', 'wc-support-system' ) . '</th>';
							echo '<td>';
								/*Background*/
								echo '<input type="text" class="wss-color-field" name="admin-color-background" value="' . $admin_color_background . '">';
								echo '<p class="description">' . __( 'Select the background color for the admin\'s messages.', 'wc-support-system' ) . '</p>';
							echo '</td>';
						echo '</tr>';

						/*Admin threads color text*/
						echo '<tr>';
							echo '<th scope="row"></th>';
							echo '<td>';
								echo '<input type="text" class="wss-color-field" name="admin-color-text" value="' . $admin_color_text . '">';
								echo '<p class="description">' . __( 'Select the text color for the admin\'s messages.', 'wc-support-system' ) . '</p>';
							echo '</td>';
						echo '</tr>';

						/*User threads color background*/
						echo '<tr>';
							echo '<th scope="row">' . __( 'User messages colors', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<input type="text" class="wss-color-field" name="user-color-background" value="' . $user_color_background . '">';
								echo '<p class="description">' . __( 'Select the background color for the user\'s messages.', 'wc-support-system' ) . '</p>';
							echo '</td>';
						echo '</tr>';

						/*User threads color text*/
						echo '<tr>';
							echo '<th scope="row"></th>';
							echo '<td>';
								echo '<input type="text" class="wss-color-field" name="user-color-text" value="' . $user_color_text . '">';
								echo '<p class="description">' . __( 'Select the text color for the user\'s messages.', 'wc-support-system' ) . '</p>';
							echo '</td>';
						echo '</tr>';

						/*User email notification*/
						echo '<tr class="user-notification-field notifications-fields">';
							echo '<th scope="row">' . __( 'User email notification', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<label for="user-notification">';
									echo '<input type="checkbox" class="user-notification" name="user-notification" value="1"' . ( $user_notification == 1 ? ' checked="checked"' : '' ) . '>';
									echo __( 'Send an email notifications to the user when an answer was published.', 'wc-support-system' );
								echo '</label>';
							echo '</td>';
						echo '</tr>';

						/*Additional recipients*/
						echo '<tr class="wss-additional-recipients-field notifications-fields">';
							echo '<th scope="row">' . __( 'Additional recipients', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<label for="wss-additional-recipients">';
									echo '<input type="checkbox" class="wss-additional-recipients" name="wss-additional-recipients" value="0" disabled>';
									echo __( 'Allow the user to specify multiple email addresses for receiving notifications.', 'wc-support-system' );
								echo '</label>';
								$this->go_premium();
							echo '</td>';
						echo '</tr>';

						/*Admin email notification*/
						echo '<tr class="admin-notification-field notifications-fields">';
							echo '<th scope="row">' . __( 'Admin email notification', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<label for="admin-notification">';
									echo '<input type="checkbox" class="admin-notification" name="admin-notification" value="1"' . ( $admin_notification == 1 ? ' checked="checked"' : '' ) . '>';
									echo __( 'Send an email notifications to the admin when a new message is published.', 'wc-support-system' );
								echo '</label>';
							echo '</td>';
						echo '</tr>';

						/*Support email*/
						echo '<tr class="support-email-fields">';
							echo '<th scope="row">' . __( 'Support email', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<input type="email" class="support-email regular-text" name="support-email" placeholder="noreply@example.com" value="' . $support_email . '">';
								echo '<p class="description">' . __( 'The email address used to send and receive notifications.', 'wc-support-system' ) . '</p>';
							echo '</td>';
						echo '</tr>';

						/*Support email "from" name*/
						echo '<tr class="support-email-fields">';
							echo '<th scope="row">' . __( '"From" name', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<input type="text" class="support-email-name regular-text" name="support-email-name" placeholder="Example Support" value="' . $support_email_name . '">';
								echo '<p class="description">' . __( 'The sender name for notifications.', 'wc-support-system' ) . '</p>';
							echo '</td>';
						echo '</tr>';

						/*Footer email text*/
						echo '<tr class="support-email-fields">';
							echo '<th scope="row">' . __( 'Footer email text', 'wc-support-system' ) . '</th>';
							echo '<td>';

								$placeholder = sprintf( __( 'Don\'t reply to this email, you can read all messages and update the ticket going to the page %s.', 'wc-support-system' ), get_the_title( $this->support_page ) );

								echo '<textarea class="support-email-footer" name="support-email-footer" placeholder="' . $placeholder . '" cols="60" rows="3">' . esc_html( wp_unslash( $support_email_footer ) ) . '</textarea>';
								echo '<p class="description">' . __( 'You can add some text after the email content.', 'wc-support-system' ) . '</p>';
							echo '</td>';
						echo '</tr>';

						/*Uploads available for customers*/
						echo '<tr>';
							echo '<th scope="row">' . __( 'Upload files', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<label for="customer-uploads">';
									echo '<input type="checkbox" name="customer-uploads" value="0" disabled="disabled">';
									echo __( 'Allow customers upload images and all the other permitted file types.', 'wc-support-system' );
								echo '</label>';
								$this->go_premium();
							echo '</td>';
						echo '</tr>';

						/*Support for not logged in users*/
						echo '<tr>';
							echo '<th scope="row">' . __( 'Guest users', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<label for="guest-users">';
									echo '<input type="checkbox" name="guest-users" value="0" disabled="disabled">';
									echo __( 'Not logged in users can receive support providing the email and an order id.', 'wc-support-system' );
								echo '</label>';
								$this->go_premium();
							echo '</td>';
						echo '</tr>';

						/*Reopen a ticket after a new thread is sent in back-end*/
						echo '<tr>';
							echo '<th scope="row">' . __( 'Reopen ticket', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<label for="reopen-ticket">';
									echo '<input type="checkbox" name="reopen-ticket" value="0" disabled="disabled">';
									echo __( 'After sending a new message, the admin can choose to left the specific ticket open and see the all thread.', 'wc-support-system' );
								echo '</label>';
								$this->go_premium();
							echo '</td>';
						echo '</tr>';

						/*Let user close tickets*/
						echo '<tr class="user-closing-tickets-field">';
							echo '<th scope="row">' . __( 'User closing tickets', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<label for="">';
									echo '<input type="checkbox" class="user-closing-tickets" name="user-closing-tickets" value="0" disabled="disabled">';
									echo __( 'Allow user to close tickets.', 'wc-support-system' );
								echo '</label>';
								$this->go_premium();
							echo '</td>';
						echo '</tr>';

						/*Close not updated tickets after a specified period*/
						echo '<tr class="auto-close-tickets-field">';
							echo '<th scope="row">' . __( 'Auto close tickets', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<label for="">';
									echo '<input type="checkbox" class="auto-close-tickets" name="auto-close-tickets" value="0" disabled="disabled">';
									echo __( 'Close tickets not updated for a specified period.', 'wc-support-system' );
								echo '</label>';
								$this->go_premium();
							echo '</td>';
						echo '</tr>';

						/*Days of no updates for sending a notice to the user*/
						echo '<tr class="auto-close-fields">';
							echo '<th scope="row">' . __( 'Notice period', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<input type="number" name="auto-close-days-notice" min="1" max="100" step="1" value="7" disabled="disabled">';
								echo '<p class="description">' . __( 'Days with no updates for sending a notice to the user.', 'wc-support-system' ) . '</p>';
								$this->go_premium();
							echo '</td>';
						echo '</tr>';

						/*Closing ticket user notification*/
						echo '<tr class="auto-close-fields">';
							echo '<th scope="row">' . __( 'User notice', 'wc-support-system' ) . '</th>';
							echo '<td>';

								$default_text = sprintf(
									__(
										"Hi, we have not heard back from you in a few days.\nDo you need anything else from us for this support case?\nIf yes, please update the ticket on %s, we will get back to you asap.\nIf your questions have been answered, please disregard this message and we will mark this case as resolved.\nThanks!",
										'wss'
									),
									get_bloginfo()
								);

								echo '<textarea class="auto-close-notice-text" name="auto-close-notice-text" cols="60" rows="6" disabled="disabled">' . $default_text . '</textarea>';
								echo '<p class="description">' . __( 'Message to the user informing him that the ticket is going to be closed.', 'wc-support-system' ) . '</p>';
								$this->go_premium();
							echo '</td>';
						echo '</tr>';

						/*Days after the notice for closing the ticket defintely*/
						echo '<tr class="auto-close-fields">';
							echo '<th scope="row">' . __( 'Closing delay', 'wc-support-system' ) . '</th>';
							echo '<td>';
								echo '<input type="number" name="auto-close-days" min="1" max="10" step="1" value="0" disabled="disabled">';
								echo '<p class="description">' . __( 'Days after the notice for closing the ticket definitely.', 'wc-support-system' ) . '</p>';
								$this->go_premium();
							echo '</td>';
						echo '</tr>';

					echo '</table>';
					echo '<input type="hidden" name="wss-options-hidden" value="1">';
					echo '<input type="submit" class="button button-primary" value="' . __( 'Save', 'wc-support-system' ) . '">';
				echo '</form>';
			echo '</div>';
			echo '<div class="wrap-right">';
				echo '<iframe width="300" height="900" scrolling="no" src="http://www.ilghera.com/images/wss-iframe.html"></iframe>';
			echo '</div>';
			echo '<div class="clear"></div>';
		echo '</div>';
	}


	/**
	 * Save the WSS options
	 */
	public function wss_save_settings() {

		if ( isset( $_POST['premium-key-sent'] ) ) {

			/*Premium key*/
			$premium_key = isset( $_POST['wss-premium-key'] ) ? sanitize_text_field( $_POST['wss-premium-key'] ) : '';
			update_option( 'wss-premium-key', $premium_key );

		} elseif ( isset( $_POST['wss-options-hidden'] ) ) {

			/*Support page*/
			$support_page = isset( $_POST['support-page'] ) ? sanitize_text_field( $_POST['support-page'] ) : '';
			if ( $support_page == 'new' ) {
				$page_title = isset( $_POST['support-page-title'] ) ? sanitize_text_field( $_POST['support-page-title'] ) : '';
				if ( $page_title ) {
					$support_page = $this->add_support_page( $page_title );
				}
			}

			update_option( 'wss-page', $support_page );
			$this->support_page     = $support_page;
			$this->support_page_url = get_the_permalink( $this->support_page );

			/*Page layout*/
			$page_layout = isset( $_POST['page-layout'] ) ? sanitize_text_field( $_POST['page-layout'] ) : '';
			update_option( 'wss-page-layout', $page_layout );

			/*Admin's threads colors*/
			$admin_color_background = isset( $_POST['admin-color-background'] ) ? sanitize_hex_color( $_POST['admin-color-background'] ) : '';
			$admin_color_text       = isset( $_POST['admin-color-text'] ) ? sanitize_hex_color( $_POST['admin-color-text'] ) : '';
			update_option( 'wss-admin-color-background', $admin_color_background );
			update_option( 'wss-admin-color-text', $admin_color_text );

			/*User's threads colors*/
			$user_color_background = isset( $_POST['user-color-background'] ) ? sanitize_hex_color( $_POST['user-color-background'] ) : '';
			$user_color_text       = isset( $_POST['user-color-text'] ) ? sanitize_hex_color( $_POST['user-color-text'] ) : '';
			update_option( 'wss-user-color-background', $user_color_background );
			update_option( 'wss-user-color-text', $user_color_text );

			/*Notifications*/
			$user_notification  = isset( $_POST['user-notification'] ) ? sanitize_text_field( $_POST['user-notification'] ) : 0;
			$admin_notification = isset( $_POST['admin-notification'] ) ? sanitize_text_field( $_POST['admin-notification'] ) : 0;
			update_option( 'wss-user-notification', $user_notification );
			update_option( 'wss-admin-notification', $admin_notification );

			/*Support email/ email name*/
			$support_email        = isset( $_POST['support-email'] ) ? sanitize_email( $_POST['support-email'] ) : '';
			$support_email_name   = isset( $_POST['support-email-name'] ) ? sanitize_text_field( $_POST['support-email-name'] ) : '';
			$support_email_footer = isset( $_POST['support-email-footer'] ) ? wp_filter_post_kses( $_POST['support-email-footer'] ) : '';
			update_option( 'wss-support-email', $support_email );
			update_option( 'wss-support-email-name', $support_email_name );
			update_option( 'wss-support-email-footer', $support_email_footer );

		}
	}

}
new wc_support_system();

