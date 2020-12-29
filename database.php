<?php

/**
 * Class database
 */
class database_surlr {

    /**
     * Database version
     */
    const DB_VERSION = '1.0';

    /**
     * Short URL length
     */
    const SHORT_URL_LENGHT = 6;

    /**
     * Database time format
     */
    const DB_TIME_FORMAT = 'Y-n-j G:i:s';

    /**
     * Database option name
     */
    const DB_OPTION_NAME = 'surlr_db_version';

    /**
     * active_plugin constructor.
     */
    public function __construct() {
        //
    }

    /**
     * Get the redirect table name
     *
     * @return string
     */
    protected function get_redirect_table_name() {
        global $wpdb;

        return $wpdb->prefix . "surlr_redirect_url";
    }

    /**
     * Get SQL structure instructions
     *
     * @param $table_name
     *
     * @return string
     */
    protected function get_sql_structure( $table_name ) {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE $table_name (
id int(9) NOT NULL AUTO_INCREMENT,
create_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
publish_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
update_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
delete_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
short_url varchar(55) NOT NULL,
post_id int(14) NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;";
    }

    /**
     * Create (or update) tables
     *
     * @return array
     */
    public function create_tables() {
        $sql = $this->get_sql_structure( $this->get_redirect_table_name() );
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        add_option( self::DB_OPTION_NAME, self::DB_VERSION );
        $return_messages   = dbDelta( $sql );
        $return_messages[] = __( 'Database updated to ' . self::DB_VERSION, 'shortener-url-redirect' );

        return $return_messages;
    }

    public function update_table_if_need() { //TODO
        $this->need_update_table();

        $this->update_tables();
    }

    protected function need_update_table() { //TODO

    }

    /**
     * Update tables
     *
     * @return array
     */
    protected function update_tables() {

        return $this->create_tables();
    }

    /**
     * Delete tables
     *
     * @return bool|int
     */
    public function delete_tables() {
        global $wpdb;
        $table_name = $this->get_redirect_table_name();
        $sql        = "DROP TABLE IF EXISTS $table_name";
        delete_option( self::DB_OPTION_NAME );

        return $wpdb->query( $sql );
    }

    /**
     * Return current date in format for DB
     *
     * @return false|string
     */
    protected function get_current_time() {
        $current_timestamp = time();

        return date( self::DB_TIME_FORMAT, $current_timestamp );
    }

    /**
     * Add new redirect URL
     *
     * @param int $post_id
     *
     * @return bool|int
     */
    public function add_new_redirect( $post_id ) {
        return $this->insert_new_redirect_row( $this->create_short_url(), $post_id );
    }

    /**
     * Add new redirect in DB
     *
     * @param string $short_url
     * @param int    $post_id
     *
     * @return bool|int
     */
    protected function insert_new_redirect_row( $short_url, $post_id ) {
        $short_url = sanitize_title( $short_url );
        $post_id   = sanitize_title( $post_id );
        global $wpdb;

        return $wpdb->insert( $this->get_redirect_table_name(), array(
            'short_url' => $short_url,
            'post_id'   => $post_id,
            'create_at' => $this->get_current_time(),
        ), array(
                                  '%s',
                                  '%d',
                                  '%s',
                              ) );
    }

    /**
     * Generate unique random short URL
     *
     * @return string
     */
    protected function create_short_url() {
        $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen( $characters );
        $random_url       = '';
        for( $i = 0; $i < self::SHORT_URL_LENGHT; $i ++ ) {
            $random_url .= $characters[ rand( 0, $charactersLength - 1 ) ];
        }

        // Check if exist
        if( !is_null( $this->get_redirect_url( $random_url ) ) ) {
            $this->create_short_url();
            //TODO: Maybe to prevent infinite call, increment SHORT_URL_LENGHT to +1
        }

        return $random_url;
    }

    /**
     * Return long URL
     *
     * @param string $short_url
     *
     * @return string|null
     */
    public function get_redirect_url( $short_url ) {
        $response = $this->get_redirect_url_in_db_by_short_url( $short_url );
        if( !is_null( $response ) && !empty( $response->post_id ) && false !== get_permalink( $response->post_id ) ) {
            return get_permalink( $response->post_id );
        }

        return null;
    }

    /**
     * Return short URL
     *
     * @param int $post_id
     *
     * @return string|null
     */
    public function get_short_url( $post_id ) {
        $response = $this->get_redirect_url_in_db_by_post_id( $post_id );
        if( !is_null( $response ) && !empty( $response->short_url ) ) {
            return $response->short_url;
        }

        return null;
    }

    /**
     * Remove redirect after delete publication
     *
     * @param int $post_id
     *
     * @return void
     */
    public function remove_redirect( $post_id ) {
        $this->soft_delete( $post_id );
    }

    /**
     * Add delete time in 'delete_at' column, the data persist in DB
     *
     * @param $post_id
     *
     * @return void
     */
    protected function soft_delete( $post_id ) {
        $post_id = sanitize_title( $post_id );
        global $wpdb;

        $wpdb->update( $this->get_redirect_table_name(), array(
            'delete_at' => $this->get_current_time(),
        ), array( 'post_id' => $post_id ), array(
                           '%s',
                       ), array( '%d' ) );
    }

    /**
     * The redirect row is permanently deleted to the DB
     *
     * @param $post_id
     *
     * @return void
     */
    protected function hard_delete( $post_id ) {
        //TODO
    }

    /**
     * Retrieve in DB the line contain the redirect data if exist by short_url
     *
     * @param string $short_url
     *
     * @return array|object|void|null
     */
    protected function get_redirect_url_in_db_by_short_url( $short_url ) {
        global $wpdb;
        $short_url  = sanitize_title_for_query( $short_url );
        $table_name = $this->get_redirect_table_name();
        $query      = $wpdb->prepare( "SELECT * FROM $table_name WHERE `short_url` = %s", array(
            $short_url,
        ) );

        return $wpdb->get_row( $query );
    }

    /**
     * Retrieve in DB the line contain the redirect data if exist by post_id
     *
     * @param int $post_id
     *
     * @return array|object|void|null
     */
    protected function get_redirect_url_in_db_by_post_id( $post_id ) {
        global $wpdb;
        $post_id    = sanitize_title_for_query( $post_id );
        $table_name = $this->get_redirect_table_name();
        $query      = $wpdb->prepare( "SELECT * FROM $table_name WHERE `post_id` = %s", array(
            $post_id,
        ) );

        return $wpdb->get_row( $query );
    }
}
