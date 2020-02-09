<?php
/**
* @package HubtelCheckoutAPIClientPlugin
*/

/*
Plugin Name: Hubtel Checkout API Client Plugin
Plugin URI: https://github.com/gagyekum/hubtel-checkout-api-client-wp-plugin
Description: Hubtel checkout api wrapper
Version: 1.0.0
Author: Gideon Agyekum
Author URI: https://github.com/gagyekum
License: GPLv2 or later
Text Domain: hubtel-checkout-api-client-plugin
*/

//defined('ABSPATH') or die('You can\'t access this file');
if (!function_exists('add_action')) {
    echo 'You cannot access this file';
    exit;
}

class HubtelCheckoutAPIClientPlugin {

    private static $instance;
    
    private const BASE_URI = '';
    private const API_KEY = '';


    private function __construct() {
        add_action( 'init_hubtel_checkout', array( $this, 'create_invoice' ) );
        add_action( 'check_hubtel_payment_status', array( $this, 'get_invoice_status' ) );
    }

    /**
     * Creates an instance of HubtelCheckoutAPIClientPlugin
     *
     * @access public
     * @return HubtelCheckoutAPIClientPlugin
     */
    public function get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Triggers the checkout by creating an invoice.
     *
     * @access public
     * @param  array $invoice_data  Invoice data
     * @return array('status' => 'success', 'checkout_url' => 'https://hubtel.com/online-chekout').
     */
    public function create_invoice(array $invoice_data) {

        $response = $this->make_api_post_request('/pos/onlinecheckout/items/initiate', $invoice_data);

        $status_code = $response['status_code'];

        if ($status_code == 200) {
            $response_body = $response['body'];

            return array(
                'status'   => 'success',
                'checkout_url' => $response_body['data']['checkoutDirectUrl']
            );
        }

        return $this->get_http_error_response($status_code);
    }

    /**
     * Checks invoice payment status
     * 
     * @access public
     * @param string $invoice_number Invoice Identifier
     * @return array('status' => 'success', 'payment_status' => 'pending')
     */
    public function get_invoice_status(string $invoice_number) {
        $response = $this->make_api_get_request("/pos/onlinecheckout/items/$invoice_number");

        $status_code = $response['status_code'];

        if ($status_code == 200) {
            $response_body = $response['body'];

            return array(
                'status'   => 'success',
                'payment_status' => $response_body['data']['paymentStatus']
            );
        }

        return $this->get_http_error_response($status_code);
    }

    private function get_headers() {
        return array(
            'Authorization' => 'Bearer ' . self::API_KEY,
            'Content-Type' => 'application/json'
        );
    }

    private function make_api_request(string $path, string $method = 'get', array $data = NULL) {
        $url = self::BASE_URI . $path;
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
            $response_body_args = array('errors' => $e->getMessage());
        }

        return array(
            'status_code' => $response_code,
            'body' => $response_body_args
        );
    }

    private function make_api_post_request(string $path, array $data) {
        return $this->make_api_request($path, 'post', $data);
    }

    private function make_api_get_request(string $path) {
        return $this->make_api_request($path);
    }

    private function get_http_error_response(int $status_code) {
        $response = array();

        switch ($status_code) {
            case 400:
                $response['status'] = 'bad_request';
                $response['message'] = 'Invalid invoice data.';
                break;

            case 401:
                $response['status'] = 'unauthorized';
                $response['message'] = 'Invalid or no API key provided in authorization header.';
                break;

            case 404:
                $response['status'] = 'not_found';
                $response['message'] = 'Requested URL or resource is not found.';
                break;
            
            default:
                $response['status'] = 'internal_server_error';
                $response['message'] = 'An error occurred while processing your request.';
                break;
        }

        return $response;
    }
}

if (class_exists('HubtelCheckoutAPIClientPlugin'))
    HubtelCheckoutAPIClientPlugin::get_instance();