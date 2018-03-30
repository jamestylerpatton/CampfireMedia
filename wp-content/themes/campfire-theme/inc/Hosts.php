<?php

class Hosts
{
    function __construct()
    {
        add_filter('get_avatar', array($this, 'host_avatar'), 10, 5);
        add_action('admin_head', array($this, 'remove_personal_options'));
    }

    /**
     * Change User Avatar to ACF image field
     */
    public function host_avatar($avatar, $id_or_email, $size, $default, $alt)
    {
        $user = false;

        if ( is_numeric( $id_or_email ) ) {
            $id = (int) $id_or_email;
            $user = get_user_by( 'id' , $id );
        } elseif ( is_object( $id_or_email ) ) {
            if ( ! empty( $id_or_email->user_id ) ) {
                $id = (int) $id_or_email->user_id;
                $user = get_user_by( 'id' , $id );
            }
        } else {
            $user = get_user_by( 'email', $id_or_email );   
        }

        if (!$user) {
            return $avatar;
        }

        // Get the user id
        $user_id = $user->ID;

        // Get the file id
        $image_id = get_user_meta($user_id, 'host_avatar', true);

        // Bail if we don't have a local avatar
        if ( ! $image_id ) {
            return $avatar;
        }
        // Get the file size
        $image_url  = wp_get_attachment_image_src( $image_id, 'thumbnail' );

        // Get the file url
        $avatar_url = $image_url[0];

        // Get the img markup
        $avatar = '<img alt="' . $alt . '" src="' . $avatar_url . '" class="avatar avatar-' . $size . '" height="' . $size . '" width="' . $size . '"/>';

        return $avatar;
    }

    function remove_personal_options()
    {
        echo '<script type="text/javascript">jQuery(document).ready(function($) {          
        $(\'form#your-profile tr.user-rich-editing-wrap\').remove(); // remove the "Visual Editor" field

        $(\'form#your-profile tr.user-syntax-highlighting-wrap\').remove(); // remove the "Syntax Highlight" field
          
        $(\'form#your-profile tr.user-admin-color-wrap\').remove(); // remove the "Admin Color Scheme" field
          
        $(\'form#your-profile tr.user-comment-shortcuts-wrap\').remove(); // remove the "Keyboard Shortcuts" field
          
        $(\'form#your-profile tr.user-admin-bar-front-wrap\').remove(); // remove the "Toolbar" field
          
        $(\'form#your-profile tr.user-language-wrap\').remove(); // remove the "Language" field
          
        $(\'form#your-profile tr.user-profile-picture\').remove(); // remove the "Profile Picture" field         
        });</script>';
    }
}

new Hosts();
