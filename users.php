<?php

/**
 * Plugin Name: Custom user fields
 * Description: upload image and extended with a phone field and linkedin field
 * Version: 1.0
 * Author: Daniël Groen
 * Author URI: https://danielgroen.nl
 **/

add_action('after_setup_theme', function () {
  add_filter('user_contactmethods', function () {
    return [];
  });
});


add_action('admin_head', function () {
  echo '<style>
          .user-profile-picture,
          [aria-label="Administrator"] strong,
          [aria-label="Subscriber"] strong,
          [aria-label="Editor"] strong,
          [aria-label="Author"] strong,
          [aria-label="Contributor"] strong,
          .user-description-wrap { display: none !important; }
        </style>';
});

// add phone number, linkedin and jobtitle
// usage: the_author_meta( 'phone' );
add_action('show_user_profile', 'my_show_extra_profile_fields');
add_action('edit_user_profile', 'my_show_extra_profile_fields');
function my_show_extra_profile_fields($user)
{ ?>
  <h3>Extra profile information</h3>
  <table class="form-table">

    <tr>
      <th><label for="job">Job title</label></th>
      <td>
        <input type="text" name="job" id="job" value="<?php echo esc_attr(get_the_author_meta('job', $user->ID)); ?>" class="regular-text" /><br />
        <span class="description">Please enter your job title.</span>
      </td>
    </tr>
    <tr>
      <th><label for="phone">Phone Number</label></th>
      <td>
        <input type="text" name="phone" id="phone" value="<?php echo esc_attr(get_the_author_meta('phone', $user->ID)); ?>" class="regular-text" /><br />
        <span class="description">Please enter your phone number.</span>
      </td>
    </tr>

    <tr>
      <th><label for="linkedin">Linkedin url</label></th>
      <td>
        <input type="url" name="linkedin" id="linkedin" value="<?php echo esc_attr(get_the_author_meta('linkedin', $user->ID)); ?>" class="regular-text" /><br />
        <span class="description">Please enter your linkedin url.</span>
      </td>
    </tr>
  <?php
}

add_action('personal_options_update', 'my_save_extra_profile_fields');
add_action('edit_user_profile_update', 'my_save_extra_profile_fields');
function my_save_extra_profile_fields($user_id)
{
  if (!current_user_can('edit_user', $user_id))
    return false;

  update_user_meta($user_id, 'job', $_POST['job']);
  update_user_meta($user_id, 'phone', $_POST['phone']);
  update_user_meta($user_id, 'linkedin', $_POST['linkedin']);
}

/**
 * Custom Avatar Without a Plugin
 */

// 1. Enqueue the needed scripts.
add_action("admin_enqueue_scripts", "ayecode_enqueue");
function ayecode_enqueue($hook)
{
  // Load scripts only on the profile page.
  if ($hook === 'profile.php' || $hook === 'user-edit.php') {
    add_thickbox();
    wp_enqueue_script('media-upload');
    wp_enqueue_media();
  }
}

// 2. Scripts for Media Uploader.
function ayecode_admin_media_scripts()
{
  ?>
    <script>
      jQuery(document).ready(function($) {
        $(document).on('click', '.avatar-image-upload', function(e) {
          e.preventDefault();
          var $button = $(this);
          var file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select or Upload an Custom Avatar',
            library: {
              type: 'image' // mime type
            },
            button: {
              text: 'Select Avatar'
            },
            multiple: false
          });
          file_frame.on('select', function() {
            var attachment = file_frame.state().get('selection').first().toJSON();
            $button.siblings('#ayecode-custom-avatar').val(attachment.sizes.thumbnail.url);
            $button.siblings('.custom-avatar-preview').attr('src', attachment.sizes.thumbnail.url);
          });
          file_frame.open();
        });
      });
    </script>
  <?php
}
add_action('admin_print_footer_scripts-profile.php', 'ayecode_admin_media_scripts');
add_action('admin_print_footer_scripts-user-edit.php', 'ayecode_admin_media_scripts');

// 3. Adding the Custom Image section for avatar.
function custom_user_profile_fields($profileuser)
{
  ?>
    <tr>
      <th>
        <label for="image"><?php _e('Custom Local Avatar', 'ayecode'); ?></label>
      </th>
      <td>
        <?php
        // Check whether we saved the custom avatar, else return the default avatar.
        $custom_avatar = get_the_author_meta('ayecode-custom-avatar', $profileuser->ID);
        if ($custom_avatar == '') {
          $custom_avatar = get_avatar_url($profileuser->ID);
        } else {
          $custom_avatar = esc_url_raw($custom_avatar);
        }
        ?>
        <img style="width: 96px; height: 96px; display: block; margin-bottom: 15px;" class="custom-avatar-preview" src="<?php echo $custom_avatar; ?>">
        <input type="text" name="ayecode-custom-avatar" id="ayecode-custom-avatar" value="<?php echo esc_attr(esc_url_raw(get_the_author_meta('ayecode-custom-avatar', $profileuser->ID))); ?>" class="regular-text" />
        <input type='button' class="avatar-image-upload button-primary" value="<?php esc_attr_e("Upload Image", "ayecode"); ?>" id="uploadimage" /><br />
        <span class="description">
          <?php _e('Please upload a custom avatar for your profile, to remove the avatar simple delete the URL and click update.', 'ayecode'); ?>
        </span>
      </td>
    </tr>
  </table>
<?php
}
add_action('show_user_profile', 'custom_user_profile_fields', 10, 1);
add_action('edit_user_profile', 'custom_user_profile_fields', 10, 1);


// 4. Saving the values.
add_action('personal_options_update', 'ayecode_save_local_avatar_fields');
add_action('edit_user_profile_update', 'ayecode_save_local_avatar_fields');
function ayecode_save_local_avatar_fields($user_id)
{
  if (current_user_can('edit_user', $user_id)) {
    if (isset($_POST['ayecode-custom-avatar'])) {
      $avatar = esc_url_raw($_POST['ayecode-custom-avatar']);
      update_user_meta($user_id, 'ayecode-custom-avatar', $avatar);
    }
  }
}
define('BP_AVATAR_THUMB_WIDTH', 250); //change this with your desired thumb width

wp_list_comments('avatar_size=250');
// 5. Set the uploaded image as default gravatar.
add_filter('get_avatar_url', 'ayecode_get_avatar_url', 10, 3);
function ayecode_get_avatar_url($url, $id_or_email, $args)
{
  $id = '';
  if (is_numeric($id_or_email)) {
    $id = (int) $id_or_email;
  } elseif (is_object($id_or_email)) {
    if (!empty($id_or_email->user_id)) {
      $id = (int) $id_or_email->user_id;
    }
  } else {
    $user = get_user_by('email', $id_or_email);
    $id = !empty($user) ?  $user->data->ID : '';
  }
  //Preparing for the launch.
  $custom_url = $id ?  get_user_meta($id, 'ayecode-custom-avatar', true) : '';

  // If there is no custom avatar set, return the normal one.
  if ($custom_url == '' || !empty($args['force_default'])) {
    return 'https://2.gravatar.com/avatar/?s=356&d=mm&r=g';
  } else {
    return str_replace('-150x150', '', esc_url_raw($custom_url));
  }
}

// Log out if not role administrator
add_action('init', function () {
  if (is_user_logged_in() == 1) {
    if (current_user_can('subscriber')) {
      wp_logout();
      wp_redirect('/', 302);
    }
  }
});
