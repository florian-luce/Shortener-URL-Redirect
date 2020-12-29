<?php
/*
Plugin Name: Shortener URL Redirect
Description: Generate short url for all posts, custom post types and pages to redirect your user to the long URL. No more long URLs share!
Version: 1.0.0
Author: Florian Luce
Author URI: florian-luce.info
Text Domain: shortener-url-redirect
License: GPLv2 or later
*/

/* ------ -
* Include plugin files
* --- -
*
*/
if( !class_exists( 'database_surlr' ) ) {
    require_once( 'database.php' );
}
if( !class_exists( 'redirect_surlr' ) ) {
    require_once( 'redirect.php' );
}

if( !class_exists( 'publication_surlr' ) ) {
    require_once( 'publication.php' );
}

if( !class_exists( 'metabox_surlr' ) ) {
    require_once( 'view/metabox.php' );
}

if( !class_exists( 'shortener_url_redirect_surlr' ) ) {
    /**
     * Class shortener_url_redirect
     */
    class shortener_url_redirect_surlr {

        /**
         * Plugin translate text domain
         */
        const TEXT_DOMAIN = 'shortener-url-redirect';

        const NOTICE_TYPE_NAME = [
            'error'   => 'notice-error',
            'warning' => 'notice-warning',
            'success' => 'notice-success',
            'info'    => 'notice-info',
        ];

        /**
         * Notices to display.
         * @var array $notices_list
         */
        protected $notices_list = array();

        /**
         * If need to display notice message.
         * @var bool $need_display_notices
         */
        protected $need_display_notices = false;

        /**
         * shortener_url_redirect constructor.
         */
        public function __construct() {

            register_activation_hook( __FILE__, [ $this, 'activate_plugin_actions' ] );

            register_deactivation_hook( __FILE__, [ $this, 'deactivate_plugin_actions' ] );
//        register_uninstall_hook( __FILE__, [ $this, 'deactivate_plugin_actions' ] ); //TODO: change deactivation function with this

            if( true === $this->need_display_notices ) {
                add_action( 'admin_notices', [ $this, 'display_admin_notice' ] );
            }

            add_action( 'init', [ $this, 'redirect' ] );

            add_action( 'transition_post_status', [ $this, 'create_publish' ], 10, 3 );
            add_action( 'after_delete_post', [ $this, 'delete_publish' ], 10, 2 );
        }

        /**
         * When the plugin is enable, set the cron event, and set notice to display
         *
         * @return void
         */
        public function activate_plugin_actions() {
            $activation = new database_surlr();
            $responses  = $activation->create_tables();

            // Set notice admin
            if( is_array( $responses ) && !empty( $responses ) ) {
                $this->need_display_notices = true;
                foreach( $responses as $response ) {
                    $this->notices_list[] = array(
                        self::NOTICE_TYPE_NAME['info'] => $response,
                    );
                }
            }
        }

        /**
         * When the plugin is disable, unset the cron event
         */
        public function deactivate_plugin_actions() {
            $db = new database_surlr();
            $db->delete_tables();
        }

        /**
         * Call to redirect URLs
         */
        public function redirect() {
            $redirect = new redirect_surlr();
            $redirect->redirect_if_need();
        }

        /**
         * Call to create an redirection if right post type
         *
         * @param string  $new_status
         * @param string  $old_status
         * @param WP_Post $post
         *
         * @return void
         */
        public function create_publish( $new_status, $old_status, $post ) {
            if( 'publish' === $new_status && 'publish' !== $old_status ) {
                $publication = new publication_surlr();
                $publication->create_redirect( $post->ID );
            }
        }

        /**
         * Remove redirect after delete publication
         *
         * @param int $post_id
         *
         * @return void
         */
        public function delete_publish( $post_id ) {
            $database = new database_surlr();
            $database->remove_redirect( $post_id );
        }

        /**
         * Display admin notice message banner
         *
         * @retun void
         */
        public function display_admin_notice() {
            if( is_array( $this->notices_list ) && !empty( $this->notices_list ) ) {
                foreach( $this->notices_list as $current_notice ) {
                    echo '<div class="notice  ' . key( $current_notice ) . ' is-dismissible">';
                    echo '<p>' . $current_notice[ key( $current_notice ) ] . '</p>';
                    echo '</div>';
                }
            }
        }
    }

    new shortener_url_redirect_surlr();
}
