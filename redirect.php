<?php

/**
 * Class redirect
 */
class redirect_surlr {

    /**
     * Current site domain
     *
     * @var string $domain
     */
    protected $domain;

    /**
     * Current URL path visited
     *
     * @var string $current_url_path
     */
    protected $current_url_path;

    /**
     * active_plugin constructor.
     */
    public function __construct() {
        $this->current_url_path = $this->get_current_url_path();
        $this->domain           = $this->get_current_domain();
    }

    /**
     * Redirect if the current URL path has an redirect URL instruction
     */
    public function redirect_if_need() {
        $database     = new database_surlr();
        $redirect_url = $database->get_redirect_url( $this->get_current_url_path() );
        if( !is_null( $redirect_url ) ) {
            $this->redirect_url( $redirect_url );
        }
    }

    /**
     * Redirect to the URL
     *
     * @param $url
     */
    protected function redirect_url( $url ) {
        wp_redirect( $url );
        exit();
    }

    /**
     * Return current URL path (without domain)
     *
     * @return string
     */
    protected function get_current_url_path() {

        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Return current domain (with '/' to the end)
     *
     * @return string
     */
    protected function get_current_domain() {

        return trailingslashit( site_url() );
    }

    /**
     * Return current domain
     *
     * @return string
     */
    public function get_domain() {

        return $this->domain;
    }
}
