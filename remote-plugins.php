<?php

/**
 * Plugin Name: Remote Plugins
 * Plugin URI: https://pradeepprajapat.netlify.app/
 * Description: This plugin using for manage the all plugin remotely using REST API 
 * Version: 1.0.0
 * Author: Pradeep Prajapati
 * Author URI: https://pradeepprajapat.netlify.app/
 * Text Domain: remote-plugins
 * Requires PHP: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
     exit;
}

if (! function_exists('get_plugins')) {
     require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Plugin activation hook
function remote_plugins_activate()
{
     // activation code here
}
register_activation_hook(__FILE__, 'remote_plugins_activate');

// Plugin deactivation hook
function remote_plugins_deactivate()
{
     // deactivation Code
}
register_deactivation_hook(__FILE__, 'remote_plugins_deactivate');

// Function for verify the token
function remote_plugins_verify_token()
{
     $headers = getallheaders(); // Changed from apache request_headers() for broader compatibility
     $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

     if (!$auth_header) {
          return false;
     }

     list($token_type, $token) = explode(' ', $auth_header);
     return $token === 'WxScDzJOYXEAcCmDSSDuieM48WpTeZGC';
}

// Function to list all available plugins
function remote_plugins_list_plugins()
{
     if (!remote_plugins_verify_token()) {
          return new WP_Error('unauthorized', 'Invalid bearer token', array('status' => 401));
     }

     $all_plugins = get_plugins();
     $plugins_list = array();

     foreach ($all_plugins as $plugin_file => $plugin_data) {
          $plugins_list[] = array(
               'name' => $plugin_data['Name'],
               'slug' => dirname($plugin_file),
               'status' => is_plugin_active($plugin_file) ? 'active' : 'inactive'
          );
     }

     return new WP_REST_Response($plugins_list, 200);
}

// Function for activate a plugin
function remote_plugins_activate_plugin(WP_REST_Request $request)
{
     if (!remote_plugins_verify_token()) {
          return new WP_Error('unauthorized', 'Invalid bearer token', array('status' => 401));
     }

     $plugin_slug = $request->get_param('plugin');
     if (!$plugin_slug) {
          return new WP_Error('missing param', 'Missing plugin slug', array('status' => 400));
     }

     $all_plugins = get_plugins();
     $plugin_file = '';

     foreach ($all_plugins as $file => $data) {
          if (dirname($file) == $plugin_slug) {
               $plugin_file = $file;
               break;
          }
     }

     if (!$plugin_file) {
          return new WP_Error('invalid_plugin', 'Invalid plugin slug', array('status' => 400));
     }

     if (is_plugin_active($plugin_file)) {
          return new WP_Error('already_active', 'Plugin is already active', array('status' => 400));
     }

     activate_plugin($plugin_file);
     return new WP_REST_Response(array('message' => 'Plugin activated successfully'), 200);
}

// Function to deactivate a plugin
function remote_plugins_deactivate_plugin(WP_REST_Request $request)
{
     if (!remote_plugins_verify_token()) {
          return new WP_Error('unauthorized', 'Invalid bearer token', array('status' => 401));
     }

     $plugin_slug = $request->get_param('plugin');
     if (!$plugin_slug) {
          return new WP_Error('missing param', 'Missing plugin slug', array('status' => 400));
     }

     $all_plugins = get_plugins();
     $plugin_file = '';

     foreach ($all_plugins as $file => $data) {
          if (dirname($file) == $plugin_slug) {
               $plugin_file = $file;
               break;
          }
     }

     if (!$plugin_file) {
          return new WP_Error('invalid Plugin', 'Invalid plugin slug', array('status' => 400));
     }

     if (!is_plugin_active($plugin_file)) {
          return new WP_Error('Already inactive', 'Plugin is already inactive', array('status' => 400));
     }

     deactivate_plugins($plugin_file);
     return new WP_REST_Response(array('message' => 'Plugin deactivated successfully'), 200);
}

// Registering the REST API routes
function remote_plugins_register_rest_routes()
{
     register_rest_route('kd/v1', '/listplugins', array(
          'methods' => 'GET',
          'callback' => 'remote_plugins_list_plugins',
     ));

     register_rest_route('kd/v1', '/activate', array(
          'methods' => 'POST',
          'callback' => 'remote_plugins_activate_plugin',
     ));

     register_rest_route('kd/v1', '/deactivate', array(
          'methods' => 'POST',
          'callback' => 'remote_plugins_deactivate_plugin',
     ));
}
add_action('rest_api_init', 'remote_plugins_register_rest_routes');

