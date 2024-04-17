<?php

namespace iflow\iflow;

use DateTime;
use iflow\iflow\Helper;

if (!defined('ABSPATH')) {
    exit; 
}

class iflowConnector
{

    private $api_key, $api_secret, $serviceE, $product_hold, $logger;
    public $credentials_checked;
    protected $last_error;

    const SOURCE_FOR_API = 'woocommerce@'.IFLOW_VERSION;


    public function __construct()
    {

        $this->api_user = get_option('IFLOW_API_USER');
        $this->api_key = get_option('IFLOW_API_KEY');
        $this->servicio = get_option('IFLOW_SERVICIO');
        //$this->product_hold = get_option('iflow_api_idproduct');
        $this->logger = wc_get_logger();
        //$this->credentials_checked = get_option('iflow_credentials_check');

    }


    public function get_api_user()
    {
        return $this->api_user;
    }

    public function get_api_key()
    {
        return $this->api_key;
    }

    public function get_servicio()
    {
        return $this->servicio;
    }
    


    public function getLastError()
    {
        return $this->last_error;
    }

    


}


