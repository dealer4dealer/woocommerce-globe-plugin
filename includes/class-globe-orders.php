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

    public function get_items($request)
    {
        $request['after'] = $request['date_modified'];

        add_filter('posts_where', function ($query) {   
            $query = str_replace('post_date', 'post_modified', $query);                
            return $query;
        }, 10, 1);

       return parent::get_items($request);
    }

    public function get_objects($query_args)
    {    
        $query  = new WP_Query();
        $result = $query->query( $query_args );

        $total_posts = $query->found_posts;
        if ( $total_posts < 1 ) {
          // Out-of-bounds, run the query again without LIMIT for total count.
          unset( $query_args['paged'] );
          $count_query = new WP_Query();
          $count_query->query( $query_args );
          $total_posts = $count_query->found_posts;
        }

        return array(
          'objects' => array_map( array( $this, 'get_object' ), $result ),
          'total'   => (int) $total_posts,
          'pages'   => (int) ceil( $total_posts / (int) $query->query_vars['posts_per_page'] ),
        );
    }
}