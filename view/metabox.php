<?php

/**
 * Class metabox
 */
abstract class metabox_surlr {

    /**
     * Set up and add the meta box.
     *
     * @return void
     */
    public function add() {
        $database  = new database_surlr();
        $short_url = $database->get_short_url( get_the_ID() );

        if( !is_null( $short_url ) ) {
            $publication_list = new publication_surlr();
            foreach( $publication_list::POST_TYPE as $screen ) {
                add_meta_box( 'surlr-metabox-redirect-info', __( 'Short URL', 'shortener-url-redirect' ), [ self::class, 'html' ], $screen, 'side' );
            }
        }
    }

    /**
     * Display the metabox HTML content.
     *
     * @param \WP_Post $post Post object.
     *
     * @return void
     */
    public function html( $post ) {
        $redirect  = new redirect_surlr();
        $domain    = $redirect->get_domain();
        $database  = new database_surlr();
        $short_url = $domain . $database->get_short_url( $post->ID );

        echo '<input type="text" id="surlr-redirect-url" value="' . $short_url . '" readonly>';
        echo '<p class="button" onclick="myFunction()">' . __( 'Copy', 'shortener-url-redirect' ) . '</p>';

        ?>
      <script>
          function myFunction() {
              let copyText = document.getElementById( "surlr-redirect-url" );
              copyText.select();
              copyText.setSelectionRange( 0, 99999 ) /* For mobile devices */
              document.execCommand( "copy" );
          }
      </script>
        <?php
    }
}

add_action( 'add_meta_boxes', [ 'metabox_surlr', 'add' ] );

//TODO
//https://developer.wordpress.org/plugins/metadata/custom-meta-boxes/
//add_action( 'save_post', [ 'metabox_surlr', 'save' ] );
