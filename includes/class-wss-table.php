<?php
/**
 * Admin tickets table
 *
 * @author ilGhera
 * @package wc-support-system-premium/includes
 *
 * @since 1.2.3
 */

defined( 'ABSPATH' ) || exit;

/*The main class is required*/
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * WSS Table class
 *
 * @since 0.9.4
 */
class WSS_Table extends WP_List_Table {

	/**
	 * The constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Ticket', 'wc-support-system' ),
				'plural'   => __( 'Tickets', 'wc-support-system' ),
				'ajax'     => false,
			)
		);
	}


	/**
	 * Get all tickets from the db
	 *
	 * @param  integer $per_page    tickets per page, default 12.
	 * @param  integer $page_number the current page, default 1.
	 *
	 * @return array
	 */
	public static function get_tickets( $per_page = 12, $page_number = 1 ) {

		global $wpdb;

		$args     = array();
		$where    = null;
		$order_by = null;
		$limit    = null;
		$offset   = null;

		/*Filtered by search term*/
		if ( isset( $_REQUEST['s'], $_REQUEST['wss-thread-sent-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['wss-thread-sent-nonce'] ) ), 'wss-thread-sent' ) ) {

			$s = trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) );

			/* Update query args */
			array_push( $args, $s, $s, $s );

			$where .= ' WHERE user_name LIKE \'%%%s%%\'';
			$where .= ' OR user_email LIKE \'%%%s%%\'';
			$where .= ' OR title LIKE \'%%%s%%\'';

			if ( 0 === strpos( $s, '#' ) ) {

				$ticket_id = substr( $s, 1 );

				/* Update query args */
				array_push( $args, $ticket_id );

				$where .= ' OR id LIKE \'%%%d%%\'';

			}
		}

		/*If filtered by the admin*/
		if ( ! empty( $_REQUEST['orderby'] ) ) {

			$val1 = esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) );
			$val2 = ! empty( $_REQUEST['order'] ) ? esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) : ' ASC';

			/* Update query args */
			array_push( $args, $val1, $val2 );

			$order_by = ' ORDER BY %s %s';

		} else {

			$order_by = ' ORDER BY status ASC';

		}

		/*Pagination details*/
		$val3 = ( $page_number - 1 ) * $per_page;

		/* Update query args */
		array_push( $args, $per_page, $val3 );

		$limit  = ' LIMIT %d';
		$offset = ' OFFSET %d';

		$tickets = $wpdb->get_results(
			$wpdb->prepare(
				"
                SELECT * FROM {$wpdb->prefix}wss_support_tickets
                $where
                $order_by
                $limit
                $offset
                ",
				$args
			),
			'ARRAY_A'
		);

		return $tickets;
	}


	/**
	 * Returns the count of tickets in the db.
	 *
	 * @return int
	 */
	public static function record_count() {

		global $wpdb;

		$sql = '
			
		';

		return $wpdb->get_var(
			"
            SELECT COUNT(*) FROM {$wpdb->prefix}wss_support_tickets
            "
		);
	}


	/**
	 * The primary column name
	 *
	 * @return string
	 */
	public function get_primary_column_name() {

		return 'title';

	}


	/**
	 * Text displayed when no tickets are available
	 *
	 * @return void
	 */
	public function no_items() {

		esc_html_e( 'It seems like therea are no support tickets opened at the moment.', 'wc-support-system' );

	}


	/**
	 * Edit every single row of the table
	 *
	 * @param array $item the single ticket in the row.
	 *
	 * @return mixed
	 */
	public function single_row( $item ) {

		echo '<tr class="ticket-' . intval( $item['id'] ) . '">';
			$this->single_row_columns( $item );
		echo '</tr>';

	}


	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param array  $item        the item.
	 * @param string $column_name the name of the column.
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'product_id':
				$title     = get_the_title( $item['product_id'] );
				$title     = $title ? $title : __( 'This product doesn\'t exist anymore', 'wc-support-system' );
				$thumbnail = get_the_post_thumbnail( $item['product_id'], array( 40, 40 ), array( 'title' => $title ) );

				if ( $thumbnail ) {
					$image = $thumbnail;
				} else {
					$image = '<img src="' . home_url() . '/wp-content/plugins/woocommerce/assets/images/placeholder.png" title="' . $title . '">';
				}

				return $image;

			case 'title':
				return '<span class="ticket-toggle' . ( 1 === intval( $item['status'] ) ? ' bold' : '' ) . '" data-ticket-id="' . $item['id'] . '">' . stripcslashes( $item['title'] ) . '</span>';

			case 'status':
				return WC_Support_System::get_ticket_status_label( $item['status'] );

			case 'delete':
				return '<img data-ticket-id="' . $item['id'] . '" src="' . plugin_dir_url( __DIR__ ) . '/images/dustbin-admin.png">';

			default:
				return $item[ $column_name ];
		}
	}


	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item the item.
	 *
	 * @return string the input checkbox
	 */
	public function column_cb( $item ) {

		return sprintf( '<input type="checkbox" name="delete[]" value="%s" />', $item['id'] );

	}


	/**
	 * Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {

		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'id'          => __( 'Id', 'wc-support-system' ),
			'title'       => __( 'Title', 'wc-support-system' ),
			'user_id'     => __( 'User id', 'wc-support-system' ),
			'user_name'   => __( 'User name', 'wc-support-system' ),
			'user_email'  => __( 'User email', 'wc-support-system' ),
			'product_id'  => __( 'Product', 'wc-support-system' ),
			'status'      => __( 'Status', 'wc-support-system' ),
			'create_time' => __( 'Create time', 'wc-support-system' ),
			'update_time' => __( 'Update time', 'wc-support-system' ),
			'delete'      => '',
		);

		return $columns;

	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {

		$sortable_columns = array(
			'id'          => array( 'id', true ),
			'title'       => array( 'title', true ),
			'user_id'     => array( 'user_id', true ),
			'user_name'   => array( 'user_name', true ),
			'user_email'  => array( 'user_email', true ),
			'product_id'  => array( 'product_id', true ),
			'status'      => array( 'status', true ),
			'create_time' => array( 'create_time', true ),
			'update_time' => array( 'update_time', true ),
		);

		return $sortable_columns;

	}


	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {

		$actions = array(
			'delete' => __( 'Delete Permanently', 'wc-support-system' ),
		);

		return $actions;

	}


	/**
	 * The bulk action process, delete tickets in this case
	 *
	 * @return void
	 */
	public function process_bulk_action() {

		if ( isset( $_POST['wss-thread-sent-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wss-thread-sent-nonce'] ) ), 'wss-thread-sent' ) ) {

			if ( ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) || ( isset( $_POST['action2'] ) && 'delete' === $_POST['action2'] ) ) {

				$delete_ids = isset( $_POST['delete'] ) ? $_POST['delete'] : array();
				$delete_ids = array_map( 'wp_unslash', $delete_ids );
				$delete_ids = array_map( 'sanitize_text_field', $delete_ids );

				foreach ( $delete_ids as $id ) {

					WC_Support_System::delete_single_ticket( $id );

				}
			}
		}

	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 *
	 * @return void
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'tickets_per_page', 12 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->items = self::get_tickets( $per_page, $current_page );

	}

}
