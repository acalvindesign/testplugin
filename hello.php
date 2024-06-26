<?php
/*
Plugin Name: Hello World Plugin
Plugin URI: http://example.com/hello-world-plugin
Description: A simple plugin that displays "Hello, World!" in the WordPress admin dashboard.
Version: 1.0
Author: Your Name
Author URI: http://example.com
License: GPL2
*/

// Hook for adding admin menus
add_action('admin_menu', 'hello_world_plugin_menu');

// Action function for the above hook
function hello_world_plugin_menu() {
    add_menu_page(
        'Hello World Plugin Page',        // Page title
        'Hello World',                    // Menu title
        'manage_options',                 // Capability
        'hello-world-plugin',             // Menu slug
        'hello_world_plugin_page_content' // Function to display the page content
    );
}

// Display the admin page content
function hello_world_plugin_page_content() {
    echo '<h1>Hello, World! Hello</h1>';
}
?>
