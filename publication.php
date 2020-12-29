<?php

/**
 * Class publication
 */
class publication_surlr {

    /**
     * publication option name
     */
    const PARSED_META_OPTION_NAME = 'surlr_parsed';

    /**
     * Post type supported
     */
    const POST_TYPE = [
        'page',
        'post',
    ];

    /**
     * active_plugin constructor.
     */
    public function __construct() {
        //
    }

    /**
     * Create redirect if right post
     *
     * @param int $post_id
     *
     * @return void
     */
    public function create_redirect( $post_id ) {
        if( $this->is_authorised_post_type( get_post_type( $post_id ) ) && !$this->is_already_created( $post_id ) ) {
            $database = new database_surlr();
            if( false !== $database->add_new_redirect( $post_id ) ) {
                update_post_meta( $post_id, self::PARSED_META_OPTION_NAME, 'true' ); //TODO: change this by token
            }
        }
    }

    /**
     * Check if the post type need an redirect URL
     *
     * @param string $post_type
     *
     * @return bool
     */
    protected function is_authorised_post_type( $post_type ) {

        return in_array( $post_type, self::POST_TYPE );
    }

    /**
     * Check if the post has already generated to prevent change status
     *
     * @param int $post_id
     *
     * @return bool
     */
    protected function is_already_created( $post_id ) {
        if( 'true' !== get_post_meta( $post_id, self::PARSED_META_OPTION_NAME, true ) ) { //Todo: change by token

            return false;
        }

        return true;
    }
}
