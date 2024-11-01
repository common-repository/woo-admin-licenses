<?php

/**
 * WC_Check_Request class.
 *
 * @extends WC_Software_API_Request
 */
class WC_Check_Request extends WC_Software_API_Request {

	/**
	 * do_request function.
	 *
	 * @access public
	 */
	public function do_request() {
		global $wc_software;

		$this->check_required( array( 'email', 'license_key', 'product_id' ) );

		$input = $this->check_input( array( 'email', 'license_key', 'product_id' ) );

		// Validate email
		if ( ! is_email( $input['email'] ) )
			$this->wc_software_api->error( '100', __( 'The email provided is invalid', 'woo-admin-licenses' ), null, array( 'success' => false ) );

		// Check if the license key is valid for this user and get the key
		$data = $wc_software->get_license_key( $input['license_key'], $input['product_id'], $input['email'] );

		if ( ! $data )
			$this->wc_software_api->error( '101', __( 'No matching license key exists', 'woo-admin-licenses' ), null, array( 'success' => false ) );

		// Validate order if set
		if ( $data->order_id ) {
			if ( version_compare( WC_VERSION, '2.2.0', '<' ) ) {
				$order_status = wp_get_post_terms( $data->order_id, 'shop_order_status' );
				$order_status = $order_status[0]->slug;
			} else {
				$order_status = get_post_status( $data->order_id );
				$order_status = 'wc-' === substr( $order_status, 0, 3 ) ? substr( $order_status, 3 ) : $order_status;
			}
			if ( $order_status != 'completed' ) {
				$this->wc_software_api->error( '102', __( 'The purchase matching this product is not complete', 'woo-admin-licenses' ), null,  array( 'success' => false ) );
			}
		}

		// Check was successful - return json
		$output_data = get_object_vars( $data );

		$activations_rows = $wc_software->get_license_activations( $input['license_key'] );
		$activations = array();
		foreach ( $activations_rows as $row ) {
			if ( ! $row->activation_active ) {
				continue;
			}
			$expire = 'Never';
			if ($row->software_expire != 0) {
				$date = new DateTime($row->activation_time);
				$date->add(new DateInterval('P' . $row->software_expire .'D'));
				$expire = $date->format('Y-m-d H:i:s');
				
			}
			
			$activations[] = array(
				'activation_id'       => $row->activation_id,
				'instance'            => $row->instance,
				'activation_platform' => $row->activation_platform,
				'activation_time'     => $row->activation_time,
				'software_expire'     => $expire,
			);
		}

		$output_data['success']     = true;
		$output_data['time']        = time();
		$output_data['remaining']   = $wc_software->activations_remaining( $data->key_id );
		$output_data['activations'] = $activations;

		$to_output                = array( 'success' );
		$to_output['message']     = 'message';
		$to_output['timestamp']   = 'time';
		$to_output['remaining']   = 'remaining';
		$to_output['activations'] = 'activations';

		return $this->prepare_output( $to_output, $output_data );
	}

}
