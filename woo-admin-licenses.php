<?php
/*
 * Plugin Name:       		Woo Admin Licenses
 * Plugin URI:        		https://wordpress.org/plugins/woo-admin-licenses/
 * Description:       		Displays the end-users license keys in the user account and also if required in a table. For showing in a table, simply place a shortcode on a new page. Logged in users can access the license keys and deactivate any software they purchased. Requires WooCommerce Software Add-On
 * Version:           		1.2.2
 * Author:            		MCPat.com
 * Author URI:        		https://www.mcpat.com
 * Developer:         		pwallner
 * Developer URI:     		https://www.mcpat.com
 * Text Domain:       		woo-admin-licenses
 * Domain Path:       		/languages
 * License:           		GPLv2
 * License URI:       		https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 	3.0.0
 * WC tested up to: 		3.2.3
 */

if ( mcpat_wal_is_requirements_met() ) {
	if ( get_option( 'mcpat_wal_enable_variable_products' ) == 'yes' ) {
		add_action( 'woocommerce_product_after_variable_attributes', 'mcpat_wal_variation_settings_fields', 10, 3 );
		add_action( 'woocommerce_save_product_variation', 'mcpat_wal_save_variation_settings_fields', 10, 2 );
		// API hook
		add_action( 'woocommerce_api_software-api', 'mcpat_wal_handle_api_request', 9 );
	}
	add_action( 'woocommerce_product_options_general_product_data', 'mcpat_wal_custom_general_fields' );
	add_action( 'woocommerce_process_product_meta', 'mcpat_wal_custom_general_fields_save' );
	
	
	if ( array_key_exists('wpml_object_id', $GLOBALS['wp_filter']) ) {
		global $sitepress;
		if ( isset( $sitepress )) { // avoids a fatal error with Polylang
			add_filter( 'wcml_js_lock_fields_ids', 'mcpat_wal_js_lock_fields_ids' );
		}
	
	}	
	add_action ('after_setup_theme', 'mcpat_wal_after_setup_theme');
	add_action('admin_menu', 'mcpat_wal_custom_submenu_page');
	add_action( 'admin_init', 'mcpat_wal_options' );

	function mcpat_wal_handle_api_request() {
		if ( get_option( 'mcpat_wal_enable_variable_products' ) !== 'yes' ) {
			return;
		}
		mcpat_wal_remove_filters_for_anonymous_class( 'woocommerce_api_software-api', 'WC_Software', 'handle_api_request', 10 );
		include_once( 'includes/class-wc-software-api.php' );
		die;
	}
	
	function mcpat_wal_js_lock_fields_ids( $ids ){
		// Add locked field IDs
		$ids[] = '_mcpat_wal_deactivated';
		$ids[] = '_mcpat_wal_deactivated1';
		$ids[] = '_mcpat_is_software';
		return $ids;
	}
	function mcpat_wal_custom_general_fields() {
		echo '<script src="' . plugins_url("/js/switchery.min.js", __FILE__) . '"></script>';
		echo '<link rel="stylesheet" href="' . plugins_url("/css/switchery.min.css", __FILE__) .'" />';

		global $woocommerce, $post;
		$product = wc_get_product( $post->ID);
		$orig_ppost = $post->ID;
		if ( array_key_exists('wpml_object_id', $GLOBALS['wp_filter']) ) {
  				global $sitepress;
  				if ( isset( $sitepress )) {
  					$orig_ppost = apply_filters( 'wpml_object_id', $post->ID, get_post_type( $post->ID ), true, $sitepress->get_default_language() );
  				}
  		}
	
  		if ( $post->ID != $orig_ppost) {
  			?><script type="text/javascript">
  					jQuery('input#_is_software').attr('id', '_mcpat_is_software').attr('name', '_mcpat_is_software').prop("readonly", true);
			
  					<?php if (get_post_meta( $orig_ppost, '_is_software', true ) != 'yes') { ?>
  						jQuery('input#_mcpat_is_software').prop("checked", false);//attr('checked', 'checked');// 	
  					<?php } else {	?>
  						jQuery('input#_mcpat_is_software').prop("checked", true);
  					<?php } ?>
  					jQuery(document).ready ( function(){
  							//fix	
  							/*jQuery('input#_software_product_id').remove();
  							jQuery('input#_software_license_key_prefix').remove();
  							jQuery('input#_software_secret_product_key').remove();
  							jQuery('input#_software_version').remove();
  							jQuery('input#_software_activations').remove();
  							jQuery('input#_software_upgradable_product').remove();
  							jQuery('input#_software_upgrade_price').remove();
  							jQuery('textarea#_software_upgrade_license_keys').remove();
  							jQuery('textarea#_software_used_license_keys').remove();*/
  							//not possible
  							jQuery('.software_tab').hide(); 
							if ( !jQuery('input#_mcpat_is_software').is(':checked') ) {
								jQuery('.mcpat_wal_software_data').hide();	
							}
					});
			</script><?php
			echo '<div class="mcpat_wal_software_data">';
		} else {
			echo '<div class="show_if_software">';	
		}
			echo '<div class="options_group show_if_simple"><h1 class="wp-heading-inline" style="margin-left: 10px;">' . __( 'Woo Admin Licenses Options', 'woo-admin-licenses' ) . '</h1>';
			// Custom fields will be created here...
			woocommerce_wp_checkbox( 
					array( 
						'id'            => '_mcpat_wal_deactivated',
						'name' 		    => '_mcpat_wal_showdeactivation_s',
						'label'         => __('Allow Deactivation', 'woo-admin-licenses' ), 
						'desc_tip'      => true,
						'description'   => __( 'Allow your customers to deactivate the software', 'woo-admin-licenses' ),
						'value'         => get_post_meta( $orig_ppost, '_mcpat_wal_showdeactivation', true )

					)
				);
			if ( get_option( 'mcpat_wal_enable_variable_products' ) === 'yes' ) {
				woocommerce_wp_text_input( 
					array(
						'id'          => '_mcpat_wal_deactivated1',
						'name'          => '_mcpat_wal_software_expire_s',
						'label'       => __( 'Expiry days', 'woo-admin-licenses' ),
						'placeholder' => __( 'Unlimited', 'woo-admin-licenses' ),
						'desc_tip'    => 'true',
						'description' => __( 'Number of days when software will expire.', 'woo-admin-licenses' ),
						'value'       => get_post_meta( $orig_ppost, '_mcpat_wal_software_expire', true )
					)
				);
       		}
			echo '</div></div>';
  	}
  	function mcpat_wal_custom_general_fields_save( $post_id ){
    	// Checkbox
    	if ( array_key_exists('wpml_object_id', $GLOBALS['wp_filter']) ) {
  				global $sitepress;
  				if ( isset( $sitepress )) {
  					if ($post_id != apply_filters( 'wpml_object_id', $post_id, get_post_type( $post_id ), true, $sitepress->get_default_language() ))
  						return;
  				}
  		}
		//global $product;
		$_product = wc_get_product( $post_id );
		if ( $_product->is_type( 'variable' ) ) {
			return;
		}
		$woocommerce_checkbox = isset( $_POST['_mcpat_wal_showdeactivation_s'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_mcpat_wal_showdeactivation', $woocommerce_checkbox );
		//expire demoversion
  		$text_field = $_POST['_mcpat_wal_software_expire_s'];
  		$text_field = (! empty( $text_field ) ) ? esc_attr( $text_field ) : '';
  		update_post_meta( $post_id, '_mcpat_wal_software_expire', $text_field );
    }	
	function mcpat_wal_variation_settings_fields( $loop, $variation_data, $variation ){
		// Checkbox
		$lock = false;
		if ( array_key_exists('wpml_object_id', $GLOBALS['wp_filter']) ) {
  				global $sitepress;
  				if ( isset( $sitepress )) {
  					$orig_ppost = apply_filters( 'wpml_object_id', $variation->ID, get_post_type( $variation->ID ), true, $sitepress->get_default_language() );
  					if ( $orig_ppost != $variation->ID ) {
  						$lock = true;
  					}
  				}
  		}

		echo '<table style="height: 1px !important; padding: 0px; margin-top: 5px; display: inline-block;"></table>';//<div class="variable_custom_field show_if_software">';// style="display: inline-block;">';
		echo '<h1 class="wp-heading-inline">' . __( 'Woo Admin Licenses Options', 'woo-admin-licenses' ) . '</h1>';

		if ( $lock ) {
				/*woocommerce_wp_checkbox( 
					array( 
						'id'            => '_mcpat_wal_deactivated',
						'name' 		    => '_mcpat_wal_is_software[' . $variation->ID . ']',
						'wrapper_class' => 'mcpat_wal_software',
						'class'			=> 'js-switch-s' . $variation->ID,
						'label'         => __('Software', 'woo-admin-licenses' ),
						'description'   => __( 'Software add-on hack for variable products. Enable this option if this is software (and you want to manage license keys)', 'woo-admin-licenses' ),
						'desc_tip'      => true,
						'value'         => get_post_meta( $variation->ID, '_is_software', true ),
						'custom_attributes' => array(
										'disabled' => 'disabled'
										)
						)
				);*/
				
				//quick and dirty because wcml hook is not working in variables
				?>
				<div class="variable_custom_field">
				<p class="form-field _mcpat_wal_deactivated_field ">
				<label for="_mcpat_wal_deactivated"><?php _e( 'Software', 'woo-admin-licenses'); ?></label><!--<span class="woocommerce-help-tip"></span>-->
				<input type="checkbox" class="checkbox js-switch-s<?php echo $variation->ID; ?>" name="_mcpat_wal_is_software[<?php echo $variation->ID; ?>]" id="_mcpat_wal_is_software" value="yes" <?php 
					if ( get_post_meta( $orig_ppost, '_is_software', true ) == 'yes') {
						echo 'checked'; 
					}
				?> disabled="disabled"> 
				<script type="text/javascript">
						// Default
						var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch-s<?php echo $variation->ID; ?>'));
						elems.forEach(function(html) {
							var switchery = new Switchery(html, { size: 'small' });
						});
				</script>
				<?php		
				echo '<img src="' . WCML_PLUGIN_URL . '/res/images/locked.png" alt="' .
					__( 'This field is locked for editing because WPML will copy its value from the original language.', 'woocommerce-multilingual' ) .
					'" title="' . __( 'This field is locked for editing because WPML will copy its value from the original language.', 'woocommerce-multilingual' ) .
					'" style="position: relative; left: 2px; top: 5px; display: inline;">';	
				?>
				</p></div><?php
				
				
		} else { 
			woocommerce_wp_checkbox( 
       			array( 
       				'id'            => '_mcpat_wal_is_software', 
       				'name' 		    => '_mcpat_wal_is_software[' . $variation->ID . ']',
       				'wrapper_class' => 'mcpat_wal_software', 
       				'class'			=> 'js-switch-s' . $variation->ID,
       				'label'         => __('Software', 'woo-admin-licenses' ),
       				'description'   => __( 'Software add-on hack for variable products. Enable this option if this is software (and you want to manage license keys)', 'woo-admin-licenses' ),
       				'desc_tip'      => true,
       				'value'         => get_post_meta( $variation->ID, '_is_software', true )
       				)
       		);
       		?>
       		<script type="text/javascript">
						// Default
						var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch-s<?php echo $variation->ID; ?>'));
						elems.forEach(function(html) {
							var switchery = new Switchery(html, { size: 'small' });
						});
						var changeCheckbox<?php echo $variation->ID; ?> = document.querySelector('.js-switch-s<?php echo $variation->ID; ?>');
						changeCheckbox<?php echo $variation->ID; ?>.onchange = function($) {
							if (changeCheckbox<?php echo $variation->ID; ?>.checked) {
								jQuery('.mcpat_wal_software_data<?php echo $variation->ID; ?>').show();
							} else {
								jQuery('.mcpat_wal_software_data<?php echo $variation->ID; ?>').hide();
							}
						};
						jQuery(document).ready ( function(){
							if (!changeCheckbox<?php echo $variation->ID; ?>.checked) {
								jQuery('.mcpat_wal_software_data<?php echo $variation->ID; ?>').hide();
							}
						});
			</script>
			<div class="woocommerce_options_panel mcpat_wal_software_data<?php echo $variation->ID; ?>">
			<div class="options_group">
			<?php
			woocommerce_wp_checkbox( 
       			array( 
       				'id'            => '_mcpat_wal_deactivated', 
       				'name' 		    => '_mcpat_wal_showdeactivation[' . $variation->ID . ']',
       				'label'         => __('Allow Deactivation', 'woo-admin-licenses' ),
       				'description'   => __( 'Allow your customers to deactivate the software', 'woo-admin-licenses' ),
       				'desc_tip'      => true,
       				'value'         => get_post_meta( $variation->ID, '_mcpat_wal_showdeactivation', true )
       				)
       		);
       		woocommerce_wp_text_input( 
       			array( 
       				'id'          => '_mcpat_wal_software_product_id[' . $variation->ID . ']', 
       				'label'       => __( 'Product ID', 'woo-admin-licenses' ), 
       				'placeholder' => __( 'e.g. SOFTWARE1', 'woo-admin-licenses' ),
       				'desc_tip'    => 'true',
       				'description' => __( 'This ID is used for the license key API.', 'woo-admin-licenses' ),
       				'value'       => get_post_meta( $variation->ID, '_software_product_id', true )
       			)
       		);
       		woocommerce_wp_text_input( 
       			array( 
       				'id'          => '_mcpat_wal_software_license_key_prefix[' . $variation->ID . ']', 
       				'label'       => __( 'License key prefix', 'woo-admin-licenses' ), 
       				'placeholder' => __( 'N/A', 'woo-admin-licenses' ),
       				'desc_tip'    => 'true',
       				'description' => __( 'Optional prefix for generated license keys.', 'woo-admin-licenses' ),
       				'value'       => get_post_meta( $variation->ID, '_software_license_key_prefix', true )
       			)
       		);
       		woocommerce_wp_text_input( 
       			array( 
       				'id'          => '_mcpat_wal_software_secret_product_key[' . $variation->ID . ']', 
       				'label'       => __( 'Secret key', 'woo-admin-licenses' ), 
       				'placeholder' => __( 'any random string', 'woo-admin-licenses' ),
       				'desc_tip'    => 'true',
       				'description' => __( 'Secret product key to use for API.', 'woo-admin-licenses' ),
       				'value'       => get_post_meta( $variation->ID, '_software_secret_product_key', true )
       			)
       		);
       		woocommerce_wp_text_input( 
       			array( 
       				'id'          => '_mcpat_wal_software_version[' . $variation->ID . ']', 
       				'label'       => __( 'Version', 'woo-admin-licenses' ), 
       				'placeholder' => __( 'e.g. 1.0', 'woo-admin-licenses' ),
       				'desc_tip'    => 'true',
       				'description' => __( 'Version number for the software.', 'woo-admin-licenses' ),
       				'value'       => get_post_meta( $variation->ID, '_software_version', true )
       			)
       		);
       		woocommerce_wp_text_input( 
       			array( 
       				'id'          => '_mcpat_wal_software_activations[' . $variation->ID . ']', 
       				'label'       => __( 'Activation limit', 'woo-admin-licenses' ), 
       				'placeholder' => __( 'Unlimited', 'woo-admin-licenses' ),
       				'desc_tip'    => 'true',
       				'description' => __( 'Amount of activations possible per license key.', 'woo-admin-licenses' ),
       				'value'       => get_post_meta( $variation->ID, '_software_activations', true )
       			)
       		);
       		woocommerce_wp_text_input( 
       			array( 
       				'id'          => '_mcpat_wal_software_expire[' . $variation->ID . ']', 
       				'label'       => __( 'Expiry days', 'woo-admin-licenses' ), 
       				'placeholder' => __( 'Unlimited', 'woo-admin-licenses' ),
       				'desc_tip'    => 'true',
       				'description' => __( 'Number of days when software will expire.', 'woo-admin-licenses' ),
       				'value'       => get_post_meta( $variation->ID, '_mcpat_wal_software_expire', true )
       			)
       		);
       		echo '</div><div class="options_group" style="display: none;">';
       		woocommerce_wp_text_input( 
       			array( 
       				'id'          => '_mcpat_wal_software_upgradable_product[' . $variation->ID . ']', 
       				'label'       => __( 'Upgradable product', 'woo-admin-licenses' ),
       				'placeholder' => '', 
       				'desc_tip'    => 'true',
       				'description' => __( 'Name of the product which can be upgraded.', 'woo-admin-licenses' ),
       				'value'       => get_post_meta( $variation->ID, '_software_upgradable_product', true )
       			)
       		);
       		woocommerce_wp_text_input( 
       			array( 
       				'id'          => '_mcpat_wal_software_upgrade_price[' . $variation->ID . ']', 
       				'label'       => sprintf(__( 'Upgrade Price (%s)', 'woo-admin-licenses' ), get_woocommerce_currency_symbol( '' )),
       				'placeholder' => __( 'e.g. 10.99', 'woo-admin-licenses' ),
       				'desc_tip'    => 'true',
       				'description' => __( 'Users with a valid upgrade key will be able to pay this amount.', 'woo-admin-licenses' ),
       				'value'       => get_post_meta( $variation->ID, '_software_upgrade_price', true )
       			)
       		);
       		// Textarea
       		woocommerce_wp_textarea_input( 
       			array( 
       				'id'          => '_mcpat_wal_software_upgrade_license_keys[' . $variation->ID . ']', 
       				'label'       => __( 'Valid upgrade keys', 'woo-admin-licenses' ), 
       				'placeholder' => '', 
       				'desc_tip'    => 'true',
       				'description' => __( 'A comma separated list of keys which can be upgraded.', 'woo-admin-licenses' ),
       				'value'       => get_post_meta( $variation->ID, '_software_upgrade_license_keys', true )
       			)
       		);
       		woocommerce_wp_textarea_input( 
       			array( 
       				'id'          => '_mcpat_wal_software_used_license_keys[' . $variation->ID . ']', 
       				'label'       => __( 'Used upgrade keys', 'woo-admin-licenses' ), 
       				'placeholder' => '', 
       				'desc_tip'    => 'true',
       				'description' => __( 'A comma separated list of keys which have been used for an upgrade already.', 'woo-admin-licenses' ),
       				'value'       => get_post_meta( $variation->ID, '_software_used_license_keys', true )
       			)
       		);
       		echo '</div></div>';
		}
	}
    function mcpat_wal_save_variation_settings_fields( $post_id ) {
    	if ( array_key_exists('wpml_object_id', $GLOBALS['wp_filter']) ) {
  				global $sitepress;
  				if ( isset( $sitepress )) {
  					if ($post_id != apply_filters( 'wpml_object_id', $post_id, get_post_type( $post_id ), true, $sitepress->get_default_language() ))
						return;
  				}
  		}
  		// Checkbox
	  	$checkbox = isset( $_POST['_mcpat_wal_showdeactivation'][ $post_id ] ) ? 'yes' : 'no';
  		update_post_meta( $post_id, '_mcpat_wal_showdeactivation', $checkbox );
  		$checkbox = isset( $_POST['_mcpat_wal_is_software'][ $post_id ] ) ? 'yes' : 'no';
  		update_post_meta( $post_id, '_is_software', $checkbox );

  		// Text Field
  		$text_field = $_POST['_mcpat_wal_software_product_id'][ $post_id ];
  		$text_field = (! empty( $text_field ) ) ? esc_attr( $text_field ) : '';
  		update_post_meta( $post_id, '_software_product_id', $text_field  );

  		$text_field = $_POST['_mcpat_wal_software_license_key_prefix'][ $post_id ];
  		$text_field = (! empty( $text_field ) ) ? esc_attr( $text_field ) : '';
  		update_post_meta( $post_id, '_software_license_key_prefix', $text_field );

  		$text_field = $_POST['_mcpat_wal_software_secret_product_key'][ $post_id ];
  		$text_field = (! empty( $text_field ) ) ? esc_attr( $text_field ) : '';
  		update_post_meta( $post_id, '_software_secret_product_key', $text_field );

  		$text_field = $_POST['_mcpat_wal_software_version'][ $post_id ];
  		$text_field = (! empty( $text_field ) ) ? esc_attr( $text_field ) : '';
  		update_post_meta( $post_id, '_software_version', $text_field );

  		$text_field = $_POST['_mcpat_wal_software_activations'][ $post_id ];
  		$text_field = (! empty( $text_field ) ) ? esc_attr( $text_field ) : '';
  		update_post_meta( $post_id, '_software_activations', $text_field );

  		$text_field = $_POST['_mcpat_wal_software_upgradable_product'][ $post_id ];
  		$text_field = (! empty( $text_field ) ) ? esc_attr( $text_field ) : '';
  		update_post_meta( $post_id, '_software_upgradable_product', $text_field );

  		$text_field = $_POST['_mcpat_wal_software_upgrade_price'][ $post_id ];
  		$text_field = (! empty( $text_field ) ) ? esc_attr( $text_field ) : '';
  		update_post_meta( $post_id, '_software_upgrade_price', $text_field );

  		//expire demoversion
  		$text_field = $_POST['_mcpat_wal_software_expire'][ $post_id ];
  		$text_field = (! empty( $text_field ) ) ? esc_attr( $text_field ) : '';
  		update_post_meta( $post_id, '_mcpat_wal_software_expire', $text_field );

  		// Textarea
  		$textarea = $_POST['_mcpat_wal_software_upgrade_license_keys'][ $post_id ];
  		$textarea = (! empty( $textarea ) ) ? esc_attr( $textarea ) : '';
  		update_post_meta( $post_id, '_software_upgrade_license_keys', $textarea );

  		$textarea = $_POST['_mcpat_wal_software_used_license_keys'][ $post_id ];
  		$textarea = (! empty( $textarea ) ) ? esc_attr( $textarea ) : '';
  		update_post_meta( $post_id, '_software_used_license_keys', $textarea );
				
  	}

	function mcpat_wal_options() {
		register_setting( 'mcpat_woo_admin_licenses', 'mcpat_wal_show_variables_account');
		register_setting( 'mcpat_woo_admin_licenses', 'mcpat_wal_enable_deactivation_freeware');
		register_setting( 'mcpat_woo_admin_licenses', 'mcpat_wal_enable_deactivation_software');
		register_setting( 'mcpat_woo_admin_licenses', 'mcpat_wal_enable_licenses_account');
		register_setting( 'mcpat_woo_admin_licenses', 'mcpat_wal_enable_variable_products');
		register_setting( 'mcpat_woo_admin_licenses', 'mcpat_wal_activation_reset');
		register_setting( 'mcpat_woo_admin_licenses', 'mcpat_wal_deactivation');
	}
	function mcpat_wal_settings_link( $links ) {
		$url = get_admin_url() . 'admin.php?page=mcpat-wal-submenu-page';
		$settings_link = '<a href="' . $url . '">' . __('Settings', 'woo-admin-licenses') . '</a>';
		$premium_link = '<a href="https://www.mcpat.com" target="_blank"><strong style="color: #11967A; display: inline;">' . __('Upgrade To Premium', 'woo-admin-licenses') . '</strong></a>';
		array_unshift( $links, $settings_link );//, $premium_link );
		return $links;
	}
	function mcpat_wal_after_setup_theme() {
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'mcpat_wal_settings_link');
	}
	function mcpat_wal_custom_submenu_page() {
		add_submenu_page( 'woocommerce' , 'WooAdminLicenses', 'WooAdminLicenses', 'manage_options', 'mcpat-wal-submenu-page', 'mcpat_wal_page_callback');
	}
	function mcpat_wal_page_callback() {
			if ( ! empty( $_POST ) && check_admin_referer( 'mcpat_wal_admin_action', 'mcpat_wal_nonce_field' ) ) {
				if ( isset( $_POST['submitted'] ) ) {
					//$this->options['custom_show_post_page'] = ! isset( $_POST['custom_show_post_page'] ) ? '' : $_POST['custom_show_post_page'];
					//update_option( $this->key, $this->options );
					
					update_option('mcpat_wal_show_variables_account', isset( $_POST['mcpat_wal_show_variables_account'] ) ? 'yes' : 'no');
					update_option('mcpat_wal_enable_deactivation_freeware', isset( $_POST['mcpat_wal_enable_deactivation_freeware'] ) ? 'yes' : 'no');
					update_option('mcpat_wal_enable_deactivation_software', isset( $_POST['mcpat_wal_enable_deactivation_software'] ) ? 'yes' : 'no');
					update_option('mcpat_wal_enable_licenses_account', isset( $_POST['mcpat_wal_enable_licenses_account'] ) ? 'yes' : 'no');
					update_option('mcpat_wal_enable_variable_products', isset( $_POST['mcpat_wal_enable_variable_products'] ) ? 'yes' : 'no');
					update_option('mcpat_wal_activation_reset', isset( $_POST['mcpat_wal_activation_reset'] ) ? 'yes' : 'no');
					update_option('mcpat_wal_deactivation', isset( $_POST['mcpat_wal_deactivation'] ) ? 'yes' : 'no');
					add_settings_error('mcpat_woo_admin_licenses', 'mcpat_wal_show_variables_account', __('Settings saved.', 'woo-admin-licenses'), 'updated');	
					
					if(isset( $_POST['mcpat_wal_enable_variable_products'] )) {
						global $wpdb;
						$myExpire = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}woocommerce_software_licenses");
						//Add column if not present.
						if(!isset($myExpire->software_expire)){
							$wpdb->query("ALTER TABLE {$wpdb->prefix}woocommerce_software_licenses ADD software_expire BIGINT(20) NULL DEFAULT NULL");
						}
					}
   				}
			}
			$checked_licenses_account ='';
			if(get_option( 'mcpat_wal_enable_licenses_account' ) !== 'no')
				$checked_licenses_account = 'checked="checked"';
			
			$checked_variables_account = '';
			if(get_option( 'mcpat_wal_show_variables_account' ) === 'yes')
				$checked_variables_account = 'checked="checked"';
			
			$checked_freeware = '';
			if(get_option( 'mcpat_wal_enable_deactivation_freeware' ) === 'yes')
				$checked_freeware = 'checked="checked"';
			$checked_software = '';
			if(get_option( 'mcpat_wal_enable_deactivation_software' ) === 'yes')
				$checked_software = 'checked="checked"';
			$checked_variable_products = '';
			if(get_option( 'mcpat_wal_enable_variable_products' ) === 'yes')
				$checked_variable_products = 'checked="checked"';
			$checked_activation_reset = '';
			if(get_option( 'mcpat_wal_activation_reset' ) !== 'no')
				$checked_activation_reset = 'checked="checked"';
			$checked_deactivation = '';
			if(get_option( 'mcpat_wal_deactivation' ) !== 'no')
				$checked_deactivation = 'checked="checked"';
			
			$nonce = wp_create_nonce( 'woo-admin-licenses' );
			?>
			<h1 class="wp-heading-inline"><?php _e( 'Woo Admin Licenses Options', 'woo-admin-licenses' ); ?></h1>
			<hr class="wp-header-end">
			<?php settings_errors(); ?>
			<h2 style="height: 5px;margin: 0px;"></h2>
			<link rel="stylesheet" href="<?php echo plugins_url("/css/switchery.min.css", __FILE__) ?>" />
			<link rel="stylesheet" href="<?php echo plugins_url("/css/mcpat_wal.css", __FILE__) ?>" />
			<script src="<?php echo plugins_url("/js/switchery.min.js", __FILE__) ?>"></script>
			<table width="100%">
			<tr>
				<td width="70%" style="vertical-align: top; background: none repeat scroll 0 0 #fafafa; border-radius: 10px; padding: 15px;">
					<form method="post" action="admin.php?page=mcpat-wal-submenu-page">
					<?php settings_fields( 'mcpat_woo_admin_licenses' ); ?>
					<?php do_settings_sections( 'mcpat_woo_admin_licenses' ); ?>
					<table class="form-table" >
							<tbody>
								<tr>
									<td style="font-weight: 600;"><?php _e( 'Show “license keys table” in the account page', 'woo-admin-licenses'); ?></td>
									<td><input class="js-switch-s js-check-change" name="mcpat_wal_enable_licenses_account" id="mcpat_wal_enable_licenses_account" value="yes" <?php echo $checked_licenses_account; ?> type="checkbox"></td>
								</tr>
								<tr>
									<td style="font-weight: 600;"><div class="rotation"><?php _e( 'Show “variables” at “license keys table” in the account page', 'woo-admin-licenses'); ?></div></td>
									<td><input class="js-dynamic-state" name="mcpat_wal_show_variables_account" id="mcpat_wal_show_variables_account" value="yes" <?php echo $checked_variables_account; ?> type="checkbox"></td>
								</tr>
								<tr>
									<td style="font-weight: 600;"><?php _e( "Show “Deactivation” at freeware", 'woo-admin-licenses'); ?></td>
									<td><input class="js-switch-s" name="mcpat_wal_enable_deactivation_freeware" id="mcpat_wal_enable_deactivation_freeware" value="yes" <?php echo $checked_freeware; ?> type="checkbox"></td>
								</tr>
								<tr>
									<td style="font-weight: 600;"><?php _e( "Show “Deactivation” at paid software", 'woo-admin-licenses'); ?></td>
									<td><input class="js-switch-s" name="mcpat_wal_enable_deactivation_software" id="mcpat_wal_enable_deactivation_software" value="yes" <?php echo $checked_software; ?> type="checkbox"></td>
								</tr>
								<tr>
									<td style="font-weight: 600;"><?php _e( "Use Software Add-On Hacks - see *)", 'woo-admin-licenses'); ?></td>
									<td><input class="js-switch-s js-check-change2" name="mcpat_wal_enable_variable_products" id="mcpat_wal_enable_variable_products" value="yes" <?php echo $checked_variable_products; ?> type="checkbox"></td>
								</tr>
								<tr>
									<td style="font-weight: 600;"><div class="rotation2"><?php _e( "Allow “Activation reset” at Software Add-On", 'woo-admin-licenses'); ?></div></td>
									<td><input class="js-dynamic-state2" name="mcpat_wal_activation_reset" id="mcpat_wal_activation_reset" value="yes" <?php echo $checked_activation_reset; ?> type="checkbox"></td>
								</tr>
								<tr>
									<td style="font-weight: 600;"><div class="rotation2"><?php _e( "Allow “Deactivation” at Software Add-On", 'woo-admin-licenses'); ?></div></td>
									<td><input class="js-dynamic-state3" name="mcpat_wal_deactivation" id="mcpat_wal_deactivation" value="yes" <?php echo $checked_deactivation; ?> type="checkbox"></td>
								</tr>
								<tr>
									<td style="font-weight: 300; color: red; padding-left: 20px;"><?php _e( "*) Overrides functions at simple and variable products and API functions: “deactivation”, “activation_reset” and “check”", 'woo-admin-licenses'); ?></td>
									<td></td>
								</tr>
								<tr>
									<td colspan=2">
										<input type="hidden" name="submitted" value="1" /> 
										<?php wp_nonce_field( 'mcpat_wal_admin_action','mcpat_wal_nonce_field' ); ?>
										<?php submit_button(); ?>
									</td>
								</tr>
							</tbody>
					</table>									
					</form>
					<script type="text/javascript">
						// Default
						var elem = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
						elem.forEach(function(html) {
							var switchery = new Switchery(html);
						});
						var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch-s'));
						elems.forEach(function(html) {
							var switchery = new Switchery(html, { size: 'small' });
						});
						var elem2 = document.querySelector('.js-dynamic-state');
						var switcheri = new Switchery(elem2, { size: 'small' });
						var changeCheckbox = document.querySelector('.js-check-change');
						changeCheckbox.onchange = function($) {
							if (changeCheckbox.checked) {
								jQuery('.rotation').css('color', '');
								switcheri.enable();
							} else {
								jQuery('.rotation').css('color', 'lightgray');
								elem2.checked = false;
								switcheri.handleOnchange(elem2.checked);
								switcheri.disable();
							}
						};
						var elem3 = document.querySelector('.js-dynamic-state2');
						var elem4 = document.querySelector('.js-dynamic-state3');
						var switcheri2 = new Switchery(elem3, { size: 'small' });
						var switcheri3 = new Switchery(elem4, { size: 'small' });
						var changeCheckbox2 = document.querySelector('.js-check-change2');
						changeCheckbox2.onchange = function($) {
							if (changeCheckbox2.checked) {
								jQuery('.rotation2').css('color', '');
								switcheri2.enable();
								switcheri3.enable();
							} else {
								jQuery('.rotation2').css('color', 'lightgray');
								elem3.checked = false;
								elem4.checked = false;
								switcheri2.handleOnchange(elem3.checked);
								switcheri2.disable();
								switcheri3.handleOnchange(elem4.checked);
								switcheri3.disable();
							}
						};
						jQuery(document).ready ( function(){
							if (!changeCheckbox.checked) {
								jQuery('.rotation').css('color', 'lightgray');
								switcheri.disable();
							}
							if (!changeCheckbox2.checked) {
								jQuery('.rotation2').css('color', 'lightgray');
								switcheri2.disable();
								switcheri3.disable();
							}
						});
					</script>
				</td>
				
				<td width="30%" style="padding:0px 8px;" valign="top">
					<div style="width:100%;">
                            <div style="background: none repeat scroll 0 0 #fafafa; border-radius: 10px; padding: 15px;">
                                <h2 style="text-align:left; line-height: 28px;">   
                                    <!--<a href="https://www.mcpat.com" target="_blank">MCPat.com</a> has following plugins for you:-->
                                    <?php echo sprintf( __( '<a href="%s" target="_blank">%s</a> has following plugins for you:', 'woo-admin-licenses' ), 'https://www.mcpat.com', 'MCPat.com' );?>
                                </h2>
                                <hr>

                                <div>
                                    <div style="font-weight: bold;font-size: 20px; margin-top: 10px;">
                                        Disable Downloadable Repeat Purchase – WooCommerce + WPML
                                    </div>
                                    <div style="margin-top:10px; margin-bottom: 8px;">
                                        <?php _e( 'This WooCommerce plugin prevents a user from being able to purchase a downloadable product that they already own. In place of the “Add to Basket” button on the product page, a message will display informing the user they already own the item, and links to download the linked files are provided there.', 'woo-admin-licenses' ); ?>
                                    </div>
                                    <div style="text-align: center;">
                                        <a href="https://de.wordpress.org/plugins/disable-downloadable-repeat-purchase/" target="_blank" class="mcpat_btn mcpat_btn-success" style="width:90%; margin-top:5px; margin-bottom: 5px; text-decoration: none;">Download</a>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top:15px; background: none repeat scroll 0 0 #fafafa; border-radius: 10px; padding: 15px;">
                                <span>
                                    <?php _e( 'Your donation helps us make great products', 'woo-admin-licenses' ); ?>
                                </span><br><br>
                                <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=p%2er%2ewallner%40gmail%2ecom&lc=AT&item_name=mcpat&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" target="_blank">
                                    <div align=center><img src="<?php echo plugins_url('/images/donate.png', __FILE__); ?>"></div>
                                </a>
                            </div>
                        </div>
				</td>			
			</tr>
			</table>
            <?php
}

// Languages localization.
function mcpat_wal_admin_licenses_cfm() {
  load_plugin_textdomain('woo-admin-licenses', FALSE, basename(dirname(__FILE__)) . '/languages');
}
add_action('init', 'mcpat_wal_admin_licenses_cfm');

function mcpat_wal_custom_endpoints() {
	add_rewrite_endpoint( 'mcpat-wal-lost-license', EP_ROOT | EP_PAGES );
}
if(get_option( 'mcpat_wal_enable_licenses_account' ) !== 'no') {
	add_action( 'init', 'mcpat_wal_custom_endpoints' );
}

function mcpat_wal_custom_query_vars( $vars ) {
	$vars[] = 'mcpat-wal-lost-license';
	return $vars;
}
if(get_option( 'mcpat_wal_enable_licenses_account' ) !== 'no') {
	add_filter( 'query_vars', 'mcpat_wal_custom_query_vars', 0 );
}

function mcpat_wal_custom_my_account_menu_items( $items ) {
	// Remove the logout menu item.
	$logout = $items['customer-logout'];
	unset( $items['customer-logout'] );

	// Insert your custom endpoint.
	$items['mcpat-wal-lost-license'] = __( 'License Keys', 'woo-admin-licenses' );

	// Insert back the logout item.
	$items['customer-logout'] = $logout;

	return $items;
}
if(get_option( 'mcpat_wal_enable_licenses_account' ) !== 'no') {
	add_filter( 'woocommerce_account_menu_items', 'mcpat_wal_custom_my_account_menu_items' );
}

function mcpat_wal_custom_flush_rewrite_rules() {
	add_rewrite_endpoint( 'mcpat-wal-lost-license', EP_ROOT | EP_PAGES );
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'mcpat_wal_custom_flush_rewrite_rules' );
register_deactivation_hook( __FILE__, 'mcpat_wal_custom_flush_rewrite_rules' );


add_shortcode('woo_admin_licenses', 'mcpat_wal_custom_endpoint_content');

function mcpat_wal_custom_endpoint_content($atts) {
	//echo do_shortcode('[woocommerce_software_lost_license]'); //old screen

	if ( ! is_user_logged_in() ) {
    		return;
	}	

	$variables = isset( $atts['variables'] )? $atts['variables'] : '';

	global $woocommerce, $wpdb, $post, $wp_query;

	$current_user = wp_get_current_user();
	
	//new and better
	$is_endpoint = isset( $wp_query->query_vars['mcpat-wal-lost-license'] );
	//paypal express with another email
	$customer_orders = get_posts( apply_filters( 'woocommerce_my_account_my_orders_query', array(
		'numberposts' => -1,
		'meta_key'    => '_customer_user',
		'meta_value'  => get_current_user_id(),
		'post_type'   => 'shop_order',
		'post_status' => 'wc-completed',
	)));
	if ( $customer_orders ) {
		foreach ( $customer_orders as $customer_order ) {
			$order = wc_get_order( $customer_order );
			$bestellungen[] = $order->get_order_number();
		}
		$licence_keys = $wpdb->get_results( " SELECT * FROM {$wpdb->prefix}woocommerce_software_licenses WHERE order_id IN (" . implode(",", $bestellungen) . ")");
		if ( $licence_keys ) {
			global $wp;
			$meineurl = add_query_arg( $_SERVER['QUERY_STRING'], '', home_url( $wp->request));
			?>
			<table>
				<thead>
					<tr>
						<th class="product" align="center"><span class="nobr" style="text-align:center;"><?php _e( 'Product', 'woo-admin-licenses' ); ?></span></th>
						<th class="licence-key" align="center" style="min-width: 380px;"><span class="nobr" style="text-align:center;"><?php _e( 'Key', 'woo-admin-licenses' ); ?></span></th>
						<th class="software-version" align="center"><span class="nobr" style="text-align:center;"><?php _e( 'Version', 'woo-admin-licenses' ); ?></span></th>
						<?php if ( $variables == 'yes' || (get_option( 'mcpat_wal_show_variables_account' ) === 'yes' && $is_endpoint) ) { ?><th class="licence-type" align="center" style="min-width: 185px;"><span class="nobr" style="text-align:center;"><?php _e( 'Type', 'woo-admin-licenses' ); ?></span></th><?php } ?>
						<th class="activations-remaining" align="center" style="width: 190px;"><span class="nobr" style="text-align:center;"><?php _e( 'Activations remaining', 'woo-admin-licenses' ); ?></span></th>
						<?php if ( get_option( 'mcpat_wal_enable_variable_products' ) == 'yes' ) {?>
							<th class="software-expire" align="center" style="min-width: 170px;"><span class="nobr" style="text-align:center;"><?php _e('Expiry date', 'woo-admin-licenses'); ?></span></th>
						<?php }?>
					</tr>
				</thead>
				<tbody>
				<?php
				//revised function acc. software add-on
				$multi = false;
				if ( array_key_exists('wpml_object_id', $GLOBALS['wp_filter']) ) {
					global $sitepress;
					if ( isset( $sitepress )) {
						$multi = true;
					}
				}	
				foreach ( $licence_keys as $lic_key) {
					global $wpdb;
					$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_software_product_id' AND meta_value = %s LIMIT 1", $lic_key->software_product_id ) );
					$item = wc_get_product($post_id);
					if ($multi) {
						$orig_ppost = apply_filters( 'wpml_object_id', $item->get_id(), get_post_type( $item->get_id() ), true, ICL_LANGUAGE_CODE );
						$link = get_permalink( $orig_ppost );
					} else {
						$link = get_permalink( $item->get_id() );
					}
					$preis = get_post_meta( $item->get_id(), '_regular_price', true);
					$showdeaktivate = get_post_meta( $item->get_id(), '_mcpat_wal_showdeactivation', true );		
					if ( $item->get_parent_id() > 0 ) {
						$parent_id = $item->get_parent_id();
						if ($multi) {
							$parent_id = apply_filters( 'wpml_object_id', $parent_id, get_post_type( $parent_id ), true, ICL_LANGUAGE_CODE );
							$variation = wc_get_product( $orig_ppost );
							$variantionen = $variation->get_attributes();
							$varstopel = array_shift($variantionen);
							foreach ($variantionen as $var) {
								$varstopel .= ' - ' . $var;
							}
							//$variation = new WC_Product_Variation($variation_id);
							//$title_slug = current($variation->get_variation_attributes());
							//$results = $wpdb->get_results("SELECT * FROM wp_terms WHERE slug = '{$title_slug}'", ARRAY_A);
							//$variation_title = $results[0]['name'];
						} else {
							$variation = wc_get_product( $item->get_id() );
							$variantionen = $variation->get_attributes();
							$varstopel = array_shift($variantionen);
							foreach ($variantionen as $var) {
								$varstopel .= ' - ' . $var;
							}
						}
						$linktitel = get_the_title( $parent_id );		
					} else { //standard product
						if ($multi) {
							$linktitel = get_the_title( $orig_ppost );
						} else {
							$linktitel = get_the_title( $item->get_id() );
						}			
					}
					//echo '<script>console.log("' . $item->get_id() . ' - ' . $linktitel . ' - ' . $preis . '")</script>';
					//general fields
					$aktivierungen = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_software_activations as activations WHERE key_id = {$lic_key->key_id} AND activation_active=1" );
					$rest = $lic_key->activations_limit - count( $aktivierungen );
					?><tr class="licences">
					<td class="product" align="center"><a href="<?php echo $link; ?>"><?php echo $linktitel; ?></a></td>
					<td class="licence-key" align="center"><?php 
						echo $lic_key->license_key; 
						if ($current_user->user_email != $lic_key->activation_email) {
							//echo "<br>(Activate with: " . $lic_key->activation_email . ")";
							?><img style="margin-left: 5px;" width="16px" src="<?php echo plugins_url('/images/help.png', __FILE__);?>" alt='<?php echo sprintf( __( 'Activate with "%s"', 'woo-admin-licenses' ), $lic_key->activation_email); ?>' 
								title='<?php echo sprintf( __( 'Activate with "%s"', 'woo-admin-licenses' ), $lic_key->activation_email); ?>'><?php	
						}
					?></td>
					<td class="product-version" align="center"><?php echo $lic_key->software_version; ?></td><?php
					if ( $variables == 'yes' || (get_option( 'mcpat_wal_show_variables_account' ) === 'yes' && $is_endpoint) ) { 
						?><td class="licence-type" align="center"><?php
						if ( $item->get_parent_id() > 0 ) {
							echo '<strong>' . $varstopel . '</strong>';
						}
						?></td><?php 
					}
					?><td class="activations-remaining" align="center"><span class="<?php echo $lic_key->license_key; ?>"><?php echo $rest; ?></span></td><?php
					if ( get_option( 'mcpat_wal_enable_variable_products' ) == 'yes' ) {?>
						<td>&nbsp;</td><?php 
					}
					echo '</tr>';
					if ( count( $aktivierungen ) >= 1 ) {
						foreach ( $aktivierungen as $aktivierung ) {
							?>
							<tr class="activation <?php echo ( $aktivierung->activation_active ) ?  'active' : 'not-active'; ?>">
							<td colspan="2" align="center" style="text-align:center;"><?php _e( 'Activated Platform: ', 'woo-admin-licenses' ); echo $aktivierung->activation_platform; ?></td>
							<td align="left" style="padding-left: 10px;" colspan="<?php if ( $variables == 'yes' || (get_option( 'mcpat_wal_show_variables_account' ) === 'yes' && $is_endpoint) ) { echo '3'; } else { echo '2'; } ?>">
							<?php if ( $aktivierung->activation_active == 1 ) {
								if (($preis == 0 && get_option( 'mcpat_wal_enable_deactivation_freeware' ) === 'yes') || ($preis != 0 && get_option( 'mcpat_wal_enable_deactivation_software' ) === 'yes') || $showdeaktivate === 'yes') {
									$arr_params = array( 'platform' => $aktivierung->activation_platform, 'softname' => $linktitel, 'deactivate' => $lic_key->software_product_id, 'email' => urlencode( $lic_key->activation_email ), 'instance' => urlencode( $aktivierung->instance ), 'licence_key' => $lic_key->license_key );
									?>
									<input type="submit" style="margin: 1px 0px 1px;min-width: 10px;min-height: 10px;padding: 5px !important;" class="button delete_class" value="<?php _e( 'Deactivate', 'woo-admin-licenses' ); ?>" deactivate="<?php echo $lic_key->software_product_id; ?>" platform="<?php echo $aktivierung->activation_platform;?>" softname="<?php echo $linktitel;?>" email="<?php echo $lic_key->activation_email;?>" instance="<?php echo urlencode( $aktivierung->instance );?>" licence_key="<?php echo $lic_key->license_key;?>">
									<span class="spinner<?php echo urlencode( $aktivierung->instance ); ?>"></span>
									<?php } else { 
										_e( 'This version cannot be deactivated', 'woo-admin-licenses' );
								}?>
							<?php } ?>
							</td>
							<?php
							if ( get_option( 'mcpat_wal_enable_variable_products' ) == 'yes' ) {
								if($lic_key->software_expire != 0) {
									$date = new DateTime($aktivierung->activation_time);
									$date->add(new DateInterval('P' . $lic_key->software_expire .'D'));
									$expire = $date->format('Y-m-d');
									$today = strtotime(date('Y-m-d'));
									if(strtotime($date->format('Y-m-d')) < $today){
										$expire = __('Software expired', 'woo-admin-licenses');
									}
								} else {
									$expire = '&infin;';
								}
								?><td class="software-expire" align="center"><?php echo $expire; ?></td>
								</tr><?php
							}
						//echo "<tr><td>".$lic_key->activation_email ."</td><td>".$lic_key->activation_email."</td><td><input type='submit' style=\"margin: 1px 0px 1px;min-width: 10px;min-height: 10px;padding: 5px !important;\" class='button delete_class' value='loesch' data-id=" . $lic_key->license_key . "></td></tr>";
						}
					}
				}
				?>
				</tbody>
			</table>
			
			<?php  					
				echo '<div class="woocommerce-info mcpat-info-message" style="display: none;"><div class="woocommerce-message-wrapper"><span class="success-icon"><i class="spk-icon spk-icon-icon-like-it"></i></span><span class="notice_text mcpat_text"></span></div></div>';
				echo '<div class="woocommerce-error mcpat-error-message" style="display: none;"><ul><ul class="mcpat_errortext"></ul></ul></div>';
  			?>
			<script>    
        			jQuery('.delete_class').click(function(){
					jQuery('.button').prop("disabled", true);
					jQuery( '.mcpat-info-message').hide();
					jQuery( '.mcpat-error-message').hide();
            		var tr = jQuery(this).closest('tr');
					var licence_key = jQuery(this).attr( 'licence_key' );
					var deactivate = jQuery(this).attr( 'deactivate' );
					var platform = jQuery(this).attr( 'platform' );
					var softname = jQuery(this).attr( 'softname' );
					var email = jQuery(this).attr( 'email' );
					var instance = jQuery(this).attr( 'instance' );
					var error = "<?php echo __( 'Unable to deactivate at this time. If this error persist, please contact us. Thank you!', 'woo-admin-licenses' );?>";
            				jQuery('.spinner'+instance).prepend('<img id="theImg" src="<?php echo get_site_url(); ?>/wp-includes/images/spinner.gif"/>'); //https://www.mcpat.com/wp-content/plugins/sitepress-multilingual-cms/res/img/ajax-loader.gif" />')
					            				
					var data = {
						action: 		'mcpat_wal_toggle_activation',
						licence_key: 	licence_key,
						deactivate:		deactivate,
						platform:		platform,
						softname:		softname,
						email:			email,
						instance:		instance,
						security: 		'<?php echo wp_create_nonce("mcpat-wal-toggle-activation"); ?>'
					};

					var request=jQuery.ajax({
            					url: "<?php echo admin_url('admin-ajax.php'); ?>",
            					type: "POST",
						cache: false,
						global: false,
						timeout: 8000,
            					data: data,
						statusCode: {
    							404: function() {
      								//alert( "page not found" );
    							}
						}
						/*,
            					error: function(jqXHR, textStatus, errorThrown) {
							jQuery('.button').prop("disabled", false);
  							//alert( "AJAX call failed: "+textStatus+" "+errorThrown );
							jQuery('.mcpat_text').html( "AJAX call failed: "+textStatus+" "+errorThrown );
							jQuery( '.mcpat-info-message').show().slideDown();
            					},
	            				success: function(data) {
							jQuery('.button').prop("disabled", false);
							var newobject = eval(data);
							if (newobject.success == true) {
								tr.fadeOut(1000, function(){
                        						jQuery(this).remove();
                    						});
								jQuery('.mcpat_text').html( newobject.message );
								jQuery('.' + licence_key ).html(parseInt(jQuery('.' + licence_key).html(), 10)+1);
							} else {
								jQuery('.mcpat_text').html( newobject.message );
							}
							jQuery( '.mcpat-info-message').show().slideDown();
            					} */
            				});
					request.success(function( data ) {
							jQuery('.button').prop("disabled", false);
							var newobject = eval(data);
							if (newobject.success == true) {
								tr.fadeOut(800, function(){
                        						jQuery(this).remove();
                    						});
								jQuery('.mcpat_text').html( newobject.message );
								jQuery('.' + licence_key ).html(parseInt(jQuery('.' + licence_key).html(), 10)+1);
								jQuery( '.mcpat-info-message').show().slideDown();
							} else {
								jQuery('.mcpat_errortext').html( newobject.message );
								jQuery( '.mcpat-error-message').show().slideDown();
								jQuery('.spinner'+instance).html('');
							}
					});
 
					request.fail(function( jqXHR, textStatus, errorThrown ) {
						jQuery('.button').prop("disabled", false);
						jQuery('.mcpat_errortext').html( error );//+" ("+textStatus+" "+errorThrown+ ")");
						jQuery( '.mcpat-error-message').show().slideDown();
						jQuery('.spinner'+instance).html('');
					});
					
					/*jQuery.post('<?php echo admin_url('admin-ajax1.php'); ?>', data, function( result ) {
						alert(result);
						//$this.closest('tr').find('td.activation_active').html( result );
						//jQuery('#activations-table').unblock();
						jQuery('.button').prop("disabled", false);
						//var json = JSON.parse(result);
						//console.log(result);
						var newobject = eval(result);
						//alert(newobject.softname);
						//console.log(json);
						if (newobject.success == true) {
							tr.fadeOut(1000, function(){
                        					jQuery(this).remove();
                    					});
							jQuery('.mcpat_text').html( newobject.message );
							jQuery('.' + licence_key ).html(parseInt(jQuery('.' + licence_key).html(), 10)+1);
						} else {
							jQuery('.mcpat_text').html( newobject.message );
						}
						jQuery( '.wc-nonpurchasable-message').show().slideDown();
						//jQuery( '.wc-nonpurchasable-message.js-variation-' + activation ).show();
					}).fail(function(jqXHR, textStatus, errorThrown) {
   						// error
						jQuery('.button').prop("disabled", false);
						alert("AJAX call failed: "+textStatus+" "+errorThrown);
						return false;
 					});
					return false; */
        			});
			</script>
			<?php	
		} else {
			?><p><?php _e( 'No licenses available yet.', 'woo-admin-licenses'); ?></p><?php
		}
	} else {
		?><p><?php _e( 'No licenses available yet.', 'woo-admin-licenses'); ?></p><?php
	}
}
add_action( 'woocommerce_account_mcpat-wal-lost-license_endpoint', 'mcpat_wal_custom_endpoint_content' );
add_action( 'wp_ajax_mcpat_wal_toggle_activation', 'mcpat_wal_toggle_activation' );
function mcpat_wal_toggle_activation() {
		check_ajax_referer( 'mcpat-wal-toggle-activation', 'security' );
		
		global $wc_software;
		$licence_key = $_POST['licence_key']; //intval( $_POST['activation_id'] );
		$deactivate = $_POST['deactivate'];
		$platform = $_POST['platform'];
		$softname = $_POST['softname'];
		$email = $_POST['email'];
		$instance = $_POST['instance'];
		
		if ( empty( $instance ) ) {
			$data = array(
		            'success' => false,
		            'message' => sprintf( __( 'Deactivation failed with error "%s"', 'woo-admin-licenses' ), __('No matching instance exists', 'woo-admin-licenses' ) )
			);
			wp_send_json( $data );
			die();
		}

		$data = $wc_software->get_license_key( $licence_key, $deactivate, $email );

		if ( ! $data ) {
			$data = array(
		            'success' => false,
		            'message' => sprintf( __( 'Deactivation failed with error "%s"', 'woo-admin-licenses' ), __('No matching license key exists', 'woo-admin-licenses' ) )
			);
			wp_send_json( $data );
			die();
		}
		// reset number of activations
		$is_deactivated = $wc_software->deactivate_license_key( $data->key_id, $instance  );

		if ( !$is_deactivated ) {
			$data = array(
		            'success' => false,
		            'message' => sprintf( __( 'Deactivation failed with error "%s"', 'woo-admin-licenses' ), __('No matching instance exists', 'woo-admin-licenses' ) )
			);
			wp_send_json( $data );
			die();
		}

	        $data = array(
            		'success' => true,
            		'message' => sprintf( __( '<p>You have successfully deactivated %s for <strong>%s</strong></p>', 'woo-admin-licenses' ), $softname, $platform )
        	);
        	wp_send_json( $data );
		die();
}

//software add-on variable product hack
function mcpat_wal_remove_filters_for_anonymous_class( $hook_name = '', $class_name ='', $method_name = '', $priority = 0 ) {
	global $wp_filter;
	// Take only filters on right hook name and priority
	if ( !isset($wp_filter[$hook_name][$priority]) || !is_array($wp_filter[$hook_name][$priority]) )
		return false;
	// Loop on filters registered
	foreach( (array) $wp_filter[$hook_name][$priority] as $unique_id => $filter_array ) {
		// Test if filter is an array ! (always for class/method)
		if ( isset($filter_array['function']) && is_array($filter_array['function']) ) {
			// Test if object is a class, class and method is equal to param !
			if ( is_object($filter_array['function'][0]) && get_class($filter_array['function'][0]) && get_class($filter_array['function'][0]) == $class_name && $filter_array['function'][1] == $method_name ) {
			    // Test for WordPress >= 4.7 WP_Hook class (https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/)
			    if( is_a( $wp_filter[$hook_name], 'WP_Hook' ) ) {
			        unset( $wp_filter[$hook_name]->callbacks[$priority][$unique_id] );
			    }
			    else {
				    unset($wp_filter[$hook_name][$priority][$unique_id]);
			    }
			}
		}
	}
	return false;
}
add_action( 'add_meta_boxes', 'mcpat_wal_add_meta_boxes', 9 );
function mcpat_wal_add_meta_boxes() {
	if ( get_option( 'mcpat_wal_enable_variable_products' ) !== 'yes' ) {
		return;
	}	
	mcpat_wal_remove_filters_for_anonymous_class( 'add_meta_boxes', 'WC_Software_Order_Admin', 'add_meta_boxes', 10 );
	global $WC_Software_Order_Admin;
	add_meta_box( 'woocommerce-order-license-keys', __( 'Software License Keys', 'woo-admin-licenses'), 'mcpat_wal_license_keys_meta_box', 'shop_order', 'normal', 'high' );
	add_meta_box( 'wc_software-activation-data', __( 'Activations', 'woo-admin-licenses' ), array( $WC_Software_Order_Admin, 'activation_meta_box' ), 'shop_order', 'normal', 'high' );
	
}
add_action( 'wp_ajax_woocommerce_add_license_key', 'mcpat_wal_add_key', 9 );
add_action( 'woocommerce_process_shop_order_meta', 'mcpat_wal_order_save_data', 9  );
function mcpat_wal_order_save_data() {
		if ( get_option( 'mcpat_wal_enable_variable_products' ) !== 'yes' ) {
			return;
		}		
		mcpat_wal_remove_filters_for_anonymous_class( 'woocommerce_process_shop_order_meta', 'WC_Software_Order_Admin', 'order_save_data', 10 );

		global $post, $wpdb;

		$key_id              = isset( $_POST['key_id'] ) ? stripslashes_deep( $_POST['key_id'] ) : array();
		$license_key         = isset( $_POST['license_key'] ) ? stripslashes_deep( $_POST['license_key'] ) : array();
		$activation_email    = isset( $_POST['activation_email'] ) ? stripslashes_deep( $_POST['activation_email'] ) : array();
		$activations_limit   = isset( $_POST['activations_limit'] ) ? stripslashes_deep( $_POST['activations_limit'] ) : array();
		$software_product_id = isset( $_POST['software_product_id'] ) ? stripslashes_deep( $_POST['software_product_id'] ) : array();
		$software_version    = isset( $_POST['software_version'] ) ? stripslashes_deep( $_POST['software_version'] ) : array();
		$software_expire    = isset( $_POST['software_expire'] ) ? stripslashes_deep( $_POST['software_expire'] ) : array();
		$key_id_count        = sizeof( $key_id );

		for ( $i = 0; $i < $key_id_count; $i++ ) {
			if ( ! isset( $key_id[$i] ) ) continue;

			$data = array(
				'license_key' 			=> esc_attr( $license_key[$i] ),
				'activation_email' 		=> esc_attr( $activation_email[$i] ),
				'activations_limit' 	=> ( $activations_limit[$i] == '' ) ? '' : (int) $activations_limit[$i],
				'software_product_id' 	=> esc_attr( $software_product_id[$i] ),
				'software_version' 		=> esc_attr( $software_version[$i] ),
				'software_expire' 		=> esc_attr( $software_expire[$i] ),
            );

            $format = array(
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s'
            );

			$wpdb->update(
				$wpdb->prefix . 'woocommerce_software_licenses',
				$data,
				array( 'key_id' => $key_id[$i] ),
				$format,
				array( '%d' )
			);

		}
}
function mcpat_wal_add_key() {
		if ( get_option( 'mcpat_wal_enable_variable_products' ) !== 'yes' ) {
			return;
		}
				
		mcpat_wal_remove_filters_for_anonymous_class( 'wp_ajax_woocommerce_add_license_key', 'WC_Software_Order_Admin', 'add_key', 10 );
		check_ajax_referer( 'add-key', 'security' );
		
		global $wpdb, $wc_software;

		$product_id  = intval( $_POST['product_id'] );
		$order_id 	 = intval( $_POST['order_id'] );
		$order       = new WC_Order( $order_id );
		$meta        = get_post_custom( $product_id );

		$wpdb->hide_errors();
		$data = array(
			'order_id' 				=> $order_id,
			'activation_email'		=> version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_email : $order->get_billing_email(),
			'prefix'				=> '',
			'license_key' 			=> ( empty( $meta['_software_license_key_prefix'][0] ) ? '' : $meta['_software_license_key_prefix'][0] ) . $wc_software->generate_license_key(),
			'software_product_id'	=> empty( $meta['_software_product_id'][0] ) ? '' : $meta['_software_product_id'][0],
			'software_version'		=> empty( $meta['_software_version'][0] ) ? '' : $meta['_software_version'][0],
			'activations_limit'		=> empty( $meta['_software_activations'][0] ) ? '' : (int) $meta['_software_activations'][0],
			'software_expire'       => empty( $meta['_mcpat_wal_software_expire'][0] ) ? '' : (int) $meta['_mcpat_wal_software_expire'][0],
        );
    
		//$key_id = $this->save_license_key( $data );
		$key_id = mcpat_wal_save_license_key( $data );
		
		if ( $key_id ) {
			$data['success'] = 1;
			$data['key_id']  = $key_id;
			wp_send_json( $data );
		}
		
		die();
}
function mcpat_wal_license_keys_meta_box() {
		global $post, $wpdb;//, $WC_Software_Order_Admin;

		?>
		<div class="order_license_keys wc-metaboxes-wrapper">

			<div class="wc-metaboxes">

				<?php
					$i = -1;

					$license_keys = $wpdb->get_results("
						SELECT * FROM {$wpdb->wc_software_licenses}
						WHERE order_id = $post->ID
					");

					if ( $license_keys && sizeof( $license_keys ) > 0 ) foreach ( $license_keys as $license_key ) :
						$i++;

						?>
			    		<div class="wc-metabox closed">
							<h3 class="fixed">
								<button type="button" rel="<?php echo $license_key->key_id; ?>" class="delete_key button"><?php _e( 'Delete key', 'woo-admin-licenses' ); ?></button>
								<div class="handlediv" title="<?php _e( 'Click to toggle', 'woo-admin-licenses' ); ?>"></div>
								<strong><?php printf( __( 'Product: %s, version %s', 'woo-admin-licenses' ), $license_key->software_product_id, $license_key->software_version ); ?> &mdash; <?php echo $license_key->license_key; ?></strong>
								<input type="hidden" name="key_id[<?php echo $i; ?>]" value="<?php echo $license_key->key_id; ?>" />
							</h3>
							<table cellpadding="0" cellspacing="0" class="wc-metabox-content">
								<tbody>
									<tr>
										<td>
											<label><?php _e( 'License Key', 'woo-admin-licenses' ); ?>:</label>
											<input type="text" class="short" name="license_key[<?php echo $i; ?>]" value="<?php echo $license_key->license_key; ?>" />
										</td>
										<td>
											<label><?php _e( 'Activation Email', 'woo-admin-licenses' ); ?>:</label>
											<input type="text" class="short" name="activation_email[<?php echo $i; ?>]" value="<?php echo $license_key->activation_email; ?>" />
										</td>
										<td>
											<label><?php _e( 'Activation Limit', 'woo-admin-licenses' ); ?>:</label>
											<input type="text" class="short" name="activations_limit[<?php echo $i; ?>]" value="<?php echo $license_key->activations_limit; ?>" placeholder="<?php _e( 'Unlimited', 'woo-admin-licenses' ); ?>" />
										</td>
									</tr>
									<tr>
										<td>
											<label><?php _e( 'Software Product ID', 'woo-admin-licenses' ); ?>:</label>
											<input type="text" class="short" name="software_product_id[<?php echo $i; ?>]" value="<?php echo $license_key->software_product_id; ?>" />
										</td>
										<td>
											<label><?php _e( 'Software Version', 'woo-admin-licenses' ); ?>:</label>
											<input type="text" class="short" name="software_version[<?php echo $i; ?>]" value="<?php echo $license_key->software_version; ?>" />
										</td>
										<td>
											<label><?php _e( 'Expiry days', 'woo-admin-licenses' ); ?>:</label>
											<?php
											if($license_key->software_expire != 0) {
												$expire = $license_key->software_expire;
											} else {
												$expire = '';//&infin;';	
											}?>
											<input type="text" class="short" name="software_expire[<?php echo $i; ?>]" value="<?php echo $expire ?>" placeholder="<?php _e( 'Unlimited', 'woo-admin-licenses' ); ?>" />
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<?php
					endforeach;
				?>
			</div>

			<div class="toolbar">
				<p class="buttons">
					<select name="add_software_id" class="add_software_id chosen_select_nostd" data-placeholder="<?php _e( 'Choose a software product&hellip;', 'woo-admin-licenses' ) ?>">
						<?php
							echo '<option value=""></option>';
							$args = array(
								'post_type' 		=> 'product',
								'posts_per_page' 	=> -1,
								'post_status'		=> 'publish',
								'order'				=> 'ASC',
								'orderby'			=> 'title'
							);
							$products = get_posts( $args );
							
							$multi = false;
							if ( array_key_exists('wpml_object_id', $GLOBALS['wp_filter']) ) {
								global $sitepress;
								if ( isset( $sitepress )) {
									$multi = true;
								}
							}
							
							
							if ($products) foreach ($products as $product) :
								if ($multi) {
									if ($product->ID != apply_filters( 'wpml_object_id', $product->ID, get_post_type( $product->ID ), true, $sitepress->get_default_language() )) {
										continue;
									}
								}
								$sku = get_post_meta($product->ID, '_sku', true);
								if ($sku) $sku = ' SKU: '.$sku;
								$args_get_children = array(
									'post_type' => array( 'product_variation', 'product' ),
									'posts_per_page' 	=> -1,
									'order'				=> 'ASC',
									'orderby'			=> 'title',
									'post_parent'		=> $product->ID
								);
								$children_products = get_children( $args_get_children );
								$istsoft = get_post_meta($product->ID, '_is_software', true);
								if ($istsoft == 'yes') {
									echo '<option value="'.$product->ID.'">'.$product->post_title.$sku.' (#'.$product->ID.''.$sku.')</option>';
								}
								if ( ! empty( $children_products ) ) :
									foreach ($children_products as $child) :
										$istsoft2 = get_post_meta($child->ID, '_is_software', true);
										if ($istsoft2 == 'yes') {
											if ($istsoft == 'yes') { 
												echo '<option value="'.$child->ID.'">&nbsp;&nbsp;&mdash;&nbsp;'.$child->post_title.'</option>';
											} else {
												echo '<option value="'.$child->ID.'">'.$child->post_title. __(' (Child @ ', 'woo-admin-licenses') . $product->post_title. ' - #'.$product->ID . ')</option>';
											}
										}
									endforeach;
								endif;
							endforeach;
						?>
					</select>
					<button type="button" class="button add_key"><?php _e( 'Add License Key', 'woo-admin-licenses' ); ?></button>
				</p>
				<div class="clear"></div>
			</div>

		</div>
		<?php
		/**
		 * Javascript
		 */
		ob_start();
		?>
		jQuery(function(){

			jQuery('.order_license_keys').on('click', 'button.add_key', function(){

				var product = jQuery('select.add_software_id').val();

				if ( ! product ) return false;

				jQuery('.order_license_keys').block({message: null, overlayCSS: { background: '#fff', opacity: 0.6 }});

				var data = {
					action: 		'woocommerce_add_license_key',
					product_id: 	product,
					order_id: 		'<?php echo $post->ID; ?>',
					security: 		'<?php echo wp_create_nonce("add-key"); ?>'
				};

				jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function( new_software ) {
					var loop = jQuery('.order_license_keys .wc-metabox').size();

					if ( new_software && new_software.success == 1 ) {

						jQuery('.order_license_keys .wc-metaboxes').append('<div class="wc-metabox closed">\
							<h3 class="fixed">\
								<button type="button" rel="' + new_software.key_id + '" class="delete_key button"><?php _e('Delete key', 'woo-admin-licenses'); ?></button>\
								<div class="handlediv" title="<?php _e('Click to toggle', 'woo-admin-licenses'); ?>"></div>\
								<strong><?php printf( __( 'Product: %s, version %s', 'woo-admin-licenses' ), "' + new_software.software_product_id + '", "' + new_software.software_version + '" ); ?> &mdash; ' + new_software.license_key + '</strong>\
								<input type="hidden" name="key_id[' + loop + ']" value="' + new_software.key_id + '" />\
							</h3>\
							<table cellpadding="0" cellspacing="0" class="wc-metabox-content">\
								<tbody>	\
									<tr>\
										<td>\
											<label><?php _e('License Key', 'woo-admin-licenses'); ?>:</label>\
											<input type="text" class="short" name="license_key[' + loop + ']" value="' + new_software.license_key + '" />\
										</td>\
										<td>\
											<label><?php _e('Activation Email', 'woo-admin-licenses'); ?>:</label>\
											<input type="text" class="short" name="activation_email[' + loop + ']" value="' + new_software.activation_email + '" />\
										</td>\
										<td>\
											<label><?php _e('Activations Remaining', 'woo-admin-licenses'); ?>:</label>\
											<input type="text" class="short" name="activations_limit[' + loop + ']" value="' + new_software.activations_limit + '" placeholder="<?php _e('Unlimited', 'woo-admin-licenses'); ?>" />\
										</td>\
									</tr>\
									<tr>\
										<td>\
											<label><?php _e('Software Product ID', 'woo-admin-licenses'); ?>:</label>\
											<input type="text" class="short" name="software_product_id[' + loop + ']" value="' + new_software.software_product_id + '" />\
										</td>\
										<td>\
											<label><?php _e('Software Version', 'woo-admin-licenses'); ?>:</label>\
											<input type="text" class="short" name="software_version[' + loop + ']" value="' + new_software.software_version + '" />\
										</td>\
										<td>\
											<label><?php _e( 'Expiry days', 'woo-admin-licenses' ); ?>:</label>\
											<input type="text" class="short" name="software_expire[<?php echo $i; ?>]" value="' + new_software.software_expire + '" placeholder="<?php _e( 'Unlimited', 'woo-admin-licenses' ); ?>" />\
										</td>\
										<td>&nbsp;</td>\
									</tr>\
								</tbody>\
							</table>\
						</div>');

					}

					jQuery('.order_license_keys').unblock();

				});

				return false;

			});

			jQuery('.order_license_keys').on('click', 'button.delete_key', function(e){
				e.preventDefault();
				var answer = confirm('<?php _e('Are you sure you want to delete this license key?', 'woo-admin-licenses'); ?>');
				if (answer){

					var el = jQuery(this).parent().parent();

					var key_id = jQuery(this).attr('rel');

					if ( key_id > 0 ) {

						jQuery(el).block({message: null, overlayCSS: { background: '#fff', opacity: 0.6 }});

						var data = {
							action: 		'woocommerce_delete_license_key',
							key_id: 		key_id,
							order_id: 		'<?php echo $post->ID; ?>',
							security: 		'<?php echo wp_create_nonce("delete-key"); ?>'
						};

						jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
							// Success
							jQuery(el).fadeOut('300', function(){
								jQuery(el).remove();
							});
						});

					} else {
						jQuery(el).fadeOut('300', function(){
							jQuery(el).remove();
						});
					}

				}
				return false;
			});

		});
		<?php
		$javascript = ob_get_clean();
		wc_enqueue_js( $javascript );
	}



add_action( 'woocommerce_order_status_completed', 'mcpat_wal_order_complete', 9 );
function mcpat_wal_order_complete( $order_id ) {
			//hack the software add-on
			if ( get_option( 'mcpat_wal_enable_variable_products' ) !== 'yes' ) {
				return;
			}		
			mcpat_wal_remove_filters_for_anonymous_class( 'woocommerce_order_status_completed', 'WC_Software', 'order_complete', 10 );
			
			global $wc_software;

			global $wpdb;

			if ( get_post_meta( $order_id, 'software_processed', true ) == 1 ) return; // Only do this once

			$order = new WC_Order( $order_id );

			if ( sizeof( $order->get_items() ) == 0 ) {
				return;
			}

			if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_renewal( $order ) ) {
				// license keys on the original/parent order still valid.
				return;
			}

			foreach ( $order->get_items() as $item ) {

				$item_product_id = ( isset( $item['product_id'] ) ) ? $item['product_id'] : $item['id'];

				if ( ! ( $item_product_id > 0 ) ) {
					continue;
				}
				
				$meta = get_post_custom( $item_product_id );
				
				//hack
				$usehack = false;
				if ( isset( $item['variation_id'] ) && $item['variation_id'] > 0 ) {
					$orig_ppost = $item['variation_id'] ;
					if ( array_key_exists('wpml_object_id', $GLOBALS['wp_filter']) ) {
						global $sitepress;
						if ( isset( $sitepress )) {
							$orig_ppost = apply_filters( 'wpml_object_id', $item['variation_id'], get_post_type( $item['variation_id'] ), true, $sitepress->get_default_language() );
						}
					}	
					if ( 'yes' != get_post_meta( $orig_ppost, '_is_software', true )) {
						//$usehack = true;
						//} else {
						continue;
					}
				} else {
					$orig_ppost = $item_product_id;
					if ( array_key_exists('wpml_object_id', $GLOBALS['wp_filter']) ) {
						global $sitepress;
						if ( isset( $sitepress )) {
							$orig_ppost = apply_filters( 'wpml_object_id', $item_product_id, get_post_type( $item_product_id ), true, $sitepress->get_default_language() );
						}
					}
					if ( 'yes' != get_post_meta( $orig_ppost, '_is_software', true )) {
						//$usehack = true;
						//} else {
						continue;
					}
				}
				

				$quantity = 1;
				if ( isset( $item['item_meta']['_qty'][0] ) ) {
					$quantity = absint( $item['item_meta']['_qty'][0] );
				} elseif ( isset( $item['quantity'] ) ) {
					$quantity = absint( $item['quantity'] );
				}

				// FOUND SOME SOFTWARE - Lets make those licenses!
				for ( $i = 0; $i < $quantity; $i++ ) {
					/*if(!$usehack){
						$data = array(
							'order_id'            => $order_id,
							'activation_email'    => version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_email : $order->get_billing_email(),
							'prefix'              => empty( $meta['_software_license_key_prefix'][0] ) ? '' : $meta['_software_license_key_prefix'][0],
							'software_product_id' => empty( $meta['_software_product_id'][0] ) ? '' : $meta['_software_product_id'][0],
							'software_version'    => empty( $meta['_software_version'][0] ) ? '' : $meta['_software_version'][0],
							'activations_limit'   => empty( $meta['_software_activations'][0] ) ? '' : (int) $meta['_software_activations'][0],
							'software_expire'   => empty( $meta['_mcpat_wal_software_expire'][0] ) ? '' : (int) $meta['_mcpat_wal_software_expire'][0],
						);
					} else {*/
							$data = array(
								'order_id'            => $order_id,
								'activation_email'    => version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_email : $order->get_billing_email(),
								'prefix'              => get_post_meta( $orig_ppost, '_software_license_key_prefix', true ),
								'software_product_id' => get_post_meta( $orig_ppost, '_software_product_id', true ),
								'software_version'    => get_post_meta( $orig_ppost, '_software_version', true ),
								'activations_limit'   => get_post_meta( $orig_ppost, '_software_activations', true ),
								'software_expire'     => get_post_meta( $orig_ppost, '_mcpat_wal_software_expire', true ),
							);
					//}
					//$key_id = $wc_software->save_license_key( $data );
					$key_id = mcpat_wal_save_license_key( $data );
				}
			}

			update_post_meta( $order_id,  'software_processed', 1);
}
function mcpat_wal_save_license_key( $data ) {
			global $wpdb;
			global $wc_software;
			
			$defaults = array(
				'order_id'            => '',
				'activation_email'    => '',
				'prefix'              => '',
				'license_key'         => '',
				'software_product_id' => '',
				'software_version'    => '',
				'activations_limit'   => '',
				'created'             => current_time( 'mysql' ),
				'software_expire'     => '',
			);

			$data = wp_parse_args( $data, $defaults );

			if ( empty( $data['license_key'] ) ) {
				$data['license_key'] = $wc_software->generate_license_key();
			}

			
			$insert = apply_filters( 'woocommerce_software_addon_save_license_key', array(
				'order_id'            => $data['order_id'],
				'activation_email'    => $data['activation_email'],
				'license_key'         => $data['prefix'] . $data['license_key'],
				'software_product_id' => $data['software_product_id'],
				'software_version'    => $data['software_version'],
				'activations_limit'   => $data['activations_limit'],
				'created'             => $data['created'],
				'software_expire'     => $data['software_expire'],
			) );

			$format = array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			);

			$wpdb->insert( $wpdb->wc_software_licenses,
				$insert,
				$format
			);

			return $wpdb->insert_id;
}

} else {
	add_action( 'admin_init', 'mcpat_wal_disable_plugin' );
}

function mcpat_wal_disable_plugin() {
    if ( current_user_can('activate_plugins') && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );

        // Hide the default "Plugin activated" notice
        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}

function mcpat_wal_is_requirements_met() {
    $min_wp  = '4.7';
    $min_wc = '3.0';
    $min_wc_soft = '1.7.3';
    
    // Check for WordPress version
    if ( version_compare( get_bloginfo('version'), $min_wp, '<' ) ) {
    	add_action( 'admin_notices', 'mcpat_wal_req_notice' );
        return false;
    }

    // Detect if WooCommerce is active.
    if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			add_action( 'admin_notices', 'mcpat_wal_req_woo_not_active_notice' );
			return false;
	} else { //Check that the WooCommerce version is upto date.
			if ( version_compare( get_option( 'woocommerce_version' ), $min_wc, '<' ) ) {
				add_action( 'admin_notices', 'mcpat_wal_req_woo_notice' );
				return false;
			}
	}
    // Detect if WooCommerce Software Add-on is installed
		if ( !in_array( 'woocommerce-software-add-on/woocommerce-software.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			add_action( 'admin_notices', 'mcpat_wal_req_woo_software_notice' );
			return false;
		} else {
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce-software-add-on/woocommerce-software.php',false, false );
			if ( version_compare( $plugin_data['Version'], $min_wc_soft, '<' ) ) {
				add_action( 'admin_notices', 'mcpat_wal_req_woo_software_notice' );
				return false;	
			}
	}
    return true;
}

	function mcpat_wal_req_notice() {
		echo '<div id="message" class="notice notice-error is-dismissible"><p>';
		echo sprintf( __( 'Sorry, <strong>woo-admin-licenses</strong> requires WordPress %s or higher. Please upgrade your WordPress setup', 'woo-admin-licenses' ), '4.7');
		echo '</div>';
	}
	
	function mcpat_wal_req_woo_not_active_notice() {
		echo '<div id="message" class="notice notice-error is-dismissible"><p>';
		echo sprintf( __( 'Sorry, <strong>woo-admin-licenses</strong> requires WooCommerce to be installed and activated first. Please <a href="%s">install WooCommerce</a>.', 'woo-admin-licenses' ), admin_url( 'plugin-install.php?tab=search&type=term&s=WooCommerce' ) );
		echo '</div>';
	}

	function mcpat_wal_req_woo_notice() {
		echo '<div id="message" class="notice notice-error is-dismissible"><p>';
		echo sprintf( __( 'Sorry, <strong>woo-admin-licenses</strong> requires WooCommerce %s or higher. Please update WooCommerce for woo-admin-licenses to work.', 'woo-admin-licenses' ), '3.0');
		echo '</div>';
	}

	function mcpat_wal_req_woo_software_notice() {
		echo '<div id="message" class="notice notice-error is-dismissible"><p>';
		echo sprintf( __( 'Sorry, <strong>woo-admin-licenses</strong> requires WooCommerce Software Add-on %s or higher to be installed and activated first.', 'woo-admin-licenses' ), '1.7.3');
		echo '</div>';
	}