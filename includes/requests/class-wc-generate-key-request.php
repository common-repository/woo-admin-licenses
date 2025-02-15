<?php

class WC_Generate_Key_Request extends WC_Software_API_Request {
	public function do_request() {
		global $wc_software, $wpdb;

		$this->check_required( array( 'product_id', 'secret_key', 'email' ) );

		$input = $this->check_input( array( 'product_id', 'secret_key', 'email', 'order_id', 'version', 'key_prefix', 'activations' ) );

		if ( $wc_software->check_product_secret( $input['product_id'], $input['secret_key'] ) ) {

			$key_prefix 	= $input['key_prefix'];
			$key 			= $wc_software->generate_license_key();
			$version 		= $input['version'];
			$activations 	= $input['activations'];
			$expire         = NULL;
			// Get product details
			$product_id = $wpdb->get_var( $wpdb->prepare( "
				SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = '_software_product_id'
				AND meta_value = %s LIMIT 1
			", $input['product_id'] ) );

			if ( $product_id ) {
				if ( ! $key_prefix ) {
					$key_prefix = get_post_meta( $product_id, '_software_license_key_prefix', true );
				}
				if ( ! $version ) {
					$version = get_post_meta( $product_id, '_software_version', true );
				}
				if ( ! $activations ) {
					$activations = get_post_meta( $product_id, '_software_activations', true );
				}
				$expire = get_post_meta( $product_id, '_mcpat_wal_software_expire', true );
			}

			$data = array(
				'order_id' 				 => $input['order_id'],
				'activation_email'		 => $input['email'],
				'prefix'				 => $key_prefix,
				'license_key'			 => $key,
				'licence_key'			 => $key, // bw compat
				'software_product_id'	 => $input['product_id'],
				'software_version'		 => $version,
				'activations_limit'		 => $activations,
				'software_expire'		 => $expire,
	        );

			//$key_id = $wc_software->save_license_key( $data );
			$key_id = mcpat_wal_save_license_key( $data );
			
			return array( 'key' => $key_prefix . $key, 'key_id' => $key_id );
		} else {
			$this->wc_software_api->error( '105', __( 'Non matching product_id and secret_key provided', 'woo-admin-licenses' ), null, array( 'activated' => false ) );
		}
	}
};

?>
