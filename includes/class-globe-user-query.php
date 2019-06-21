<?php
defined('ABSPATH') || exit;

class Globe_User_Query extends WP_User_Query
{
	private $date_modified;

	public function __construct( $query = null ) {
		parent::__construct($query);
	}

	public function setDateModified($date) 
	{
		$this->date_modified = $date;
	}

	public function query() {
		global $wpdb;

		$qv =& $this->query_vars;

		$query = "
        SELECT
        ID,         
        user_registered as date_created, 
        CASE 
        WHEN meta.meta_value IS NOT NULL AND meta.meta_key = 'last_update' 
        THEN FROM_UNIXTIME(meta.meta_value, '%%Y-%%m-%%d %%h:%%i:%%s')
        ELSE user_registered END
        AS %s
        FROM {$wpdb->users}
            AS users 
            LEFT JOIN (
                SELECT user_id, meta_key, meta1.meta_value
                FROM {$wpdb->usermeta} AS meta1 
                WHERE meta1.meta_key = 'last_update'
                ) as meta on users.ID = meta.user_id

            INNER JOIN (
            SELECT user_id, meta_key, meta_value
            FROM {$wpdb->usermeta} AS meta1
            WHERE meta1.meta_key = %s
            ) as meta1 on users.ID = meta1.user_id 

            WHERE users.user_registered > %s
            OR (FROM_UNIXTIME(meta.meta_value, '%%Y-%%m-%%d %%h:%%i:%%s') > %s)
        
        ORDER BY %s ASC
        ";

        $cap = $wpdb->get_blog_prefix() . 'capabilities';
        $key = 'date_modified';
        

        $this->request = $wpdb->prepare($query, array($key, $cap, $this->date_modified, $this->date_modified, $key));		 
        $this->request .= $this->query_limit;

		if ( is_array( $qv['fields'] ) || 'all' == $qv['fields'] ) {
			$this->results = $wpdb->get_results( $this->request );
		} else {
			$this->results = $wpdb->get_col( $this->request );
		}

		return $this->results;
		if ( isset( $qv['count_total'] ) && $qv['count_total'] )
			$this->total_users = (int) $wpdb->get_var( apply_filters( 'found_users_query', 'SELECT FOUND_ROWS()' ) );

		if ( !$this->results )
			return [];

		if ( 'all_with_meta' == $qv['fields'] ) {
			cache_users( $this->results );

			$r = array();
			foreach ( $this->results as $userid )
				$r[ $userid ] = new WP_User( $userid, '', $qv['blog_id'] );

			$this->results = $r;
		} elseif ( 'all' == $qv['fields'] ) {
			foreach ( $this->results as $key => $user ) {
				$this->results[ $key ] = new WP_User( $user, '', $qv['blog_id'] );
			}
		}
	}
}