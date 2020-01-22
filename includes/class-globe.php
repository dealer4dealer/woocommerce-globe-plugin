<?php

defined('ABSPATH') || exit;

class Globe
{
    private          $_version         = '1.0.3';
    protected static $_instance        = null;
    protected static $_productInstance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        $this->includes();
        $this->customers();
        $this->orders();
        $this->init();
    }

    public function init()
    {       
        add_action('rest_api_init', function () {
            register_rest_route('wc-globe/v1', 'version', array(
                'methods'     => WP_REST_Server::READABLE,
                'callback'    => array($this, 'globe_api_version'),
            ));
        });
    }

    public function globe_api_version($data)
    {
        return $this->_version;
    }

    public function includes()
    {
        include_once dirname(__FILE__) . '/class-globe-user-query.php';
        include_once dirname(__FILE__) . '/class-globe-customers.php';
        include_once dirname(__FILE__) . '/class-globe-orders.php';
    }

    public function customers()
    {
        Globe_Customers::instance();
    }

    public function orders()
    {
        Globe_Orders::instance();
    }
}
