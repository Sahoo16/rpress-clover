<?php
/**
 * Rpress Clover function
 *
 * @package Rpress_clover
 * @since   2.2
 */

defined( 'ABSPATH' ) || exit;

	function rp_clover_menu_page() {

		 if ( ! current_user_can( 'manage_shop_settings' ) ) {
	        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	    }
	    // Get the saved option values
	    $mearchant_id 	= get_option( 'clover_merchant_id' );
	    $auth_key 		= get_option( 'clover_auth_key' );
	    // Handle form submissions
	    if ( isset( $_POST['submit'] ) ) {
	        // Verify the nonce
	        if ( ! isset( $_POST['my_custom_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['my_custom_meta_box_nonce'], 'my_custom_meta_box_nonce' ) ) {
	            wp_die( __( 'Security check failed' ) );
	        }
	        // Get the values submitted through the form
	        $mearchant_id	= sanitize_text_field( $_POST['mearchant-id'] );   
	        $auth_key 		= sanitize_text_field( $_POST['auth-key'] );

	        // Save the values in the options table
	        update_option( 'clover_merchant_id', $mearchant_id );
	        update_option( 'clover_auth_key', $auth_key );
	    }
	?>
		<div class="wrap">
			<h2><?php esc_html_e( 'RestroPress Clover Pos', 'rp-clover' ); ?></h2>
			
				<div class="cat-import">
				<form  method="post" action="#"  enctype="multipart/form-data" id="my-form">
					

					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">Mearchant ID</th>
								<td>
									<input type="text" name="mearchant-id" value=" <?php echo $mearchant_id; ?>">
								</td>
								
							</tr>								
							<tr>
								<th scope="row">API Token</th>
								<td>
									<input type="text" name="auth-key"  value=" <?php echo $auth_key; ?>">
								</td>		
							</tr>

							<tr>
								<td>
									<?php wp_nonce_field( 'my_custom_meta_box_nonce', 'my_custom_meta_box_nonce' ); ?>
									<input type ="submit" name = "submit"  value="<?php esc_html_e( 'Save Changes', 'rp-clover' ); ?>" class="button-primary" />
								</td>
							</tr>

							<tr>
								<th scope="row"> Webhook URL</th>
								<?php $web_url = home_url().'/restropress/wp-json/clover-webhook/v1/set-url/'; ?>
								<td>
									<input type="text" name="auth-key"  value=" <?php echo $web_url; ?>" style ="width :300px" readonly>
								</td>		
							</tr>

							<tr>
								<th scope="row"> Web hook verification</th>
								<td>
									<input type="text" name="auth-key"  value=" <?php $ver = get_option( 'clover_webhook_ver' ); echo $ver; ?>" style ="width :300px" readonly>
								</td>		
							</tr>
							
							<tr>
								<th><?php esc_html_e( 'Import food categories', 'rp-clover' ); ?></th>
								<td>
								<button type="button" id = "import-id"  class="button-secondary import-cat"><?php esc_html_e( 'click here', 'rp-clover' );?></button>
					
								<div id="import-categories-result"></div>
								</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Import Customers ', 'rp-clover' ); ?></th>
								<td>
								<button type="button" id = "import-cust-id"  class="button-secondary import-cust"><?php esc_html_e( 'click here', 'rp-clover' );?></button>

								<div id="import-customer-result"></div>
								</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Import Items ', 'rp-clover' ); ?></th>
								<td>
								<button type="button" id = "import-item-id"  class="button-secondary import-item"><?php esc_html_e( 'click here', 'rp-clover' );?></button>

								<div id="import-item-result"></div>
								</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Import Orders ', 'rp-clover' ); ?></th>
								<td>
								<button type="button" id = "import-order-id"  class="button-secondary import-order"><?php esc_html_e( 'click here', 'rp-clover' );?></button>

								
								<div id="import-order-result"></div>
								</td>
						</tr>
						</tbody>
					</table>
				</form>
			</div> 				
		
		</div>		
	<?php

	}
?>