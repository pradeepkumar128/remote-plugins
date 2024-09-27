<?php
// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Code to run when the plugin is uninstalled
delete_option('remote_plugins_option');
