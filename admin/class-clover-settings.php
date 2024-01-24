<?php
/**

 * RP_Clover_Settings
 *
 * @package RestroPress Discount Code

 * @since 1.0
 */

defined( 'ABSPATH' ) || exit;

class RP_Clover_Settings {

	public function __construct() {	
		add_action( 'admin_menu', array( $this, 'rp_clover_admin_menu' ),99 );

		add_action( 'wp_ajax_get_categories', array( $this,'get_categories_ajax' ) );
		add_action( 'wp_ajax_nopriv_get_categories', array( $this,'get_categories_ajax' ) );
		add_action( 'created_term', array( $this,'add_upd_cat_pos' ), 10, 3);
		add_action( 'delete_term', array( $this,' delete_cat_pos' ),10, 3  );

		add_action( 'wp_ajax_get_customers', array( $this,'get_customers_ajax' ) );
		add_action( 'wp_ajax_nopriv_get_customers', array( $this,'get_customers_ajax' ) );
		add_action('rpress_customer_post_create', array( $this,'create_customer' ),  99, 2);
		add_action('rpress_pre_delete_customer', array( $this,'delete_customer_action' ),  99, 3);

		add_action( 'wp_ajax_get_items', array( $this,'get_items_ajax' ) );
		add_action( 'wp_ajax_nopriv_get_items', array( $this,'get_items_ajax' ) );
		add_action( 'save_post', array( $this,'send_items' ), 10, 2 );
		add_action(	'wp_trash_post', array( $this,'delete_items' ), 10, 1);

		add_action(	'wp_ajax_get_orders', array( $this,'get_orders_ajax' ) );
		add_action( 'wp_ajax_nopriv_get_orders', array( $this,'get_orders_ajax' ) );
		add_action(	'rpress_after_payment_receipt', array( $this,'add_order_details' ), 10, 2);
		add_action(	'rpress_payment_delete', array( $this,'delete_order' ), 10, 1);
		add_action(	'rpress_view_order_details_update_after', array( $this,'update_order' ), 10,1 );

		add_action(	'rest_api_init', function () {
			register_rest_route( 'clover-webhook/v1', '/set-url/', array(
				'methods' => 'POST',
				'callback' => array( $this, 'clover_set_webhook_url' ),
			));
		});	
			
		add_filter( 'manage_food-category_custom_column', array( $this,'add_book_place_column_content'), 10, 3 );
		add_filter( 'manage_edit-food-category_columns', array( $this,'add_custom_column_to_terms_table'), 10, 1 );

		add_filter( 'rpress_fooditem_columns',array( $this,'custom_post_type_columns' ) );
		add_action( 'manage_posts_custom_column', array( $this,'custom_post_type_column_data' ), 10, 2 );

		// add_filter( 'init', array( $this,'test_function'), 10 );

		$this->get_admin_fields();
		
	}

	/**
	 * Set Clover in the admin menu.
	 * @return $general_settings
	 * @author magnigeeks
	 * @package RP_Clover_Settings
	 * @since   1.0
	 */
	public function rp_clover_admin_menu() {
		add_submenu_page(
            'restropress',
            __( 'Clover Pos', 'rp-clover' ),
            __( 'Clover Pos', 'rp-clover' ),
            'manage_shop_settings',
            'rpress-clover-pos',
            'rp_clover_menu_page',
        );      
	}
		
	public function get_admin_fields() {
		require_once RP_CLOVER_PLUGIN_DIR . 'includes/rp-clover-function.php';
	}

	//category functionality started
	public function get_categories_ajax() {
		$mearchant_id 	= get_option( 'clover_merchant_id' );
	    $auth_key 		= get_option( 'clover_auth_key' );
	    $url = 'https://sandbox.dev.clover.com/v3/merchants/' . $mearchant_id . '/categories';
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        'Authorization: Bearer ' . $auth_key . '',
	        'Content-Type: application/json'
	    ));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response = curl_exec($ch);
	    curl_close($ch);
	    $categories = json_decode($response, true);
 		foreach( $categories['elements'] as $categorie ){	       			
			$name = $categorie['name'];
			$clv_cat_id = $categorie['id'];
			$term = get_term_by('_clover_cat_id', $clv_cat_id , 'food-category');
			if ($term) {
				//update category
 				$term_id = $term->term_id;
				add_option( 'clover_catgs_import', $term['term_id'], );
				$updated_term = wp_update_term($term_id,'food-category' , array(
					'name' => $name,
				));
			}
			else{
				//add categoery
				$term = wp_insert_term( $name, 'food-category' );
				if (!is_wp_error($term)) {
					add_option( 'clover_catgs_import', $term['term_id'], );
					add_term_meta( $term['term_id'],  '_clover_cat_id', $clv_cat_id, true );
 				}  				
			}	
		}

		wp_send_json_success('Categories imported successfully.');

		 wp_die();
	}
	public function add_upd_cat_pos( $term_id, $tt_id, $taxonomy ) {
		if( get_option( 'clover_hook_cat' ) == $term_id ){
			delete_option( 'clover_hook_cat' );
			return;
		}		
		if( get_option( 'clover_catgs_import' ) == $term_id ){
			delete_option( 'clover_hook_cat' );
			return;
		}		
		if( $taxonomy === 'food-category' ) {
			$clover_cat_id = get_term_meta( $term_id, '_clover_cat_id', true );
			$food_cat_name = get_term( $term_id )->name;
			$mearchant_id 	= get_option( 'clover_merchant_id' );
			$auth_key 		= get_option( 'clover_auth_key' );
			$data = array(
				'name' => $food_cat_name
			);
			if( $clover_cat_id ){
				add_option( 'clover_callback_hook',$clover_cat_id, );
				//update category at clover
				$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $mearchant_id . '/categories/' .$clover_cat_id;			 
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt( $ch, CURLOPT_POST, 1 );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($data) );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer ' . $auth_key,
					'Content-Type: application/json'
				) );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			}
			else{
				//create category at clover
				$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $mearchant_id . '/categories';			 
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt( $ch, CURLOPT_POST, 1 );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($data) );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer ' . $auth_key,
					'Content-Type: application/json'
				) );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				$response = curl_exec( $ch );
				curl_close( $ch );				
				$categories = json_decode( $response, true );
				$clover_cat_id = $categories['id'];
				add_term_meta( $term_id, '_clover_cat_id', $clover_cat_id ); 
			} 			
		}

	}	
 	public function delete_cat_pos($term_id, $tt_id, $taxonomy) {
		$clover_cat_id = get_term_meta( $term_id, '_clover_cat_id', true );
		$merchant_id 	= get_option( 'clover_merchant_id' );
		$auth_key 		= get_option( 'clover_auth_key' );
		$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $merchant_id . '/categories/' . $clover_cat_id;
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer ' . $auth_key,
			'Content-Type: application/json'
		) );
		$response = curl_exec($ch);
		curl_close($ch);
		delete_term_meta($term_id, '_clover_cat_id');
	}
	public function item_category_action( $object,$clv_id ){
		if(get_option( 'clover_callback_hook')==$clv_id){
			delete_option( 'clover_callback_hook' );
			return;
		}
		global $wpdb;
		$meta_key = '_clover_cat_id';  
		$meta_value = $clv_id;  
			$table_prefix = $wpdb->prefix;
			$query = $wpdb->prepare(
			"SELECT term_id
			FROM {$table_prefix}termmeta
			WHERE meta_key = %s AND meta_value = %s",
			$meta_key,
			$meta_value
		);
		$term_id = $wpdb->get_var($query);
		if ( $term_id  ) {
			//update food category
				$cat_data = array(
				'name' => $object['name'],
				);
			add_option( 'clover_hook_cat', $term_id, );
			wp_update_term($term_id, 'food-category', $cat_data); 
		} else {
			//new food category
 			$clover_cat_id = $meta_value;
			$term_data = array(
				'term'     => $object['name'],   
				'taxonomy' => 'food-category',  
			);			
			$term = wp_insert_term( $term_data['term'], $term_data['taxonomy'] );
			add_term_meta( $term['term_id'], $meta_key, $clover_cat_id, true );
		}
	}
	//category functionality ended
	

	//item functionality started
	public function get_items_ajax() {
		$mearchant_id  = get_option( 'clover_merchant_id' );
		$auth_key      = get_option( 'clover_auth_key' );
		$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $mearchant_id . '/items';	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer ' . $auth_key . '',
			'Content-Type: application/json'
		));	
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	
		$response = curl_exec($ch);
		curl_close($ch);	
		$cl_fooditems = json_decode($response, true);
		$fooditems = $cl_fooditems['elements'];
		foreach( $fooditems  as $fooditem ) {
		   $fooditem_data = array(
			   'post_title'  => $fooditem['name'],
			   'post_status' => 'publish',
			   'post_type'   => 'fooditem',
		   );
			$args = array(
				'post_type' => 'fooditem',  
				'posts_per_page' => 1,  
				'meta_key' =>  '_clover_item_id',
				'meta_value' => $fooditem['id'],
				);
			$query = new WP_Query($args);
			global $wpdb;
			$table_prefix = $wpdb->prefix;
			if ($query->have_posts()) {
				while ($query->have_posts()) {
					$query->the_post();
					$post_id = get_the_ID();
  				}
 			} else {
				$sql = $wpdb->prepare(
					"INSERT INTO " . $table_prefix . "posts (post_title, post_status, post_type) VALUES (%s, %s, %s)",
					$fooditem_data['post_title'],
					$fooditem_data['post_status'],
					$fooditem_data['post_type']
				);
				$wpdb->query($sql);
				$fooditem_id = $wpdb->insert_id;
				$wpdb->insert(
					$table_prefix . 'postmeta',
					array(
						'post_id'    => $fooditem_id,
						'meta_key'   => '_clover_item_id',
						'meta_value' => $fooditem['id']
					)
				);
				  $wpdb->insert(
				   $table_prefix . 'postmeta',
				   array(
					   'post_id'    => $fooditem_id,
					   'meta_key'   => 'rpress_price',
					   'meta_value' => $fooditem['price']/100
				   )
			   );
 			}
			
	   	}		 
		wp_send_json_success( 'Items imported successfully.' );   
		wp_die();
   	}
   	public function send_items( $post_ID, $post ) {
		if ($post->post_type === 'fooditem'){
			if (defined('DOING_TRASH') && DOING_TRASH) return;
			if ( !empty( $post->post_title ) && $post->post_title != 'Auto Draft' ) {
				$mearchant_id 	= get_option( 'clover_merchant_id' );
				$auth_key 		= get_option( 'clover_auth_key' );
				$clover_item_id = get_post_meta( $post_ID, '_clover_item_id', true );
				$terms = wp_get_post_terms( $post_ID, 'food-category' );
				$categories = [];
				if (!empty($terms)) {
					foreach ($terms as $term) {
						// Add the term name to the array
						$term_names['name'] = $term->name;
						array_push( $categories, $term_names );

					}
				}
				print_r( $categories );
				$temp = json_encode( $categories );
				

				
				if ( isset($clover_item_id) && !empty($clover_item_id)) {
					// update clover item
					$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $mearchant_id . '/items/' .$clover_item_id;
					$item_price = get_post_meta($post_ID, 'rpress_price', true);
					$roundup_price = round( $item_price * 100 );
					$item_name =  $post->post_title;
					$data = array(
						"hidden" => false,  
						"available" => true,  
						"autoManage" => false,  
						"defaultTaxRates" => true,  
						"isRevenue" => false,  
						"name" => $item_name,
						"price" => $roundup_price,
						"categories" => $categories

					);
					$json = json_encode($data);
					$ch = curl_init();
					curl_setopt( $ch, CURLOPT_URL, $url );
					curl_setopt( $ch, CURLOPT_POST, 1 );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($data) );
					curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
						'Authorization: Bearer ' . $auth_key,
						'Content-Type: application/json'
					) );
					curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
					$response = curl_exec( $ch );
					
					curl_close( $ch );
					$return = json_decode( $response, true );

					return;
				}
				else {
					$item_price = get_post_meta($post_ID, 'rpress_price', true);
					$roundup_price = round( $item_price * 100 );
					$item_name =  $post->post_title;
					$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $mearchant_id . '/items';
					$data = array(
								"hidden" => false,  
								"available" => true,  
								"autoManage" => false,  
								"defaultTaxRates" => true,  
								"isRevenue" => false,  
								"name" => $item_name,
								"price" => $roundup_price,
								"categories" => $categories
					);
					$json = json_encode($data);
					$ch = curl_init();
					curl_setopt( $ch, CURLOPT_URL, $url );
					curl_setopt( $ch, CURLOPT_POST, 1 );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($data) );
					curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
						'Authorization: Bearer ' . $auth_key,
						'Content-Type: application/json'
					) );
					curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
					$response = curl_exec( $ch );				
					curl_close( $ch );
					$return = json_decode( $response, true );
					print_r($return);
					$clover_item_id = $return['id'];
					add_post_meta($post_ID,  '_clover_item_id', $clover_item_id);
				}
			}
			else {
				return;
			}		
		}
		else {
			return;
		} 	
	}
	public function delete_items( $post_id ) {
		$mearchant_id 	= get_option( 'clover_merchant_id' );
		$auth_key 		= get_option( 'clover_auth_key' );
		if (get_post_type($post_id) == 'fooditem') {
			$clover_item_id = get_post_meta( $post_id, '_clover_item_id', true );
			if ( isset($clover_item_id) && !empty($clover_item_id)) {
				$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $mearchant_id . '/items/' .$clover_item_id;
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer ' . $auth_key,
					'Content-Type: application/json'
				) );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				$response = curl_exec( $ch );
				curl_close( $ch );				 
				delete_post_meta($post_id, 'your_meta_key');
			}
		}
	}
	public function clover_item_action( $object, $clv_id ) {
		$mearchant_id  = get_option( 'clover_merchant_id' );
		$auth_key      = get_option( 'clover_auth_key' );
		$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $mearchant_id . '/items/'.$clv_id;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);	
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer ' . $auth_key . '',
			'Content-Type: application/json'
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		$cl_fooditem = json_decode($response, true);
 		$args = array(
			'meta_key'   =>'_clover_item_id',
			'meta_value' => $clv_id,
			'post_type'  => 'fooditem',   
			'post_status'=> 'publish',  
			'fields'     => 'ids',   
		);
		$query = new WP_Query($args);
		global $wpdb;
		$table_prefix = $wpdb->prefix;
		if ($query->have_posts()) {
			//update food item
			$fooditem_id = $query->posts;
			$sql = $wpdb->prepare(
				"UPDATE " . $table_prefix . "posts 
				SET post_title = %s 
				WHERE ID = %d",
				$cl_fooditem['name'],
 				$fooditem_id[0]
			);
			$wpdb->query($sql);
			update_post_meta($fooditem_id[0],  'rpress_price', $cl_fooditem['price']/100 );	
 		} else {
			$sql = $wpdb->prepare(
				"INSERT INTO " . $table_prefix . "posts (post_title, post_status, post_type) VALUES (%s, %s, %s)",
				$cl_fooditem['name'],
				'publish',
				'fooditem'
			);
			$wpdb->query($sql);
			$fooditem_id = $wpdb->insert_id;
			$wpdb->insert(
				$table_prefix . 'postmeta',
				array(
					'post_id'    => $fooditem_id,
					'meta_key'   => '_clover_item_id',
					'meta_value' => $cl_fooditem['id']
				)
			);
	
			// Set the price
			$wpdb->insert(
				$table_prefix . 'postmeta',
				array(
					'post_id'    => $fooditem_id,
					'meta_key'   => 'rpress_price',
					'meta_value' => $cl_fooditem['price']/100
				)
			);
	
 		}
	}
	//item functionality ended


	//customer functionality started
	public function get_customers_ajax() {
		$merchant_id 	= get_option( 'clover_merchant_id' );
	    $auth_key 		= get_option( 'clover_auth_key' );
 		$custome_obj = new RPRESS_Customer();
		$customer_meta_obj = new RPRESS_DB_Customer_Meta();
		global $wpdb;
		$meta_key = 'clover_customer_id';  
 		$table_prefix = $wpdb->prefix;
		$query = $wpdb->prepare(
			"SELECT meta_value
			FROM {$table_prefix}rpress_customermeta
			WHERE meta_key = %s",
			$meta_key			 
		);
		$results = $wpdb->get_results( $query, ARRAY_A );
		$clover_customer_ids = array_column( $results, 'meta_value' );
	    $url = 'https://sandbox.dev.clover.com/v3/merchants/' . $merchant_id . '/customers/?expand=emailAddresses';
	    $ch = curl_init();
	    curl_setopt( $ch, CURLOPT_URL, $url );
	    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
	        'Authorization: Bearer ' . $auth_key . '',
	        'Content-Type: application/json'
	    ));	
	    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );	    
	    $response = curl_exec( $ch );
	    curl_close( $ch );	   
	    $customers 	= json_decode( $response, true );	    
		foreach ( $customers['elements'] as $customer ) {
			$first_name 		= $customer['firstName'];
			$last_name 			= $customer['lastName'];
			$name 				= $first_name .' '.$last_name;
			$clover_id 			= $customer['id'];
			$email_id = $customer['emailAddresses' ]['elements'][0]['emailAddress'];
			$prev_value 		= '';
			$data = array(
				'name'           => $name,
 				'payment_ids'	 => '',
			);
			global $wpdb;
			$table_prefix = $wpdb->prefix;
			if ( in_array( $clover_id, $clover_customer_ids ) ){
				//update customer
				$query = $wpdb->prepare(
					"SELECT customer_id
					FROM {$table_prefix}rpress_customermeta
					WHERE meta_key = %s AND meta_value = %s",
					'clover_customer_id',
					$clover_id
				);
				$wpdb->query( $query );
				$customer_id = $wpdb->get_var( $query );
				$sql = $wpdb->prepare(
					"UPDATE " . $table_prefix . "rpress_customers 
					SET email = %s, name = %s
					WHERE id = %d",
					$email_id,
					$name,
					$customer_id
				);
				$wpdb->query($sql);
			}
			else{
				//create a customer
				$query = $wpdb->prepare(
					"INSERT IGNORE INTO " . $table_prefix . "rpress_customers ( email, name ) VALUES (%s, %s)",
					$email_id,
					$name				 
				);
				$wpdb->query($query);
				$customer_id = $wpdb->insert_id;
				$customer_meta_obj->add_meta( $customer_id, 'clover_customer_id', $clover_id, true );
 			}
 		
		}
		wp_send_json_success( 'Customer imported successfully.' );

	   	wp_die();
	}
 	public function create_customer( $created, $args ) {
		$mearchant_id 	= get_option( 'clover_merchant_id' );
		$auth_key 		= get_option( 'clover_auth_key' );
		$user_id = $args['user_id'];
		$email_id = $args['email'];
		$customer_id = $created; 
		$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $mearchant_id . '/customers';
		 
		$fullName = $args['name'] ;
		$nameParts = explode(' ', $fullName);
 		$firstName = $nameParts[0];
 		if (count($nameParts) > 2) {
 			for ($i = 1; $i < count($nameParts) - 1; $i++) {
				$firstName .= ' ' . $nameParts[$i];
			}
		}
		$lastName = end( $nameParts );   
		$data = array(
				'firstName' => $firstName,
				'lastName' => $lastName,
				'emailAddresses' => array(
					array('emailAddress' => $email_id )
				)
			);
		$data = json_encode($data);
 		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS,  $data );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer ' . $auth_key,
			'Content-Type: application/json'
		) );
 		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $ch );
 		curl_close( $ch );
		$result = json_decode( $response );
 		$clover_customer_id = $result->id;
		$customer_meta_obj = new RPRESS_DB_Customer_Meta();
		$customer_meta_obj->add_meta( $customer_id, 'clover_customer_id', $clover_customer_id,true );
	}
	public function delete_customer_action( $customer_id, $confirm, $remove_data ){
		$customer_meta_obj = new RPRESS_DB_Customer_Meta();
		$clover_cust_id = $customer_meta_obj->get_meta( $customer_id, 'clover_customer_id',  true );
		print_r( $clover_cust_id );
		$mearchant_id 	= get_option( 'clover_merchant_id' );
		$auth_key 		= get_option( 'clover_auth_key' ); 
 		if ( $clover_cust_id ){
			$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $mearchant_id . '/customers/' .$clover_cust_id;
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'DELETE' );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer ' . $auth_key,
				'Content-Type: application/json'
			) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			$response = curl_exec( $ch );
			curl_close( $ch );
			$customer_meta_obj->delete_meta( $customer_id, 'clover_customer_id', $clover_cust_id );
		}	 
		
	}
	public function clover_customer_action( $object, $clv_id ) {
  		$customer_meta_obj = new RPRESS_DB_Customer_Meta();
		global $wpdb;
		$meta_key = 'clover_customer_id';  
 		$table_prefix = $wpdb->prefix;
		$query = $wpdb->prepare(
			"SELECT meta_value
			FROM {$table_prefix}rpress_customermeta
			WHERE meta_key = %s",
			$meta_key			 
		);
		$results = $wpdb->get_results($query, ARRAY_A);
		$clover_customer_ids = array_column($results, 'meta_value');
 		if ( in_array( $clv_id, $clover_customer_ids) ){
			//update customer
			$query = $wpdb->prepare(
				"SELECT customer_id
				FROM {$table_prefix}rpress_customermeta
				WHERE meta_key = %s AND meta_value = %s",
				$meta_key,
				$clv_id
			);
			$wpdb->query( $query );
 			$customer_id = $wpdb->get_var( $query );
			$sql = $wpdb->prepare(
				"UPDATE " . $table_prefix . "rpress_customers 
				SET email = %s, name = %s, notes = %s
				WHERE id = %d",
				$object['emailAddresses'][0]['emailAddress'],
				$name,
				$object['metadata']['note'],
 				$customer_id
			);
			$wpdb->query($sql);
 		}
		else{
			//add customer
			$name = $object['firstName'] . ' '.$object['lastName'];
			$query = $wpdb->prepare(
				"INSERT INTO " . $table_prefix . "rpress_customers ( email, name, notes ) VALUES (%s, %s,%s)",
				$object['emailAddresses'][0]['emailAddress'],
				$name,
				$object['metadata']['note']
			);
			$wpdb->query($query);
			$customer_id = $wpdb->insert_id;
			$customer_meta_obj->add_meta( $customer_id, 'clover_customer_id', $clv_id, true );
		}		 				
	}
	//customer functionality ended
	
	
	//order function start
   	public function get_orders_ajax() {
		$merchant_id = get_option('clover_merchant_id');
		$auth_key = get_option('clover_auth_key');
		$url = 'https://sandbox.dev.clover.com/v3/merchants/'. $merchant_id .'/orders';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer ' . $auth_key,
			'Content-Type: application/json'
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		
		$response = curl_exec($ch);
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
		curl_close($ch);	
		if ($http_status !== 200) {
 			wp_send_json_error('Failed to fetch orders from Clover API.');
			return;
		}
		$orders = json_decode($response, true);
		$meta_key = '_clover_order_id'; 
		global $wpdb;
 		$all_clover_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
				$meta_key
			)
		);		
		if (isset($orders['elements'])) {
			$elements = $orders['elements'];
 			foreach ($elements as $order) {
				$clover_id = $order['id'];
				if (!(in_array($clover_id, $all_clover_ids))) {
					//create order at restropress
					$payment_data = array(
						'price' => $order['total']/100, 
						'user_info' => array(
							'email' => $order['customers']['elements'][0]['id'],  
						),
						'currency' => $order['currency'],
						'status' => 'pending', 
					);					
					$order_id = rpress_insert_payment($payment_data);
					add_post_meta($order_id, $meta_key, $clover_id);
					if (is_wp_error($order_id)) {
						wp_send_json_error('Error importing orders: ' . $order_id->get_error_message());
						return;
					}
				}
			}
			
			wp_send_json_success('Orders imported successfully.');
		} else {		
			wp_send_json_error('No order elements found in the Clover API response.');
		}
		wp_die();
	}	
 	public function add_order_details( $payment, $rpress_receipt_args ) {
 		$order_id = $payment->ID;
		$payment_mode = get_post_meta( $order_id, '_rpress_payment_mode', true );
		$payment_status = $payment->post_status;
		$clover_payment_status = '';
		if( $payment_status === 'processing' || $payment_status === 'pending' )
			$clover_payment_status = 'OPEN';
 		elseif( $payment_status === 'paid')
			$clover_payment_status = 'PAID';
		elseif( $payment_status === 'refunded')
			$clover_payment_status = 'REFUNDED';

 		$payment_subtotal = get_post_meta( $order_id, '_rpress_payment_subtotal', true );
		$clover_order_id = get_post_meta( $order_id, '_clover_order_id', true );
		if ( !empty( $clover_order_id ) ) {
			 return;
		}
		else {
			//create new order
			$merchant_id 	= get_option( 'clover_merchant_id' );
			$auth_key 		= get_option( 'clover_auth_key' );
			$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $merchant_id . '/orders';
			$data = [
				'state' => 'OPEN',
				'title' => $payment->post_title,
				'total' => $payment_subtotal*100,
				'customers' => [  
					array(
						'firstName' =>$payment->post_title,
					),
				],
				'paymentState' => $clover_payment_status				
			];
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($data) );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer ' . $auth_key,
				'Content-Type: application/json'
			) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			$response = curl_exec( $ch );
			curl_close( $ch );	
			$clover_data = json_decode( $response );
 			$clover_order_id = $clover_data->id;
			add_post_meta( $order_id, '_clover_order_id', $clover_order_id, true );
		}
	}
 	public function update_order( $payment_id ){
		$payment_status = get_post_status( $payment_id );
		$clover_payment_status = '';
		if( $payment_status === 'processing' || $payment_status === 'pending' )
			$clover_payment_status = 'OPEN';
 		elseif( $payment_status === 'paid')
			$clover_payment_status = 'PAID';
		elseif( $payment_status === 'refunded')
			$clover_payment_status = 'REFUNDED';
		
		$clover_order_id = get_post_meta( $payment_id, '_clover_order_id', true );
		if( $clover_order_id ){
			$merchant_id 	= get_option( 'clover_merchant_id' );
			$auth_key 		= get_option( 'clover_auth_key' );
			$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $merchant_id . '/orders/'.$clover_order_id;
			$data = [
				'state' => 'open',
				'paymentState' => $clover_payment_status
			];
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($data) );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer ' . $auth_key,
				'Content-Type: application/json'
			) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			$response = curl_exec( $ch );
			curl_close( $ch );
 		}
		
		 
	}
 	public function delete_order( $payment_id ) {
 		$clover_order_id = get_post_meta( $payment_id, '_clover_order_id', true );
 		$merchant_id 	= get_option( 'clover_merchant_id' );
		$auth_key 		= get_option( 'clover_auth_key' );

		$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $merchant_id . '/orders/' . $clover_order_id;

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer ' . $auth_key,
			'Content-Type: application/json'
		) );
		$response = curl_exec($ch);
		curl_close($ch);
		 
	}
	public function clover_order_action( $object, $clv_id ) {
		$merchant_id = get_option('clover_merchant_id');
		$auth_key = get_option('clover_auth_key');
		$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $merchant_id . '/orders/FR0JVQBNZGFA8';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer ' . $auth_key,
			'Content-Type: application/json'
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		
		$response = curl_exec($ch);
		$response = json_decode( $response );
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
		curl_close($ch);
		$payment_status = $response->paymentState;
		$rs_payment_status = '';
		if( $payment_status === 'OPEN' )
			$rs_payment_status = 'pending';
 		elseif( $payment_status === 'PAID')
			$rs_payment_status = 'paid';
		elseif( $payment_status === 'REFUNDED')
			$rs_payment_status = 'refund';

		$args = array(
			'meta_key'   => '_clover_order_id',
			'meta_value' => $clv_id,
			'post_type'  => 'rpress_payment',   
 			'fields'     => 'ids',   
		);
		$query = new WP_Query($args);

		if ($query->have_posts()) {
			//update order
			$post_ids = $query->posts[0];

		}
		else{
			//add order 
			$payment_data = array(
				'price' => $response->total/100, 
				'user_info' => array(
					'email' => $order['customers']['elements'][0]['id'],  
				),
				'currency' => $response->currency,
				'status' => $rs_payment_status, 
			);					
			$order_id = rpress_insert_payment($payment_data);
			add_post_meta($order_id, '_clover_order_id', $clover_id);
		}

	}
	//order functionality end


	// function to handel webhook of clover
	public function clover_set_webhook_url( WP_REST_Request $request) {
		error_log( print_r( $request, true ) );
		$data  = $request->get_json_params();
		$mearchant_id 	= get_option( 'clover_merchant_id' );
		$appid = isset( $data['appId'] ) ? $data['appId'] : null;
		$merchant_datas = isset($data['merchants'][$mearchant_id]) ? $data['merchants'][$mearchant_id]: array();
		if( isset( $data['verificationCode'] ) ){
			update_option( 'clover_webhook_ver', $data['verificationCode'] );

		}
 		foreach ($merchant_datas as $key => $merchant_data) {
			$object_id = $merchant_data['objectId'];
			$type = $merchant_data['type'];
			$parts = explode(':', $object_id);
			$result = $parts[0];
			$clv_id = $parts[1];			
 			if( $result == 'IC'){
				if ( $type == 'DELETE'){
					//delete food category
					global $wpdb;
					$meta_key = '_clover_cat_id';  
					$meta_value = $clv_id;  
					$table_prefix = $wpdb->prefix;
					$query = $wpdb->prepare(
						"SELECT term_id
						FROM {$table_prefix}termmeta
						WHERE meta_key = %s AND meta_value = %s",
						$meta_key,
						$meta_value
					);
					$term_id = $wpdb->get_var($query);
					if ( $term_id  ) {
						wp_delete_term( $term_id, 'food-category' );
					}
  					return rest_ensure_response( array( 'message' => 'Webhook URL set successfully' ) );
				}
				//add or update food category
				$this->item_category_action( $merchant_data['object'],$clv_id );
			}
			elseif( $result == 'C' ) {
				if ( $type == 'DELETE'){
					//delete customer
					global $wpdb;
					$table_prefix = $wpdb->prefix;
					$query = $wpdb->prepare(
						"SELECT customer_id
						FROM {$table_prefix}rpress_customermeta
						WHERE meta_key = %s AND meta_value = %s",
						'clover_customer_id',
						$clv_id
					);
					$customer_id = $wpdb->get_var($query);
					$query = $wpdb->prepare(
						"DELETE
						FROM {$table_prefix}rpress_customers
						WHERE id = %d ",
						$customer_id
					);
					$wpdb->get_var($query);
					$query = $wpdb->prepare(
						"DELETE
						FROM {$table_prefix}rpress_customermeta
						WHERE customer_id = %d ",
						$customer_id
					);
					$wpdb->get_var($query);
				}
				else{ 
					$this->clover_customer_action( $merchant_data['object'],$clv_id );
				}
			}
			elseif( $result == 'I' ) {
				if ( $type == 'DELETE'){
					global $wpdb;
					$post_id = $wpdb->get_var($wpdb->prepare(
						"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
						 '_clover_item_id',
						$clv_id
					));
					wp_delete_post($post_id, true);
				}
				else{
					$this->clover_item_action( $merchant_data['object'],$clv_id );
				}
 			}
			elseif( $result == 'O' ) {
				$this->clover_order_action( $merchant_data['object'],$clv_id );
 			}
		}
 		return rest_ensure_response( array( 'message' => 'Webhook URL set successfully' ) );
	}
	
	
	//add clover id column to category table.
	public function add_custom_column_to_terms_table( $columns ) {
		$columns['clover_cat_id'] = 'Clover Id';
	   return $columns;
   	}
	public function add_book_place_column_content( $content, $column_name, $term_id ) {
		$term= get_term( $term_id, 'food-category' );
		if ( 'clover_cat_id' === $column_name ) {
 			$custom_data = get_term_meta( $term_id, '_clover_cat_id', true );
			$content = $custom_data ? esc_html( $custom_data ) : 'N/A';
		}
	
		return $content;
	}


	//add clover id column at food item table
	public function custom_post_type_columns( $columns ) {
		$columns['clover_id'] =  __( 'clover Id', 'restropress' );
		return $columns;
	}
	public function custom_post_type_column_data( $column, $post_id ) {
		if ( get_post_type( $post_id ) == 'fooditem' ) {
			if ( $column == 'clover_id' ) {
				$author = get_post_meta( $post_id, '_clover_item_id', true );
				echo esc_html( $author );
			}
		}
		
	}
		
	public function test_function(){
		$mearchant_id  = get_option( 'clover_merchant_id' );
		$auth_key      = get_option( 'clover_auth_key' );
		$url = 'https://sandbox.dev.clover.com/v3/merchants/' . $mearchant_id . '/customers/?expand=emailAddresses';
	    $ch = curl_init();
	    curl_setopt( $ch, CURLOPT_URL, $url );
	    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
	        'Authorization: Bearer ' . $auth_key . '',
	        'Content-Type: application/json'
	    ));	
	    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );	    
	    $response = curl_exec( $ch );
	    curl_close( $ch );
		$customers 	= json_decode( $response, true );	    
		// echo '<pre>';
		// print_r( $customers );
		foreach ( $customers['elements'] as $customer ) {
			 $email_id = $customer['emailAddresses' ]['elements'][0]['emailAddress'];
			 print_r( $email_id );

		}
		exit;
	}


	     
 
		
	
}
new RP_Clover_Settings();

?>

