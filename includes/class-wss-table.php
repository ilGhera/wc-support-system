<?php
/**
 * Admin tickets table
 * @author ilGhera
 * @package wc-support-system-premium/includes
 * @since 1.0.3
 */

/*The main calss is required*/
if (!class_exists('WP_List_Table')) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * WSS Table
 */
class wss_table extends WP_List_Table {

	public function __construct() {
		parent::__construct( [
			'singular' => __( 'Ticket', 'wss' ), //singular name of the listed records
			'plural'   => __( 'Tickets', 'wss' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?

		] );
	}

	
	/**
	 * Get all tickets from the db
	 * @param  integer $per_page    tickets per page, dwfault 12
	 * @param  integer $page_number the current page, default 1
	 * @return array
	 */
	public static function get_tickets($per_page = 12, $page_number = 1) {

		global $wpdb;
		
		$query  = "SELECT * FROM " . $wpdb->prefix . "wss_support_tickets";

		/*Filtered by search term*/
		if(isset($_REQUEST['s'])) {

			$query .= " WHERE user_name LIKE '%" . esc_sql($_REQUEST['s']) . "%'";
			$query .= " OR user_email LIKE '%" . esc_sql($_REQUEST['s']) . "%'";
			$query .= " OR title LIKE '%" . esc_sql($_REQUEST['s']) . "%'";

            if ( 0 === strpos( $_REQUEST['s'], '#' ) ) {

                $ticket_id = substr( $_REQUEST['s'], 1 );
                $query    .= " OR id LIKE '%" . esc_sql( $ticket_id ) . "%'";

            }
		
		}
		
		/*If filtered by the admin*/
		if(!empty( $_REQUEST['orderby'])) {
		
			$query .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
			$query .= ! empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
	    
	    } else {
		
			$query .= " ORDER BY status ASC";	    	
	    
	    }
		
		/*Pagination details*/
		$query .= " LIMIT $per_page";
		$query .= " OFFSET " . ($page_number - 1) * $per_page; 

		$tickets = $wpdb->get_results($query, 'ARRAY_A');

		return $tickets;
	}


	/**
	 * Returns the count of tickets in the db.
	 */
	public static function record_count() {
		global $wpdb;

		$sql = "
			SELECT COUNT(*) FROM " . $wpdb->prefix ."wss_support_tickets
		";

		return $wpdb->get_var($sql);
	}


	public function get_primary_column_name() {
		return 'title';
	}


	/** 
	 * Text displayed when no tickets are available
	 * @return string
	 */
	public function no_items() {
		echo __( 'It seems like therea are no support tickets opened at the moment.', 'wss' );
	}


	/**
	 * Edit every single row of the table
	 * @param  array $item the single ticket in the row
	 * @return mixed       the row
	 */
	public function single_row($item) {
	    echo '<tr class="ticket-' . $item['id'] . '">';
		    $this->single_row_columns($item);
	    echo '</tr>';
	}


	/**
	 * Render a column when no column specific method exists.
	 * @param array $item
	 * @param string $column_name
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			
			case 'product_id':
                $title     = get_the_title( $item['product_id'] );
                $title     = $title ? $title : __( 'This product doesn\'t exist anymore', 'wss' );
				$thumbnail = get_the_post_thumbnail( $item['product_id'], array( 40,40 ), array( 'title' => $title ) );
				
				if($thumbnail) {
					$image = $thumbnail;
				} else {
					$image = '<img src="' . home_url() . '/wp-content/plugins/woocommerce/assets/images/placeholder.png" title="' . $title . '">';
				}

				return $image;
				break;
			
			case 'title':
				return '<span class="ticket-toggle' . ($item['status'] == 1 ? ' bold' : '') . '" data-ticket-id="' . $item['id'] . '">' .stripcslashes($item['title']) . '</span>';
				break;
			
			case 'status':
				return wc_support_system::get_ticket_status_label($item['status']);
				break;
			
			case 'delete':
				return '<img data-ticket-id="' . $item['id'] . '" src="' . plugin_dir_url(__DIR__) . '/images/dustbin-admin.png">';
				break;
			
			default:
			  return $item[$column_name];
		}
	}


	/**
	 * Render the bulk edit checkbox
	 */
	function column_cb($item) {
		return sprintf('<input type="checkbox" name="delete[]" value="%s" />', $item['id']);
	}


	/**
	 * Associative array of columns
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb' 		  => '<input type="checkbox" />',
			'id' 		  => __('Id', 'wss'),
			'title' 	  => __('Title', 'wss'),
			'user_id' 	  => __('User id', 'wss'),
			'user_name'   => __('User name', 'wss'),
			'user_email'  => __('User email', 'wss'),
			'product_id'  => __('Product', 'wss'),
			'status'	  => __('Status', 'wss'),
			'create_time' => __('Create time', 'wss'),
			'update_time' => __('Update time', 'wss'),
			'delete'	  => ''
		);

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'id' 		  => array('id', true),
			'title' 	  => array('title', true),
			'user_id' 	  => array('user_id', true),
			'user_name'   => array('user_name', true),
			'user_email'  => array('user_email', true),
			'product_id'  => array('product_id', true),
			'status' 	  => array('status', true),
			'create_time' => array('create_time', true),
			'update_time' => array('update_time', true)
		);

		return $sortable_columns;
	}


	/**
	 * Returns an associative array containing the bulk action
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __('Delete Permanently', 'wss')
		);

		return $actions;
	}


	/**
	 * The bulk action process, delete tickets in this case
	 */
	public function process_bulk_action() {
		
		if ( (isset($_POST['action']) && $_POST['action'] == 'delete') || (isset($_POST['action2']) && $_POST['action2'] == 'delete') ) {

			$delete_ids = esc_sql($_POST['delete']);

			foreach ( $delete_ids as $id ) {
				wc_support_system::delete_single_ticket($id);
			}

		}
	}


	/**
	* Handles data query and filter, sorting, and pagination.
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
				'per_page'    => $per_page
			)
		);

		$this->items = self::get_tickets( $per_page, $current_page );

	}

}
