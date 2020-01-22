<?php
defined('ABSPATH') || exit;

class Globe_Customers extends WC_REST_Customers_Controller
{
    protected static $_instance = null;
    public           $version   = '1';
    public           $namespace = 'wc-globe/v1';
    public           $base      = 'customers';
    private          $_request  = null;

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

            register_rest_route($this->namespace, $this->base, array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_item'),
                'permission_callback' => array($this, 'create_item_permissions_check'),
                'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
            ));

            register_rest_route($this->namespace, $this->base . '/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_item'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
                'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
            ));

            register_rest_route($this->namespace, $this->base . '/roles', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_roles'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params(),
            ));

            register_rest_route($this->namespace, $this->base . '/(?P<id>[\d]+)', array(
                'args' => array(
                    'id' => array(
                        'description' => __('Unique identifier for the resource.', 'woocommerce'),
                        'type'        => 'integer',
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                    'args'                => array(
                        'context' => $this->get_context_param(array('default' => 'view')),
                    ),
                ),
            ));
        });
    }

    public function get_items($request)
    {
        $date_modified = $request['date_modified'] ?: 0;

        if ($request['email']) {
            return parent::get_items($request);
        }

        $prepared_args = array();
        $prepared_args['exclude'] = $request['exclude'];
        $prepared_args['include'] = $request['include'];
        $prepared_args['order']   = $request['order'];
        $prepared_args['number']  = $request['per_page'];
        if ( ! empty( $request['offset'] ) ) {
            $prepared_args['offset'] = $request['offset'];
        } else {
            $prepared_args['offset'] = ( $request['page'] - 1 ) * $prepared_args['number'];
        }
        $orderby_possibles = array(
            'id'              => 'ID',
            'include'         => 'include',
            'name'            => 'display_name',
            'registered_date' => 'registered',
        );
        $prepared_args['orderby'] = $orderby_possibles[ $request['orderby'] ];
        $prepared_args['search']  = $request['search'];

        if ( '' !== $prepared_args['search'] ) {
            $prepared_args['search'] = '*' . $prepared_args['search'] . '*';
        }

        // Filter by email.
        if ( ! empty( $request['email'] ) ) {
            $prepared_args['search']         = $request['email'];
            $prepared_args['search_columns'] = array( 'user_email' );
        }

        // Filter by role.
        if ( 'all' !== $request['role'] ) {
            $prepared_args['role'] = $request['role'];
        }

        $prepared_args = apply_filters( 'woocommerce_rest_customer_query', $prepared_args, $request );


        $query = new Globe_User_Query( $prepared_args );
        $query->setDateModified($date_modified);
        $query->query();

        $users = array();
        foreach ( $query->results as $user ) {
            $data = $this->prepare_item_for_response( $user, $request );
            $users[] = $this->prepare_response_for_collection( $data );
        }

        $response = rest_ensure_response( $users );

        // Store pagination values for headers then unset for count query.
        $per_page = (int) $prepared_args['number'];
        $page = ceil( ( ( (int) $prepared_args['offset'] ) / $per_page ) + 1 );

        $prepared_args['fields'] = 'ID';

        $total_users = $query->get_total();
        if ( $total_users < 1 ) {
            // Out-of-bounds, run the query again without LIMIT for total count.
            unset( $prepared_args['number'] );
            unset( $prepared_args['offset'] );
            $count_query = new WP_User_Query( $prepared_args );
            $total_users = $count_query->get_total();
        }
        $response->header( 'X-WP-Total', (int) $total_users );
        $max_pages = ceil( $total_users / $per_page );
        $response->header( 'X-WP-TotalPages', (int) $max_pages );

        $base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );
        if ( $page > 1 ) {
            $prev_page = $page - 1;
            if ( $prev_page > $max_pages ) {
                $prev_page = $max_pages;
            }
            $prev_link = add_query_arg( 'page', $prev_page, $base );
            $response->link_header( 'prev', $prev_link );
        }
        if ( $max_pages > $page ) {
            $next_page = $page + 1;
            $next_link = add_query_arg( 'page', $next_page, $base );
            $response->link_header( 'next', $next_link );
        }

        return $response;
    }
}