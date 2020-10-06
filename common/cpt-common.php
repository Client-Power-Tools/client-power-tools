<?php

namespace Client_Power_Tools\Core\Common;

/**
* Adds the Client and Client Manager user roles and capabilities, and assigns
* all CPT capabilities to admins.
*/
function cpt_add_roles() {

  add_role(
    'cpt-client',
    'Client'
  );

  add_role(
    'cpt-client-manager',
    'Client Manager',
    [
      'cpt-view-clients'    => true,
      'cpt-manage-clients'  => true,
    ]
  );

  $role = get_role( 'administrator' );

  $role->add_cap( 'cpt-view-clients' );
  $role->add_cap( 'cpt-manage-clients' );
  $role->add_cap( 'cpt-manage-team' );
  $role->add_cap( 'cpt-manage-settings' );

}

add_action( 'init', __NAMESPACE__ . '\cpt_add_roles' );


/**
* Checks to see whether the current user is a client. Returns true if the current
* user has the cpt-client role, false if not.
*
* If no user ID is provided, checks to see whether a user is logged-in with the
* cpt-client role.
*/
function cpt_is_client( $user_id = null ) {

  if ( is_null( $user_id ) && is_user_logged_in() ) {
    $user_id = get_current_user_id();
  }

  $user = get_userdata( $user_id );

  if ( in_array( 'cpt-client', $user->roles ) ) {
    return true;
  } else {
    return false;
  }

}


function cpt_get_client_profile_url( $user_id ) {
  return add_query_arg( 'user_id', $user_id, admin_url( 'admin.php?page=cpt' ) );
}


function cpt_get_client_dashboard_url() {
  $page_id = get_option( 'cpt_client_dashboard_page_selection' );
  return get_permalink( $page_id );
}


function cpt_is_client_dashboard() {

  global $wp_query;

  $client_dashboard_id  = get_option( 'cpt_client_dashboard_page_selection' );
  $this_page_id         = isset( $wp_query->post->ID ) ? $wp_query->post->ID : false;

  if ( $this_page_id && $client_dashboard_id == $this_page_id ) {
    return true;
  } else {
    return false;
  }

}


function cpt_get_client_name( $user_id ) {

  if ( ! $user_id ) { return; }

  $user_meta = get_userdata( $user_id );

  if ( $user_meta->first_name && $user_meta->last_name ) {
    $client_name = $user_meta->first_name . ' ' . $user_meta->last_name;
  } else {
    $client_name = $user_meta->display_name;
  }

  return $client_name;

}


// Returns an array with the user's details.
function cpt_get_client_data( $user_id ) {

  if ( ! $user_id ) { return; }

  $userdata = get_userdata( $user_id );

  $client_data = [
    'user_id'     => $user_id,
    'first_name'  => get_user_meta( $user_id, 'first_name', true ),
    'last_name'   => get_user_meta( $user_id, 'last_name', true ),
    'email'       => $userdata->user_email,
    'client_id'   => get_user_meta( $user_id, 'cpt_client_id', true ),
    'status'      => get_user_meta( $user_id, 'cpt_client_status', true ),
  ];

  return $client_data;

}


function cpt_get_email_card( $title = null, $content = null, $button_txt = 'Go', $button_url = null ) {

  $card_style     = 'border: 1px solid #ddd; box-sizing: border-box; font-family: Jost, Helvetica, Arial, sans-serif; margin: 30px 3px 30px 0; padding: 30px; max-width: 500px;';
  $h2_style       = 'margin-top: 0;';
  $button_style   = 'background-color: #eee; border: 1px solid #ddd; box-sizing: border-box; display: block; margin: 0; padding: 1em; width: 100%; text-align: center;';

  ob_start();

    echo '<div class="cpt-card" align="left" style="' . $card_style . '">';

      if ( ! empty( $title ) ) {
        echo '<h2 style="' . $h2_style . '">' . $title . '</h2>';
      }

      if ( ! empty( $content ) ) {
        echo $content;
      }

      if ( ! empty( $button_url ) ) {
        echo '<a class="button" href="' . $button_url . '" style="' . $button_style . '">' . $button_txt . '</a>';
      }

    echo '</div>';

  return ob_get_clean();

}


/**
* Checks for a transient with the results of an action, and if one exists,
* outputs a notice. In the admin, this is a standard WordPress admin notice. On
* the front end, this is a modal.
*/
function cpt_get_notices( $transient_key ) {

  if ( ! $transient_key ) { return; }

  $result = get_transient( $transient_key );

  if ( ! empty( $result ) ) {

    if ( is_admin() ) {

      if ( is_wp_error( $result ) ) {
        $wrapper = '<div class="cpt-notice notice notice-error is-dismissible">';
      } else {
        $wrapper = '<div class="cpt-notice notice notice-success is-dismissible">';
      }

    } else {

      ob_start();

        ?>

          <button class="cpt-notice-dismiss-button">
            <img src="<?php echo CLIENT_POWER_TOOLS_DIR_URL; ?>frontend/images/cpt-dismiss-button.svg" height="25px" width="25px" />
          </button>

        <?php

      $dismiss_button = ob_get_clean();

      $wrapper = '<div class="cpt-inline-modal">' . "\n" . $dismiss_button;

    }



    echo $wrapper;
    echo '<p>' . __( $result ) . '</p>';
    echo '</div>';

  }

  delete_transient( $transient_key );

}

add_action( 'admin_notices', __NAMESPACE__ . '\cpt_get_notices' );
