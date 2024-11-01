<?php

class WC_Deactivation_Request extends WC_Software_API_Request {
	public function do_request() {
		global $wc_software;

		$required = array( 'email', 'license_key', 'product_id' );
		$this->check_required( $required );

		$input = $this->check_input( array( 'email', 'license_key', 'product_id', 'platform', 'instance', 'activation_id' ) );

		// Validate email
		if ( ! is_email( $input['email'] ) )
			$this->wc_software_api->error( '100', __( 'The email provided is invalid', 'woo-admin-licenses' ), null, array( 'reset' => false ) );

		$data = $wc_software->get_license_key( $input['license_key'], $input['product_id'], $input['email'] );

		if ( ! $data )
			$this->wc_software_api->error( '101', __( 'No matching license key exists', 'woo-admin-licenses' ), null, array( 'activated' => false ) );

		// reset number of activations
		global $wpdb;
		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_software_product_id' AND meta_value = %s LIMIT 1", $input['product_id'] ) );
		$item = wc_get_product($post_id);
		$preis = get_post_meta( $item->get_id(), '_regular_price', true);
		$showdeaktivate = get_post_meta( $item->get_id(), '_mcpat_wal_showdeactivation', true );
		if (($preis == 0 && get_option( 'mcpat_wal_enable_deactivation_freeware' ) === 'yes') || ($preis != 0 && get_option( 'mcpat_wal_enable_deactivation_software' ) === 'yes') || $showdeaktivate === 'yes')
			$is_deactivated = $wc_software->deactivate_license_key( $data->key_id, $input['instance'], $input['activation_id'] );

		if ( !$is_deactivated )
			$this->wc_software_api->error( '901', __( 'This version cannot be deactivated', 'woo-admin-licenses' ), null, array( 'activated' => false ) );
		//	$this->wc_software_api->error( '104', __( 'No matching instance exists', 'woo-admin-licenses' ), null, array( 'activated' => false ) );

		$output_data = get_object_vars( $data );
		$output_data['reset'] = true;
		$output_data['timestamp'] = time();
		$to_output = array();
		$to_output['reset'] = 'reset';
		$to_output['timestamp'] = 'timestamp';
		return $this->prepare_output( $to_output, $output_data );
	}
}
