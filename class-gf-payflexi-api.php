<?php
if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class GFPayflexiApi
{
    public $plugin_name;
    
    protected $public_key;
    
    protected $secret_key;

    public function __construct($config)
    {
        $this->plugin_name = 'PayFlexi Addon for Gravity Forms';
        $this->secret_key = $config->secret_key ?? '';
        $this->public_key = $config->public_key ?? '';
    }
    /**
     * Send request to the PayFlexi Api
     * 
     * @param string $endpoint API request path
     * @param array $args API request arguments
     * @param string $method API request method
     * @param string $domain API request uri
     * 
     * @return object|null JSON decoded transaction object. NULL on API error.
     */
    public function send_request(
        $endpoint,
        $args = array(),
        $method = 'post',
        $domain = 'https://api.payflexi.co/'
    ) {
        $uri = "{$domain}{$endpoint}";

        $arg_array = array(
            'method'    => strtoupper($method),
            'body'      => json_encode($args),
            'timeout'   => 60,
            'headers'   => $this->get_headers(),
            'sslverify' => false, //Set to true on production
        );

        $res = wp_remote_request($uri, $arg_array);

        if (is_wp_error($res)) {
            throw new Exception(sprintf(__('You had an HTTP error connecting to %s', 'gravityformspayflexi'), $this->name));
        }

        $body = json_decode($res['body'], true);

        if ($body !== null) {
            if ($body['errors']) {
                throw new Exception("{$body['message']}");
            } else {
                return $body;
            }
        } else { // Un-decipherable message
            throw new Exception(sprintf(__('There was an issue connecting with the payment processor. Try again later.', 'gravityformspayflexi'), $this->name));
        }

        return false;
    }

    /**
     * Validate Webhook Signature
     *
     * @param $input
     * @return boolean
     */
    public function validate_webhook($input)
    {
        return $_SERVER['HTTP_X_PAYFLEXI_SIGNATURE'] == hash_hmac('sha512', $input, $this->secret_key);
    }

    /**
     * Generates the headers to pass to API request.
     */
    public function get_headers()
    {
        return apply_filters('gf_payflexi_request_headers', [
            'Authorization' => "Bearer {$this->secret_key}",
            'Content-Type' =>  "application/json"
        ]);
    }
}
