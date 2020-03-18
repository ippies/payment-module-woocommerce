<?php

	class WC_Gateway_Ippies extends WC_Payment_Gateway {
 
	    // Ippies IP address
	    const IPPIES_IP = '46.182.221.124';

	    // Ippies URL
	    const IPPIES_URL = 'https://payment.ippies.nl/paymod.php';

	    // Ippies sandbox url
	    const IPPIES_TEST_URL = 'https://payment.ippiestest.nl/paymod.php';

	    // Gateway name
	    const PAYMENT_METHOD = 'ippies';

		/**
		* initialise gateway with custom settings
		*/
		public function __construct() {

			global $woocommerce;

			$this->id = self::PAYMENT_METHOD;
			$this->icon = WOOCOMMERCE_IPPIES_PLUGIN_URL . 'resources/images/ippies.png';
			$this->has_fields = false;
			$this->title = 'ippies.nl';
			$this->description = __('Pay with the ippies you have saved!', 'ippies-payment-gateway');
			$this->init_form_fields();
			$this->init_settings();

			//Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_ippies_response_legacy' ) );
		} 

		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Enable/Disable', 'woocommerce'),
					'type' => 'checkbox',
					'label' => __('Enable ippies.nl Payment', 'ippies-payment-gateway'),
					'default' => 'yes'
				),
				'ippies_mode' => array(
					'title' => __('Live/Test', 'ippies-payment-gateway'),
					'type' => 'checkbox',
					'label' => __('Disable test mode', 'ippies-payment-gateway'),
					'default' => 'no'
				),
				'ippies_api_live_id' => array(
					'title' => __('Ippies API ID (live)', 'ippies-payment-gateway'),
					'type' => 'text',
				),
				'ippies_api_live_secret' => array(
					'title' => __('Ippies API secret (live)', 'ippies-payment-gateway'),
					'type' => 'text',
				),
				'ippies_api_test_id' => array(
					'title' => __('Ippies API ID (test)', 'ippies-payment-gateway'),
					'type' => 'text',
				),
				'ippies_api_test_secret' => array(
					'title' => __('Ippies API secret (test)', 'ippies-payment-gateway'),
					'type' => 'text',
				),                          
				'title' => array(
					'title' => __('Title', 'woocommerce'),
					'type' => 'text',
					'description' => __('This controls the title which user sees during checkout.', 'ippies-payment-gateway'),
					'default' => 'ippies.nl',
					'desc_tip' => true,
				)
			);
		}

		function process_payment( $order_id ) {

			global $woocommerce;
			
			$order = new WC_Order( $order_id );

			$order->reduce_order_stock();

			$woocommerce->cart->empty_cart();

			return array(
				'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
			);
		}

		function receipt_page( $order ) {
			echo $this->generate_ippies_form_legacy( $order );
		}

		function format_shop_name_for_order_id( $string, $separator = '-' )
		{
		    $accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
		    $special_cases = array( '&' => 'and', "'" => '');
		    $string = mb_strtolower( trim( $string ), 'UTF-8' );
		    $string = str_replace( array_keys($special_cases), array_values( $special_cases), $string );
		    $string = preg_replace( $accents_regex, '$1', htmlentities( $string, ENT_QUOTES, 'UTF-8' ) );
		    $string = preg_replace("/[^a-z0-9]/u", "$separator", $string);
		    $string = preg_replace("/[$separator]+/u", "$separator", $string);
		    return strtoupper($string);
		}

		function generate_ippies_form_legacy( $order_id ) {

			$order = new WC_Order( $order_id );

			// SET THIS WITH SETTINGS
			$ippies_url = self::IPPIES_URL;
			$controle_key = $this->get_option( 'ippies_api_live_secret' );;
			$pay_shopid = $this->get_option( 'ippies_api_live_id' );
			$live_mode = $this->get_option( 'ippies_mode' );

			if ($live_mode == 'no') {
				$ippies_url = self::IPPIES_TEST_URL;
				$controle_key = $this->get_option( 'ippies_api_test_secret' );;
				$pay_shopid = $this->get_option( 'ippies_api_test_id' );
			}

			// $ippies_id = $this->get_option( 'ippies_id' );
			$blog_name = (string) get_bloginfo();

			$pay_orderid = $this->format_shop_name_for_order_id($blog_name) . '_' . esc_attr($order_id);
			$pay_amount = floatval($order->get_total()) * 100;
			$return_normal = $this->get_return_url($order);
			$return_true = $this->get_return_url($order);
			$return_false = $this->get_return_url($order);
			$test = ($this->get_option( 'ippies_test' ) == "yes") ? 1 : 0;
			$test = 0;
			$pay_hash = sha1($pay_shopid.md5($controle_key).$pay_orderid.$pay_amount);


            $notify_url = add_query_arg( 'wc-api', 'WC_Gateway_Ippies', home_url( '/' ));

			wc_enqueue_js( '
				$.blockUI({
						message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to ippies.nl to make payment.', 'ippies-payment-gateway' ) ) . '",
						baseZ: 99999,
						overlayCSS:
						{
							background: "#fff",
							opacity: 0.6
						},
						css: {
							padding:        "20px",
							zindex:         "9999999",
							textAlign:      "center",
							color:          "#555",
							border:         "3px solid #aaa",
							backgroundColor:"#fff",
							cursor:         "wait",
							lineHeight:		"24px",
						}
					});
				jQuery("#submit_ippies_payment_form").click();
			' );

	        ob_start();
	            include(WOOCOMMERCE_IPPIES_PLUGIN_DIR . '/frontend/templates/woocommerce-ippies-payment-button.tpl.php');
	            $html = ob_get_contents();
	        ob_end_clean();

	        return $html;

		}

		function check_ippies_response_legacy() {
			global $woocommerce;

            $data = $_REQUEST;

            $blog_name = (string) get_bloginfo();
			$pay_order_id = $this->format_shop_name_for_order_id($blog_name) . '_';
			$order_id = str_replace($pay_order_id, '', $data['orderid']);
            $order = new WC_Order($order_id);

            $order->add_order_note('Callback ippies.nl payment callback.');
            
            if($_SERVER['REMOTE_ADDR'] <> self::IPPIES_IP) {
            	$order->add_order_note('ippies.nl: Unknown ip address');
                wp_redirect( $this->get_return_url( $order ) );
            }

            $blog_name = (string) get_bloginfo();
			$pay_order_id = $this->format_shop_name_for_order_id($blog_name) . '_' . esc_attr($order_id);

            $check_data_array = [
            	'shop_id' => $this->get_option( 'ippies_shop_id' ),
            	'order_id' => $pay_order_id,
            	'amount' => floatval($order->get_total()) * 100,
            	'ip' => $data['ip'],
            	'partner_trans_id' => $this->partner_trans_id,
            	'hash' => $data['hash'],
            ];

            $check_payment_url = 'https://payment.ippies.nl/check.php?' . http_build_query($check_data_array);
			
			$response = file_get_contents($check_payment_url, false, stream_context_create([
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
				]
			]));

            if ($response) {
                $response = json_decode($response, true);

                switch($response['status']) {
	                case 0:
	                    $order->payment_complete();
	                    break;
	                case 1:
                        $order->update_status('pending');
                        break;
	                case 2:
                        $order->update_status('processing');
                        break;
	                case 3:
                        $order->update_status('failed');
                        break;
	                case 4:
                        $order->update_status('failed');
                        break;
	                case 5:
                        $order->update_status('on-hold');
                        break;
	                default:
	                    die('WooCommerce - Wrong status - '.$response['status']);
	            }
            } else {
                $order->update_status('pending');
            }

            die('OK');           
		}
	}