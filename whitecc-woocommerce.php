<?php
/*
Plugin Name: White Payments
Description: A full stack payment solution for the Middle East - www.whitepayments.com
Version: 0.3
Plugin URI: #
Author: White Payments
Author URI: http://www.whitepayments.com
License: Under GPL2   

*/


require_once('vendor/autoload.php'); 

add_action('plugins_loaded', 'woocommerce_tech_whitecc_init', 0);

function woocommerce_tech_whitecc_init() {

   if ( !class_exists( 'WC_Payment_Gateway' ) ) 
      return;

   /**
   * Localisation
   */
   load_plugin_textdomain('wc-tech-whitecc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
   
   /**
   * Authorize.net AIM Payment Gateway class
   */
   class WC_Tech_Whitecc extends WC_Payment_Gateway 
   {
      protected $msg = array();
      public function __construct(){

         $this->id               = 'whitecc';
         $this->method_title     = __('White Payments', 'wc-tech-whitecc');
       #  $this->icon             = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/logo.gif';
         $this->has_fields       = true;
         $this->init_form_fields();
         $this->init_settings();
         $this->currency         = $this->settings['currency'];
         $this->title            = $this->settings['title'];
         $this->description      = $this->settings['description'];
         $this->mode             = $this->settings['working_mode'];
         $this->pek              = $this->settings['pek'];
         $this->test_sec_key     = $this->settings['test_sec_key'];
         // $this->test_pub_key     = $this->settings['test_pub_key'];
         $this->live_sec_key     = $this->settings['live_sec_key'];
         // $this->live_pub_key     = $this->settings['live_pub_key'];
         $this->success_message  = $this->settings['success_message'];
         $this->failed_message   = $this->settings['failed_message'];
         $this->liveurl          = '';
         $this->testurl          = '';
         $this->msg['message']   = "";
         $this->msg['class']     = "";
       
         
         if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
             add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
          } else {
             add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
         }

         add_action('woocommerce_receipt_whitecc', array(&$this, 'receipt_page'));
         add_action('woocommerce_thankyou_whitecc',array(&$this, 'thankyou_page'));
         // add_action('wp_footer', array( &$this, 'tci_white_js'));
         
      }
      // public function tci_white_js()
      // {
      //    echo "
      //       <script>
      //    var btree = Braintree.create('".$this->pek     ."'); // Use Public Encryption key setting
      //    btree.onSubmitEncryptForm('checkout'); // Replace with the form name
      //    </script>";

      // }
      
      function init_form_fields()
      {

         $this->form_fields = array(
            'enabled'      => array(
                  'title'        => __('Enable/Disable', 'wc-tech-whitecc'),
                  'type'         => 'checkbox',
                  'label'        => __('Enable White Payments Module.', 'wc-tech-whitecc'),
                  'default'      => 'no'),
            'title'        => array(
                  'title'        => __('Title:', 'wc-tech-whitecc'),
                  'type'         => 'text',
                  'default'      => __('Credit card (Powered by White)', 'wc-tech-whitecc')),
            'description'  => array(
                  'title'        => __('Description:', 'wc-tech-whitecc'),
                  'type'         => 'textarea',
                  'description'  => __('This controls the description which the user sees during checkout.', 'wc-tech-whitecc'),
                  'default'      => __('Custom Description', 'wc-tech-whitecc')),
            'working_mode'    => array(
                  'title'        => __('API Mode'),
                  'type'         => 'select',
                  'options'      => array('live'=>'Live Mode', 'test'=>'Test Mode'),
                  'description'  => "Live/TestMode" ),
            'currency'    => array(
                  'title'        => __('Currency'),
                  'type'         => 'select',
                  'options'      => array('BHD'=>'BHD - Bahraini Dinar', 'USD'=>'USD - US Dollars'),
                  'description'  => "Select the currency in which the user will be charged" ),
            'pek'     => array(
                  'title'        => __('Public Encryption Key', 'wc-tech-whitecc'),
                  'type'         => 'textarea',
                  'description'  => __('This is Public Encryption Key')),
            'test_sec_key' => array(
                  'title'        => __('Test Secret Key', 'wc-tech-whitecc'),
                  'type'         => 'text',
                  'description'  =>  __('You can get your Test Secret Key from the White Dashboard', 'wc-tech-whitecc')),
            // 'test_pub_key' => array(
            //       'title'        => __('Test Publishable Key', 'wc-tech-whitecc'),
            //       'type'         => 'text',
            //       'description'  =>  __('Test Publishable Key', 'wc-tech-whitecc')),
            'live_sec_key' => array(
                  'title'        => __('Live Secret Key', 'wc-tech-whitecc'),
                  'type'         => 'text',
                  'description'  =>  __('You can get your Live Secret Key from the White Dashboard', 'wc-tech-whitecc')),
            // 'live_pub_key' => array(
            //       'title'        => __('Live Publishable Key', 'wc-tech-whitecc'),
            //       'type'         => 'text',
            //       'description'  =>  __('Live Publishable Key', 'wc-tech-whitecc')),
            'success_message' => array(
                  'title'        => __('Transaction Success Message', 'wc-tech-whitecc'),
                  'type'         => 'textarea',
                  'description'=>  __('Message to be displayed on successful transaction.', 'wc-tech-whitecc'),
                  'default'      => __('Your payment has been procssed successfully.', 'wc-tech-whitecc')),
            'failed_message'  => array(
                  'title'        => __('Transaction Failed Message', 'wc-tech-whitecc'),
                  'type'         => 'textarea',
                  'description'  =>  __('Message to be displayed on failed transaction.', 'wc-tech-whitecc'),
                  'default'      => __('Your transaction has been declined.', 'wc-tech-whitecc')),
         );
      }
      
      /**
       * Admin Panel Options
       * 
      **/
      public function admin_options()
      {
         echo '<h3>'.__('White Payments for WooCommerce', 'wc-tech-whitecc').'</h3>';
         echo '<p>'.__('Easily process payments using White:').'</p>';
         echo '<table class="form-table">';
         $this->generate_settings_html();
         echo '</table>';

      }
      
      /**
      *  Fields for White CC
      **/
      function payment_fields()
      {
         if ( $this->description ) 
            echo wpautop(wptexturize($this->description));
            echo "<input type='text' name='card[number]' style='margin:10px 0;padding:5px;width:100%;max-width:240px' placeholder='Card Number'><br/>";
            echo "<select name='card[exp_month]' style='margin:0;padding:5px;width:33.33%;max-width:80px;'><option value=''>MM</option>";
            for( $i =1; $i < 13; $i++){
               echo '<option value="'.str_pad($i, 2,'0',STR_PAD_LEFT).'">'.str_pad($i, 2,'0',STR_PAD_LEFT).'</option>'."\n";
            }
            echo "</select>";
            echo "<select name='card[exp_year]' style='margin:0;padding:5px;width:33.33%;max-width:80px;'><option value=''>YYYY</option>";
                  for( $i =0; $i < 15; $i++){
                     echo '<option value="'.(date('Y')+$i).'">'.(date('Y')+$i).'</option>'."\n";
                  }
            echo "</select>";
            echo "<input type='password' name='card[cvv]' maxlength='6' style='margin:0;padding:5px;width:33.33%;max-width:80px;' placeholder='CVV'><br/>";
           
           
      }
      
      /*
      * Basic Card validation
      */
      public function validate_fields()
      {
           global $woocommerce;
           if( !$this->isCreditCardNumber($_POST['card']['number']) ){
              return $woocommerce->add_error(__('(Card Error) Invalid card number.', 'wc-tech-whitecc'));
           }
           
           if( !$this->isCCVNumber($_POST['card']['cvv']) ){
              return $woocommerce->add_error(__('(Card Error) Invalid CVV number.', 'wc-tech-whitecc'));
           }
              
          
      }
      
      /*
      * Check card 
      */
      private function isCreditCardNumber($toCheck) 
      {
         if (!is_numeric($toCheck))
            return false;
        
        $number = preg_replace('/[^0-9]+/', '', $toCheck);
        $strlen = strlen($number);
        $sum    = 0;

        if ($strlen < 13)
            return false; 
            
        for ($i=0; $i < $strlen; $i++)
        {
            $digit = substr($number, $strlen - $i - 1, 1);
            if($i % 2 == 1)
            {
                $sub_total = $digit * 2;
                if($sub_total > 9)
                {
                    $sub_total = 1 + ($sub_total - 10);
                }
            } 
            else 
            {
                $sub_total = $digit;
            }
            $sum += $sub_total;
        }
        
        if ($sum > 0 AND $sum % 10 == 0)
            return true; 

        return false;
      }
        
      private function isCCVNumber($toCheck) 
      {
         $length = strlen($toCheck);
         return is_numeric($toCheck) AND $length > 2 AND $length < 5;
      }
    
      /*
      * Check expiry date
      */
      private function isCorrectExpireDate($date) 
      {
          
         if (is_numeric($date) && (strlen($date) == 4)){
            return true;
         }
         return false;
      }
      
      public function thankyou_page($order_id) 
      {
      
       
      }
      
      /**
      * Receipt Page
      **/
      function receipt_page($order)
      {
         echo '<p>'.__('Thank you for your order.', 'wc-tech-whitecc').'</p>';
        
      }
      
      /**
       * Process the payment and return the result
      **/
      function process_payment($order_id)
      {
         global $woocommerce;
         $order = new WC_Order($order_id);

         if($this->mode == 'live'){
           $white_key = $this->live_sec_key;
         }
         else{
           $white_key = $this->test_sec_key;
         }
         
                  
         White::setApiKey($white_key);
         
    
         try { 
            $white_response = White_Charge::create(array( 
               "amount" => round($order->order_total / 3.67, 3), # AED --> USD
               // TODO: Fix API to work with long floating numbers
               "currency" => $this->currency,
               "card" => array( 
                 "number" => $_REQUEST['card']['number'], 
                 "exp_month" => $_REQUEST['card']['exp_month'], 
                 "exp_year" => $_REQUEST['card']['exp_year'], 
                 "cvv" => $_REQUEST['card']['cvv']
                 ), 
               "description" => $this->description
             ));
             
            if ($order->status != 'completed') {
                $order->payment_complete();
                 $woocommerce->cart->empty_cart();

                 $order->add_order_note($this->success_message . 'Transaction ID: '. $white_response['tag'] );
                 unset($_SESSION['order_awaiting_payment']);
             }

              return array('result'   => 'success',
                 'redirect'  => get_site_url().'/checkout/order-received/'.$order->id.'/?key='.$order->order_key );
              
         } catch(White_Error_Card $e) { 
          // Since it's a decline, White_CardError will be caught 
             return   $woocommerce->add_error(__('(Card Error) '.$e->getMessage(), 'wc-tech-whitecc'));
          
         } catch (White_Error_Parameters $e) { 

          // Invalid parameters were supplied to White's API 
             return $woocommerce->add_error(__('(Parameter Error) '.$e->getMessage() , 'wc-tech-whitecc'));
         } catch (White_Error $e) { 

           return  $woocommerce->add_error(__('(General Error) '.$e->getMessage(), 'wc-tech-whitecc'));
          // Display a very generic error to the user
         } catch (Exception $e) { 

            return $woocommerce->add_error(__('(Unrelated Error) '.$e->getMessage() , 'wc-tech-whitecc'));
          // Something else happened, completely unrelated to White 
         }
         // No exception raised? 
        
         exit;
      }
      
   }

   /**
    * Add this Gateway to WooCommerce
   **/
   function woocommerce_add_tech_whitecc_gateway($methods) 
   {
      $methods[] = 'WC_Tech_Whitecc';
      return $methods;
   }

   add_filter('woocommerce_payment_gateways', 'woocommerce_add_tech_whitecc_gateway' );

   
   
}

