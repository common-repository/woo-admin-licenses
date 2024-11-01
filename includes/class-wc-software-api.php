<?php
/**
 * WC_Software_API class.
 * modified by MCPAT (software expire)
 * @extends WooCommerce_Software
 */
class WC_Software_API {
	public $debug;
	private $available_requests = array();

	public function __construct( $debug = false ) {
		$this->debug = ( WP_DEBUG ) ? true : $debug; // always on if WP_DEBUG is on

		#1.7.4
		if ( version_compare( get_option( 'woocommerce_software_version' ), '1.7.4', '=' ) ) {
			if ( apply_filters( 'woocommerce_software_addon_api_require_user_auth', false ) && ! is_user_logged_in() ) {
				$this->error( '403' );
			}
		}

		$this->load_available_requests();

		if ( isset( $_REQUEST['request'] ) ) {

			$request = $_REQUEST['request'];

			if ( isset( $this->available_requests[ $request ] ) ) {
				$json = $this->available_requests[ $request ]->do_request();
			}

			if ( ! isset( $json ) ) $this->error( '100', __( 'Invalid API Request', 'woo-admin-licenses' ) );

		} else {
			$this->error( '100', __( 'No API Request Made', 'woo-admin-licenses' ) );
		}

		nocache_headers();
		wp_send_json( $json );
	}

	private function load_available_requests() {
		require_once( WP_PLUGIN_DIR . '/woocommerce-software-add-on/includes/class-wc-software-api-request.php' );
		//generate with expire days
		require( 'requests/class-wc-generate-key-request.php' );
		//with output software_expire
		require( 'requests/class-wc-check-request.php' );
		require( WP_PLUGIN_DIR . '/woocommerce-software-add-on/includes/requests/class-wc-activation-request.php' );
		require( WP_PLUGIN_DIR . '/woocommerce-software-add-on/includes/requests/class-wc-activation-reset-request.php' );
		//only if deactivation is allowed
		require( 'requests/class-wc-deactivation-request.php' );

		$this->available_requests['generate_key'] = new WC_Generate_Key_Request( $this );
		$this->available_requests['check'] = new WC_Check_Request( $this );
		$this->available_requests['activation'] = new WC_Activation_Request( $this );
		//only if activated in settings
		if (get_option( 'mcpat_wal_activation_reset' ) !== 'no')
			$this->available_requests['activation_reset'] = new WC_Activation_Reset_Request( $this );
		if (get_option( 'mcpat_wal_deactivation' ) !== 'no')
			$this->available_requests['deactivation'] = new WC_Deactivation_Request( $this );
	}

	public function error( $code = 100, $debug_message = null, $secret = null, $addtl_data = array() ) {
		switch ( $code ) {
			case '101' :
				$error = array( 'error' => __( 'Invalid License Key', 'woo-admin-licenses' ), 'code' => '101' );
				break;
			case '102' :
				$error = array( 'error' => __( 'Software has been deactivated', 'woo-admin-licenses' ), 'code' => '102' );
				break;
			case '103' :
				$error = array( 'error' => __( 'Exceeded maximum number of activations', 'woo-admin-licenses' ), 'code' => '103' );
				break;
			case '104' :
				$error = array( 'error' => __( 'Invalid Instance ID', 'woo-admin-licenses' ), 'code' => '104' );
				break;
			case '105' :
				$error = array( 'error' => __( 'Invalid security key', 'woo-admin-licenses' ), 'code' => '105' );
				break;
			#1.7.4
			case '403' :
				$error = array( 'error' => __( 'Forbidden', 'woo-admin-licenses' ), 'code' => '403' );
				break;
			case '901' :
				$error = array( 'error' => __( 'This version cannot be deactivated', 'woo-admin-licenses' ), 'code' => '901' );
				break;
			default :
				$error = array( 'error' => __( 'Invalid Request', 'woo-admin-licenses' ), 'code' => '100' );
				break;
		}

		if ( isset( $this->debug ) && $this->debug ) {
			if ( ! isset( $debug_message ) || ! $debug_message ) $debug_message = __( 'No debug information available', 'woo-admin-licenses' );
			$error['additional info'] = $debug_message;
		}

		if ( isset( $addtl_data['secret'] ) ) {
			$secret = $addtl_data['secret'];
			unset( $addtl_data['secret'] );
		}

		foreach ( $addtl_data as $k => $v ) {
			$error[ $k ] = $v;
		}

		$secret = ( $secret ) ? $secret : 'null';
		$error['timestamp'] = time();

		foreach ( $error as $k => $v ) {
			if ( $v === false ) $v = 'false';
			if ( $v === true ) $v = 'true';
			$sigjoined[] = "$k=$v";
		}

		$sig = implode( '&', $sigjoined );
		$sig = 'secret=' . $secret . '&' . $sig;

		if ( !$this->debug ) $sig = md5( $sig );

		$error['sig'] = $sig;
		$json = $error;

		nocache_headers();
		wp_send_json( $json );
	}
}

$GLOBALS['wc_software_api'] = new WC_Software_API(); // run the API
