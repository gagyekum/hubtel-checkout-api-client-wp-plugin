<?php
/**
* @package SlydepayMerchantAPIClientPlugin
*/

/*
Plugin Name: Slydepay Merchant API Client Plugin
Plugin URI: https://github.com/gagyekum/slydepay-merchant-api-client-wp-plugin
Description: Slydepay Merchant api wrapper
Version: 1.0.0
Author: Gideon Agyekum
Author URI: https://github.com/gagyekum
License: GPLv2 or later
Text Domain: slydepay-merchant-api-client-plugin
*/

//defined('ABSPATH') or die('You can\'t access this file');
if (!function_exists('add_action')) {
    echo 'You cannot access this file';
    exit;
}

class SlydepayMerchantAPIClientPlugin {

    private static $instance;
    private const BASE_URI = 'https://app.slydepay.com.gh';

    private function __construct() {
        add_action( 'init_slydepay_merchant', array( $this, 'get_pay_options' ) );
        add_action( 'init_slydepay_merchant', array( $this, 'create_invoice' ) );
        add_action( 'init_slydepay_merchant', array( $this, 'send_invoice' ) );
        add_action( 'init_slydepay_merchant', array( $this, 'check_payment_status' ) );
        add_action( 'init_slydepay_merchant', array( $this, 'confirm_transaction' ) );
    }

    /**
     * Creates an instance of SlydepayMerchantAPIClientPlugin
     *
     * @access public
     * @return SlydepayMerchantAPIClientPlugin slydepay merchant api client instance
     */
    public function get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function get_headers() {
        return array(
            'Content-Type' => 'application/json'
        );
    }

    private function get_url($path) {
        return self::BASE_URI . $path;
    }

    private function make_api_request(string $path, string $method = 'get', array $data = NULL) {
        $url = $this->get_url($path);
        $args = array('headers' => $this->get_headers());

        if (!is_null($data)) 
            $args['body'] = $data;

        try {
            if (strtolower($method) == 'post')
                $response = wp_remote_post($url, $args);
            else
                $response = wp_remote_get($url, $args);
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $response_body_args = json_decode($response_body, true);
        } catch (Exception $e) {
            $response_code = 500;
            $response_body_args = array(
                'errorCode' => 'INTERNAL_SERVER_ERROR',
                'errorMessage' => $e->getMessage()
            );
        }

        return array(
            'status_code' => $response_code,
            'body' => $response_body_args
        );
    }

    /**
     * Retrieves a list of all possible payment options on Slydepay.
     *
     * @access public
     * @param array $merchant_info Merchant details
     * @return array Http response 
     */
    public function get_pay_options(array $merchant_info) {
        return $this->make_api_request('/api/merchant/invoice/payoptions', 'post', $merchant_info);
    }

    /**
     * Creates an invoice and sends back slydepay pay token.
     *
     * @access public
     * @param array $invoice_details Invoice details
     * @return array Http response
     */
    public function create_invoice($invoice_details) {
        return $this->make_api_request('/api/merchant/invoice/create', 'post', $invoice_details);
    }

    /**
     * Sends an invoice priorly generated to you customer.
     *
     * @access public
     * @param array $invoice_details Invoice details
     * @return array Http response
     */
    public function send_invoice($invoice_details) {
        return $this->make_api_request('/api/merchant/invoice/send', 'post', $invoice_details);
    }

    /**
     * Checks the status of the payment.
     *
     * @access public
     * @param array $payment_info Payment details
     * @return array Http response
     */
    public function check_payment_status($payment_info) {
        return $this->make_api_request('/api/merchant/invoice/checkstatus', 'post', $payment_info);
    }

    /**
     * Confirms customer transaction.
     *
     * @access public
     * @param array $transaction_details Transaction details
     * @return array Http response
     */
    public function confirm_transaction() {
        return $this->make_api_request('/api/merchant/transaction/confirm', 'post', $transaction_details);
    }
}

if (class_exists('SlydepayMerchantAPIClientPlugin'))
    SlydepayMerchantAPIClientPlugin::get_instance();