<?php
/*
Plugin Name: My Plugin
Plugin URI: https://example.com/my-plugin
Description: testing.
Version: 0.3.1
Author: A Calvin Design
Author URI: https://example.com
License: GPL2
*/
// Include Hello World functionality
require_once plugin_dir_path(__FILE__) . 'hello-world.php';

// Include Database Tables functionality
require_once plugin_dir_path(__FILE__) . 'database-tables.php';
add_action('admin_menu', 'database_tables_plugin_menu');
function database_tables_plugin_menu() {
    add_menu_page(
        'Database Tables Plugin Page',      // Page title
        'Database Tables',                  // Menu title
        'manage_options',                   // Capability
        'database-tables-plugin',           // Menu slug
        'database_tables_plugin_page_content' // Function to display the page content
    );
    
    add_submenu_page(
        'database-tables-plugin',
        'Hello World Plugin Page',        // Page title
        'Hello World',                    // Menu title
        'manage_options',                 // Capability
        'hello-world-plugin',             // Menu slug
        'hello_world_plugin_page_content' // Function to display the page content
    );
}
function my_plugin_update_check() {
    $current_version = '0.3.2'; // Current plugin version
    $update_check_url = 'https://raw.githubusercontent.com/acalvindesign/testplugin/main/update.json';

    $response = wp_remote_get($update_check_url);

    if (is_wp_error($response)) {
        return;
    }

    $update_data = json_decode(wp_remote_retrieve_body($response));

    if (version_compare($current_version, $update_data->new_version, '<')) {
        add_action('in_plugin_update_message-my-plugin/my-plugin.php', function() use ($update_data) {
            echo '<br /><strong>' . esc_html($update_data->update_message) . '</strong>';
        });

        add_filter('site_transient_update_plugins', function($transient) use ($update_data) {
            $plugin_slug = plugin_basename(__FILE__);

            $plugin_data = array(
                'slug' => dirname($plugin_slug),
                'new_version' => $update_data->new_version,
                'url' => $update_data->package,
                'package' => $update_data->package,
            );

            $transient->response[$plugin_slug] = (object) $plugin_data;

            return $transient;
        });
    }
}
add_action('admin_init', 'my_plugin_update_check');
