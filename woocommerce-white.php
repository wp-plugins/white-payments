<?php
/*
Plugin Name: White Payments
Description: A full stack payment solution for the Middle East - www.whitepayments.com
Version: 2.0.5
Plugin URI: https://www.whitepayments.com
Author: White Payments
Author URI: https://www.whitepayments.com
License: Under GPL2   
*/

require plugin_dir_path(__FILE__).'vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* Enable automatic updates to this plugin
   ----------------------------------------------------------- */
add_filter('auto_update_plugin', '__return_true');

/* Add a custom payment class to WC
  ------------------------------------------------------------ */
add_action('plugins_loaded', 'woocommerce_white', 0);

function woocommerce_white(){
  if (!class_exists('WC_Payment_Gateway'))
    return; // if the WC payment gateway class is not available, do nothing
  if(class_exists('WC_White'))
    return;

    class WC_Gateway_White extends WC_Payment_Gateway{
        public function __construct(){

            $plugin_dir = plugin_dir_url(__FILE__);

            global $woocommerce;

            $this->id = 'white';
            $this->icon = apply_filters('woocommerce_white_icon', ''.$plugin_dir.'white-cards.png');
            $this->has_fields = true;

            // Load the settings
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title = "Credit card (powered by White)";
            $this->test_open_key = $this->get_option('test_open_key');
            $this->test_secret_key = $this->get_option('test_secret_key');
            $this->live_open_key = $this->get_option('live_open_key');
            $this->live_secret_key = $this->get_option('live_secret_key');
            $this->description = $this->get_option('description');
            $this->test_mode = $this->get_option('test_mode');

            // Logs
            if ($this->debug == 'yes'){
                $this->log = $woocommerce->logger();
            }

            // Actions
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

            // Save options
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // Payment listener/API hook
            #add_action('woocommerce_api_wc_' . $this->id, array($this, 'check_ipn_response'));

            if (!$this->is_valid_for_use()){
                $this->enabled = false;
            }
        }

        /**
         * Check if this gateway is enabled and available in the user's currency
         *
         * @access public
         * @return bool
         */
        function is_valid_for_use() {
            if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_white_supported_currencies', array( 'AED', 'USD' ) ) ) ) return false;

            return true;
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'api keys' and availability on a country-by-country basis
         *
         * @since 1.0.0
         */
        public function admin_options() {

            ?>
            <h3><?php _e( 'White Payments', 'woocommerce' ); ?></h3>
            <p><?php _e( 'Please fill in the below section to start accepting payments on your site! You can find all the required information in your <a href="https://dashboard.whitepayments.com/" target="_blank">White Dashboard</a>.', 'woocommerce' ); ?></p>

            <?php if ( $this->is_valid_for_use() ) : ?>

                <table class="form-table">
                    <?php
                    // Generate the HTML For the settings form.
                    $this->generate_settings_html();
                    ?>
                </table><!--/.form-table-->

            <?php else : ?>
                <div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'White does not support your store currency.', 'woocommerce' ); ?></p></div>
            <?php
            endif;
        }


        /**
         * Initialise Gateway Settings Form Fields
         *
         * @access public
         * @return void
         */
        function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __( 'Enable/Disable', 'woocommerce' ),
                    'type' => 'checkbox',
                    'label' => __( 'Enable White', 'woocommerce' ),
                    'default' => 'yes'
                ),
                'description' => array(
                    'title' => __( 'Description', 'woocommerce' ),
                    'type' => 'text',
                    'description' => __( 'This is the description the user sees during checkout.', 'woocommerce' ),
                    'default' => __( 'Pay for your items with Credit Card', 'woocommerce' )
                ),
                'test_open_key' => array(
                    'title' => __( 'Test Open Key', 'woocommerce' ),
                    'type'      => 'text',
                    'description' => __( 'Please enter your test open key (you can get it from your White dashboard).', 'woocommerce' ),
                    'default' => '',
                    'desc_tip'      => true,
                    'placeholder' => ''
                ),
                'test_secret_key' => array(
                    'title' => __( 'Test Secret Key', 'woocommerce' ),
                    'type'      => 'text',
                    'description' => __( 'Please enter your test secret key (you can get it from your White dashboard).', 'woocommerce' ),
                    'default' => '',
                    'desc_tip'      => true,
                    'placeholder' => ''
                ),
                'live_open_key' => array(
                    'title' => __( 'Live Open Key', 'woocommerce' ),
                    'type'      => 'text',
                    'description' => __( 'Please enter your live open key (you can get it from your White dashboard).', 'woocommerce' ),
                    'default' => '',
                    'desc_tip'      => true,
                    'placeholder' => ''
                ),
                'live_secret_key' => array(
                    'title' => __( 'Live Secret Key', 'woocommerce' ),
                    'type'      => 'text',
                    'description' => __( 'Please enter your live secret key (you can get it from your White dashboard).', 'woocommerce' ),
                    'default' => '',
                    'desc_tip'      => true,
                    'placeholder' => ''
                ),
                'test_mode' => array(
                    'title' => __( 'Test mode', 'woocommerce' ),
                    'type' => 'checkbox',
                    'label' => __( 'Enable Test mode', 'woocommerce' ),
                    'default' => 'no'
                )
            );

        }

        /**
         * Generate the credit card payment form
         *
         * @access public
         * @param none
         * @return string
         */
        function payment_fields() {
            $plugin_dir = plugin_dir_url(__FILE__);
            // Description of payment method from settings
            if ($this->description) { ?>
                <p><?php
                echo $this->description; ?>
                </p><?php
            } ?>

            <ul class="woocommerce-error" style="display:none" id="white_error_creditcard">
            <li>Credit Card details are incorrect, please try again.</li>
            </ul>

            <fieldset class='white-payments'>
            <input id="white-token" name="whiteToken" type="hidden" value="">

            <!-- Credit card number -->
            <p class="form-row">
                <label for="white-ccNo"><?php echo __( 'Credit Card number', 'woocommerce' ) ?> <span class="required">*</span></label>
                <input type="text" class="input-text" id="white-ccNo" autocomplete="off" value="" />
            </p>

            <div class="clear"></div>

            <!-- Credit card expiration -->
            <p class="form-row">
                <label for="white-expMonth"><?php echo __( 'Expiration date', 'woocommerce') ?> <span class="required">*</span></label>
                <select id="white-expMonth" class="woocommerce-select woocommerce-cc-month">
                    <option value=""><?php _e( 'Month', 'woocommerce' ) ?></option><?php
                    $months = array();
                    for ( $i = 1; $i <= 12; $i ++ ) {
                        $timestamp = mktime( 0, 0, 0, $i, 1 );
                        $months[ date( 'n', $timestamp ) ] = date( 'F', $timestamp );
                    }
                    foreach ( $months as $num => $name ) {
                        printf( '<option value="%02d">%s</option>', $num, $name );
                    } ?>
                </select>
                <select id="white-expYear" class="woocommerce-select woocommerce-cc-year">
                    <option value=""><?php _e( 'Year', 'woocommerce' ) ?></option>
                    <?php
                    $years = array();
                    for ( $i = date( 'y' ); $i <= date( 'y' ) + 15; $i ++ ) {
                        printf( '<option value="20%u">20%u</option>', $i, $i );
                    }
                    ?>
                </select>
            </p>
            <div class="clear"></div>

            <!-- Credit card security code -->
            <p class="form-row">
            <label for="white-cvc"><?php _e( 'Card security code', 'woocommerce' ) ?> <span class="required">*</span></label>
            <input type="text" class="input-text" id="white-cvc" autocomplete="off" maxlength="4" style="width:55px" />
            <span class="help"><?php _e( '3 or 4 digits usually found on the signature strip.', 'woocommerce' ) ?></span>
            </p>

            <div class="clear"></div>

            </fieldset>

           <script type="text/javascript">
                var formName = "order_review";
                var myForm = document.getElementsByName('checkout')[0];
                if(myForm) {
                    myForm.id = "whiteCCForm";
                    formName = "whiteCCForm";
                } 
                jQuery('#' + formName).on("click", function(){
                    jQuery('#place_order').unbind('click');
                    jQuery('#place_order').click(function(e) {
                        e.preventDefault();
                        retrieveToken();
                    });
                });

                function whiteCallback(status, response) {
                    // Complete
                    if(response.error) {
                        // Something went wrong
                        clearPaymentFields();
                        jQuery('#place_order').click(function(e) {
                            e.preventDefault();
                            retrieveToken();
                        });
                        jQuery("#white_error_creditcard").show();
                        // TODO: Show the actual error
                    }
                    else {
                        // Successfully retrieved a token
                        clearPaymentFields();
                        jQuery('#white-token').val(response.id);
                        jQuery('#place_order').unbind('click');
                        jQuery('#place_order').click(function(e) {
                            return true;
                        });
                        jQuery('#place_order').click();
                    }
                }

                var retrieveToken = function () {
                    jQuery("#white_error_creditcard").hide();
                    if (jQuery('div.payment_method_white:first').css('display') === 'block') {
                        jQuery('#white-ccNo').val(jQuery('#white-ccNo').val().replace(/[^0-9 \.]+/g,''));
                        var white = new White('<?php echo $this->test_mode == 'yes'? $this->test_open_key : $this->live_open_key ?>');
                        white.createToken({
                            number: jQuery('#white-ccNo').val(),
                            exp_month: jQuery('#white-expMonth').val(),
                            exp_year: jQuery('#white-expYear').val(),
                            cvc: jQuery('#white-cvc').val()
                        }, whiteCallback);
                    } else {
                        jQuery('#place_order').unbind('click');
                        jQuery('#place_order').click(function(e) {
                            return true;
                        });
                        jQuery('#place_order').click();
                    }
                }

                function clearPaymentFields() {
                    jQuery('#white-ccNo').val('');
                    jQuery('#white-cvc').val('');
                    jQuery('#white-expMonth').val('');
                    jQuery('#white-expYear').val('');
                }
            </script>

            <script type="text/javascript" src="https://fast.whitepayments.com/whitejs/white.js"></script>
            <?php
        }

        /**
         * Process the payment and return the result
         *
         * @access public
         * @param int $order_id
         * @return array
         */
        function process_payment( $order_id ) {
            global $woocommerce;

            $order = new WC_Order($order_id);

            if ( 'yes' == $this->debug )
                $this->log->add( 'white', 'Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->notify_url );

            // White Args
            $white_args = array(
                'description' => "WooCommerce - ".$order->billing_email,
                'card' => $_POST['whiteToken'],
                'currency' => get_woocommerce_currency(),
                /**
                 * TODO: 
                 * Update the amount to consider currencies with varying
                 * minimum currency amounts .. I'm just using 100 here for
                 * USD and AED (both with 100 cents = 1 unit).
                 */
                'amount' => $order->get_total() * 100 
                );

            try {
                if ($this->test_mode == 'yes') {
                    White::setApiKey($this->test_secret_key);
                } else {
                    White::setApiKey($this->live_secret_key);
                }

                // Charge the token
                $charge = White_Charge::create($white_args);

                // No exceptions? Yaay, all done!
                $order->payment_complete();
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url( $order )
                );

            } catch (White_Error $e) {
                $woocommerce->add_error(__('Error:', 'woothemes') . $e->getMessage());
                return;
            }
        }

    }

    /**
     * Add the gateway to WooCommerce
     **/
    function add_white_gateway($methods){
        $methods[] = 'WC_Gateway_White';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_white_gateway');

}
