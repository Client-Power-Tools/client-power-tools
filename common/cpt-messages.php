<?php

namespace Client_Power_Tools\Core\Common;
use Client_Power_Tools\Core\Frontend;


/*
 * This has to be loaded as a common file in order to use the admin-post action
 * hook. And it would probably be even more confusing to load it as a common
 * file but store it in the frontend directory/namespace. Even that sentence is
 * confusing.
 */


function cpt_messages( $user_id ) {

  if ( ! $user_id ) { return; }
  if ( ! cpt_is_messages() && ! is_admin() ) { return; }

  // Return if the module is disabled.
  if ( ! get_option( 'cpt_module_messaging' ) ) { return; }

  echo '<div class="cpt-messages">';

    cpt_message_list( $user_id );

    echo '<h2>' . __( 'New Message' ) . '</h2>';
    echo '<div id="cpt-new-message-form">';
      cpt_new_message_form( $user_id );
    echo '</div>';

  echo '</div>';

}

function cpt_messages_page_title( $title, $id ) {

  $client_dashboard_id = get_option( 'cpt_client_dashboard_page_selection' );

  if ( cpt_is_messages() && $id == $client_dashboard_id && in_the_loop() ) {
    $title = $title . ': Messages';
  }

  return $title;

}

add_filter( 'the_title', __NAMESPACE__ . '\cpt_messages_page_title', 10, 2 );


function cpt_message_list( $user_id ) {

  if ( ! $user_id ) { return; }

  /**
  * Removes the the_title filter so it doesn't execute within the
  * nested query for client messages.
  */
  // remove_filter( 'the_title', __NAMESPACE__ . '\cpt_messages_page_title' );

  $paged = isset( $_GET[ 'paged' ] ) ? sanitize_key( intval( $_GET[ 'paged' ] ) ) : get_query_var( 'paged' );

  $args = [
    'meta_key'        => 'cpt_clients_user_id',
    'meta_value'      => $user_id,
    'paged'           => $paged,
    'post_type'       => 'cpt_message',
  ];

  $cpt_messages = new \WP_Query( $args );

  if ( $cpt_messages->have_posts() ) :

    ob_start();

      while ( $cpt_messages->have_posts() ) : $cpt_messages->the_post();

        $message_id       = get_the_ID();
        $message_classes  = [ 'cpt-message' ];
        $message_meta     = '<p><small>';

        switch ( get_the_author_meta( 'ID' ) ) {

          case ( get_current_user_id() ):

            $message_classes[]   = 'my-message';
            $message_meta       .= __( 'Sent', 'client-power-tools' );

            break;

          case ( $user_id ):

            $message_classes[]   = 'client-message not-my-message';

                                   /**
                                    * translators:
                                    * 1: sender's name
                                    */
            $message_meta       .= sprintf( __( 'Received from %1$s', 'client-power-tools' ), get_the_author() );

            break;

          default:

            $message_classes[]   = 'not-my-message';

                                   /**
                                    * translators:
                                    * 1: sender's name
                                    */
            $message_meta       .= sprintf( __( 'Sent by %1$s', 'client-power-tools' ), get_the_author() );

        }

        if ( get_post_meta( $message_id, 'cpt_status_update_request' ) ) {
          $message_classes[]     = 'status-update-request';
        }

        $message_meta .= ' on ' . get_the_date( 'F jS, Y, \a\t g:i a' ) . '</small></p>';

        echo '<div id="cpt-message-' . $message_id . '" class="' . implode( ' ', $message_classes ) . '">';

          echo '<div class="cpt-message-content">';

            if ( get_the_title() && get_the_title() !== 'Untitled' ) {
              echo '<h3 class="cpt-message-title">' . get_the_title() . '</h3>';
            }

            if ( ! get_post_meta( $message_id, 'cpt_status_update_request' ) ) {
              the_content();
            }

          echo '</div>';

          echo '<div class="cpt-message-meta">' . $message_meta . '</div>';

        echo '</div>';

      endwhile;

      $big = 999999;

      echo paginate_links([
        'base'    => str_replace( $big, '%#%', get_pagenum_link( $big, false ) ),
        'format'  => '?paged=%#%',
        'current' => max( 1, $paged ),
        'total'   => $cpt_messages->max_num_pages,
      ]);

    echo ob_get_clean();

  else :

    /**
     * translators:
     * 1: html
     * 2: html
     */
    printf( __( '%1$sNo messages found.%2$s' , 'client-power-tools' ), '<p>', '</p>' );

  endif;

}


function cpt_new_message_form( $user_id ) {

  $editor_args  = [
    'media_buttons' => false,
    'quicktags'     => false,
    'textarea_name' => 'message',
    'tinymce'       => [
      'toolbar1'    => 'formatselect, bold, italic, bullist, numlist, blockquote, outdent, indent, link, unlink',
    ],
  ];

  if ( is_admin() ) {

    ob_start();

      ?>

        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">

          <?php wp_nonce_field( 'cpt_new_message_added', 'cpt_new_message_nonce' ); ?>
          <input name="action" value="cpt_new_message_added" type="hidden">
          <input name="clients_user_id" value="<?php echo $user_id; ?>" type="hidden">

          <table class="form-table" role="presentation">
            <tbody>
              <tr>
                <th scope="row">
                  <label for="subject_line"><?php _e( 'Subject Line', 'client-power-tools' ); ?></label>
                </th>
                <td>
                  <input name="subject_line" id="subject_line" class="large-text" type="text">
                  <p><?php
                    /*
                     * translators:
                     * 1: sender's name
                     */
                    printf( __( 'If you leave this field empty, the subject line will be "New message from %1$s".', 'client-power-tools' ), cpt_get_name( get_current_user_id() ) );
                  ?></p>
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="message"><?php _e( 'Message', 'client-power-tools' ); ?></label>
                </th>
                <td>
                  <?php \wp_editor( '', 'cpt-message-editor', $editor_args ); ?>
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <?php _e( 'Options', 'client-power-tools' ); ?>
                </th>
                <td>
                  <fieldset>

                    <?php if ( get_option( 'cpt_send_message_content' ) == false ) { ?>

                      <label for="send_message_content">
                        <input name="send_message_content" id="send_message_content" type="checkbox" value="1">
                        <?php _e( 'Send message content.', 'client-power-tools' ); ?>
                      </label>
                      <p class="description"><?php _e( 'If checked, the client will receive the actual message by email instead of a notification with a prompt to log into their client portal. This is less secure.', 'client-power-tools' ); ?></p>

                    <?php } else { ?>

                      <label for="send_notification_only">
                        <input name="send_notification_only" id="send_notification_only" type="checkbox" value="1">
                        <?php _e( 'Send notification only.', 'client-power-tools' ); ?>
                      </label>
                      <p class="description"><?php _e( 'If checked, the client will receive an email letting them know they have a message, but they will have to log into their client dashboard to view the body of the message. This is more secure.', 'client-power-tools' ); ?></p>

                    <?php } ?>

                  </fieldset>
                </td>
              </tr>
            </tbody>
          </table>

          <p class="submit">
            <input name="submit" id="submit" class="button button-primary" type="submit" value="<?php _e( 'Send Message', 'client-power-tools' ); ?>">
          </p>

        </form>

      <?php

    $new_message_form = ob_get_clean();

    \_WP_Editors::enqueue_scripts();
    \_WP_Editors::editor_js();
    \print_footer_scripts();

  } else {

    ob_start();
      \wp_editor( '', 'cpt-message-editor', $editor_args );
    $message_editor = ob_get_clean();

    ob_start();

      ?>

        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">

          <?php wp_nonce_field( 'cpt_new_message_added', 'cpt_new_message_nonce' ); ?>
          <input name="action" value="cpt_new_message_added" type="hidden">
          <input name="clients_user_id" value="<?php echo $user_id; ?>" type="hidden">

          <?php

            /**
             * translators:
             * 1: html
             * 2: html
             * 3: html
             * 4: html & text field
             */
            printf( __( '%1$sSubject Line %2$s(optional)%3$s', 'client-power-tools' ),
              '<label for="subject_line">',
              '<small>',
              '</small></label><input name="subject_line" id="subject_line" class="large-text" type="text"><p style="line-height: 0; margin-bottom: 1em;"> </p>'
            );

            /**
             * translators:
             * 1: html
             * 2: html
             * 3: html & wp_editor textarea field
             */
            printf( __( '%1$sMessage %2$s(required)%3$s', 'client-power-tools' ),
              '<label for="message">',
              '<small>',
              '</small></label>' . $message_editor . '<p style="line-height: 0; margin-bottom: 1em;"> </p>'
            );

          ?>

          <p class="submit">
            <input name="submit" id="submit" class="button button-primary" type="submit" value="<?php _e( 'Send Message', 'client-power-tools' ); ?>">
          </p>

        </form>

      <?php

    $new_message_form = ob_get_clean();

  }

  echo $new_message_form;

}


function cpt_process_new_message() {

  if ( isset( $_POST[ 'cpt_new_message_nonce' ] ) && wp_verify_nonce( $_POST[ 'cpt_new_message_nonce' ], 'cpt_new_message_added' ) ) {

    $post_title       = wp_strip_all_tags( sanitize_text_field( $_POST[ 'subject_line' ] ) );
    $post_content     = wp_kses_post( $_POST[ 'message' ] );
    $clients_user_id  = sanitize_key( intval( $_POST[ 'clients_user_id' ] ) );

    // Figures out whether to send the full content of this message.
    $send_msg_content_default = get_option( 'cpt_send_message_content' );

    if ( ! $send_msg_content_default ) {

      if ( isset( $_POST[ 'send_message_content' ] ) && $_POST[ 'send_message_content' ] == 1 ) {
        $send_this_msg_content = true;
      } else {
        $send_this_msg_content = false;
      }

    } else {

      if ( isset( $_POST[ 'send_notification_only' ] ) && $_POST[ 'send_notification_only' ] == 1 ) {
        $send_this_msg_content = false;
      } else {
        $send_this_msg_content = true;
      }

    }

    /**
    * Note. When creating a new message, for the post slug we generate an md5
    * hash from the timestamp plus a random integer, making the message URL
    * pretty much impossible to guess.
    */
    $new_message = [
      'post_name'     => md5( time() . random_int( 0, PHP_INT_MAX ) ),
      'post_title'    => $post_title,
      'post_content'  => $post_content,
      'post_status'   => 'publish',
      'post_type'     => 'cpt_message',
      'meta_input'    => [
        'cpt_clients_user_id'       => $clients_user_id,
        'cpt_send_message_content'  => $send_this_msg_content,
      ],
    ];

    $post = wp_insert_post( $new_message, $wp_error );

    if ( is_wp_error( $post ) ) {

      /**
       * translators:
       * 1: error message
       */
      $result = sprintf( __( 'Message could not be sent. Error message: %1$s', 'client-power-tools' ), $post->get_error_message() );

    } else {

      cpt_message_notification( $post );

      $result = __( 'Message sent!', 'client-power-tools' );

    }

    set_transient( 'cpt_new_message_result', $result, 45  );

    wp_redirect( $_POST[ '_wp_http_referer' ] );
    exit;

  } else {

    die();

  }

}

add_action( 'admin_post_cpt_new_message_added', __NAMESPACE__ . '\cpt_process_new_message' );


function cpt_message_notification( $message_id ) {

  if ( ! $message_id ) { return; }

  $send_this_msg_content = get_post_meta( $message_id, 'cpt_send_message_content', true );

  $msg_obj          = get_post( $message_id );
  $sender_id        = $msg_obj->post_author;
  $clients_user_id  = get_post_meta( $message_id, 'cpt_clients_user_id', true );
  $client_data      = cpt_get_client_data( $clients_user_id );

  $from_name        = get_the_author_meta( 'display_name', $msg_obj->post_author );
  $from_email       = get_the_author_meta( 'user_email', $msg_obj->post_author );

  $headers[]        = 'Content-Type: text/html; charset=UTF-8';
  $headers[]        = 'From: ' . $from_name . ' <' . $from_email . '>';

                      /**
                       * translators:
                       * 1: sender's name
                       */
  $subject          = $msg_obj->post_title ? $msg_obj->post_title : sprintf( __( 'You have a new message from %1$s', 'client-power-tools' ), $from_name );

  if ( $send_this_msg_content ) {

    $message = get_the_content( null, false, $msg_obj );

  } else {

    if ( $sender_id == $clients_user_id ) {

      $to           = $client_data[ 'manager_email' ];

      $message      = sprintf( __( '%1$sTo read your message, please visit your client dashboard.%2$s', 'client-power-tools' ), '<p>', '</p>' );
      $button_url   = cpt_get_client_profile_url( $clients_user_id ) . '#cpt-message-' . $message_id;

    } else {

      $to           = $client_data[ 'email' ];

      $message      = sprintf( __( '%1$sTo read this message, please view the client page.%2$s', 'client-power-tools' ), '<p>', '</p>' );
      $button_url   = cpt_get_client_dashboard_url() . '#cpt-message-' . $message_id;

    }

    $button_txt     = __( 'Go to Message', 'client-power-tools' );
    $message        = cpt_get_email_card( $subject, $message, $button_txt, $button_url );

  }

  wp_mail( $to, $subject, $message, $headers );

}
