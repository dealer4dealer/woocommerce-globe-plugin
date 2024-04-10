<?php

defined('ABSPATH') || exit;

class Globe
{
    private          $_version         = '1.1.0';
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
        if ( ! $this->isXcoreRequest() ) {
            return;
		}

        $this->includes();
        $this->customers();
        $this->orders();
        $this->init();
    }

    public function init()
    {       
        add_action('rest_api_init', function () {
            register_rest_route('wc-globe/v1', 'version', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'globe_api_version'),
                'permission_callback' => '__return_true',
            ));
        });
    }

    public function initHooks()
    {
        add_filter('woocommerce_rest_shop_order_object_query', [$this, 'xcoreFilterByDateModified'], 10, 2);
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

    /**
	 * We rely heavily on the ability to retrieve data by its modification date. This
	 * adds the functionality to do so for both orders and products.
	 * Since Woocommerce 5.8.0 the option has been added to filter products by modified
	 * date using modified_after
	 *
	 * @param $args
	 * @param $request
	 *
	 * @return array
	 */
	public function xcoreFilterByDateModified( $args, $request )
	{
		$args['date_query'][0]['inclusive'] = true;

		if ($request->get_param('modified_after')) {
			return $args;
		}

		$objectId      = $request->get_param( 'id' );
		$date_modified = $request->get_param( 'date_modified' ) ?: '2001-01-01 00:00:00';

		if ( $objectId ) {
			$args['post__in'][] = $objectId;
		}

		$args['date_query'][0]['column']    = 'post_modified_gmt';
		$args['date_query'][0]['after']     = $date_modified;

		return $args;
	}

    private function isXcoreRequest()
    {
        if (empty($_SERVER['REQUEST_URI'])) {
            return false;
        }

        $restPrefix = trailingslashit(rest_get_url_prefix());
        return (false !== strpos($_SERVER['REQUEST_URI'], $restPrefix . 'wc-globe/'));
    }
}
