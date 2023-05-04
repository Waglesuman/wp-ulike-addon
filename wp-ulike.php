<?php
/**
 * Plugin Name:       WP ULike addon
 * Plugin URI:        https://codepixelzmedia.com/givewp-addon
 * Description:       This addon creates a new table that stores the data when the user like comment.
 * Version:           1.0.0
 * Author:            Codepixelz Media
 * Author URI:        https://codepixelzmedia.com/
 * Text Domain:       wp-ulike-addon
 * Domain Path:       /languages/
 * Tested up to: 	  6.2
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

// Define the table name
global $wpdb;
$table_name = $wpdb->prefix . 'cpm_table';
define('SLACK_WEBHOOK', 'https://hooks.slack.com/services/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');


// Create the table on plugin activation
function wp_ulike_addon_create_table()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'cpm_table';

  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id int(11) NOT NULL AUTO_INCREMENT,
		comment_id int(11) NOT NULL,
		post_id int(11) NOT NULL,
		status varchar(11) NOT NULL,
		message varchar(255) NOT NULL,
		PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

  dbDelta($sql);
}
register_activation_hook(__FILE__, 'wp_ulike_addon_create_table');

// Add the JavaScript code to update and insert data into the table
function wp_ulike_addon_enqueue_scripts()
{
  // wp_enqueue_script('wp-ulike-addon', plugin_dir_url(__FILE__) . '/assets/js/wp-ulike-addon.js', array('jquery'), '1.0.0', true);
  wp_enqueue_script('wp-ulike-addon', plugin_dir_url(__FILE__) . '/wp-ulike-addon.js', array('jquery'), '1.0.0', true);
  wp_localize_script('wp-ulike-addon', 'wp_ulike_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
  wp_enqueue_script('wp-ajax');
}
add_action('wp_enqueue_scripts', 'wp_ulike_addon_enqueue_scripts');



function cpm_custom_ulike_table()
{
  $id_to_be_updated_arr = explode(",", $_POST['curr_ulike_data']);
  $comment_id = (int) ($id_to_be_updated_arr[0]);
  $curr_post_id = (int) $id_to_be_updated_arr[1];

  // Get the current user nickname
  $user = wp_get_current_user();
  $nickname = $user->nickname;

  global $wpdb;
  $table_name = $wpdb->prefix . 'cpm_table';



  // Make your message
  $message = ['payload' => json_encode(['text' => $nickname . ' has disliked your comment. View the comment: ' . get_permalink($curr_post_id)])];
  // Use curl to send your message
  $c = curl_init(SLACK_WEBHOOK);
  curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($c, CURLOPT_POST, true);
  curl_setopt($c, CURLOPT_POSTFIELDS, $message);
  curl_exec($c);
  curl_close($c);



  $wpdb->delete(
    $table_name,
    [
      'comment_id' => $comment_id,
      'post_id' => $curr_post_id,
      'status' => 'like',
      'message' => $nickname . ' has liked the post'
    ]
  );

  $wpdb->insert(
    $table_name,
    [
      'comment_id' => $comment_id,
      'post_id' => $curr_post_id,
      'status' => 'unlike',
      'message' => $nickname . ' has unliked the post'
    ]
  );


}
add_action('wp_ajax_cpm_custom_ulike_table', 'cpm_custom_ulike_table');


// remove table 
function cpm_custom_ulike_table_remove()
{
  $id_to_be_updated_arr = explode(",", $_POST['curr_ulike_data']);
  $comment_id = (int) ($id_to_be_updated_arr[0]);
  $curr_post_id = (int) $id_to_be_updated_arr[1];


  // Get the current user nickname
  $user = wp_get_current_user();
  $nickname = $user->nickname;

  global $wpdb;
  $table_name = $wpdb->prefix . 'cpm_table';


  // Make your message
  $message = ['payload' => json_encode(['text' => $nickname . ' liked your comment. View the comment: ' . get_permalink($curr_post_id)])];
  // Use curl to send your message
  $c = curl_init(SLACK_WEBHOOK);
  curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($c, CURLOPT_POST, true);
  curl_setopt($c, CURLOPT_POSTFIELDS, $message);
  curl_exec($c);
  curl_close($c);


  $wpdb->delete(
    $table_name,
    [
      'comment_id' => $comment_id,
      'post_id' => $curr_post_id,
      'status' => 'unlike',
      'message' => $nickname . ' has unliked the post'
    ]
  );

  $wpdb->insert(
    $table_name,
    [
      'comment_id' => $comment_id,
      'post_id' => $curr_post_id,
      'status' => 'like',
      'message' => $nickname . ' has liked the post'
    ]
  );
}

add_action('wp_ajax_cpm_custom_ulike_table_remove', 'cpm_custom_ulike_table_remove');



function cpm_javascript_code()
{
  ?>

  <script>
    jQuery(document).ready(function () {

      jQuery('.wp_ulike_btn').each(function (index, item) {
        jQuery(item).on("click", item, function (event) {
          event.preventDefault();

          /* This code is sending an AJAX request to the server to update the data in the custom table
          created by the plugin. */
          var curr_ulike_data = jQuery(this).attr('data-ulike-id') + "," + <?php echo get_queried_object_id(); ?>


          if (jQuery(item).hasClass('wp_ulike_btn_is_active')) {
            jQuery.ajax({
              url: ajaxurl,
              method: 'POST',
              data: {
                action: 'cpm_custom_ulike_table',
                curr_ulike_data: curr_ulike_data
              },
              beforeSend: function (respond) { },
              success: function (response) {
                console.log(response);
              },
              error: function (response) { },
              complete: function (respond) { },
            });
          }
        });
      });

      // here is te code to remove from table

      jQuery('.wp_ulike_btn').each(function (index, item) {
        jQuery(item).on("click", item, function (event) {
          event.preventDefault();

          /* This code is sending an AJAX request to the server to update the data in the custom table
          created by the plugin. */
          var curr_ulike_data = jQuery(this).attr('data-ulike-id') + "," + <?php echo get_queried_object_id(); ?>

          if (!jQuery(item).hasClass('wp_ulike_btn_is_active')) {
            jQuery.ajax({
              url: ajaxurl,
              method: 'POST',
              data: {
                action: 'cpm_custom_ulike_table_remove',
                curr_ulike_data: curr_ulike_data
              },
              beforeSend: function (respond) { },
              success: function (response) {
                console.log(response);
              },
              error: function (response) { },
              complete: function (respond) { },
            });
          }
        });
      });
    });

  </script>
  <?php

}
add_action('wp_footer', 'cpm_javascript_code');
