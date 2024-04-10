<?php
defined('ABSPATH') || exit;

class Globe_Orders extends WC_REST_Orders_Controller
{
    protected static $_instance = null;
    public           $version   = '1';
    public           $namespace = 'wc-globe/v1';
    public           $base      = 'orders';

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        add_action('rest_api_init', function () {
            register_rest_route($this->namespace, $this->base, array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_items'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params(),
            ));

            register_rest_route($this->namespace, $this->base . '/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_item'),
                'permission_callback' => array($this, 'get_item_permissions_check'),
                'args'                => $this->get_collection_params(),
            ));
        });
    }

    /**
	 * Set alternate default values
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();
		$params['per_page']['default']      = 50;
		$params['order']['default']         = 'asc';
		$params['orderby']['default']       = 'modified';
		$params['dates_are_gmt']['default'] = true;
		return $params;
	}
}