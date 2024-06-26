<?php
/*
Plugin Name: Database Tables Plugin
Plugin URI: http://example.com/database-tables-plugin
Description: A simple plugin that displays all database table names in the WordPress admin dashboard with pagination.
Version: 1.3
Author: Your Name
Author URI: http://example.com
License: GPL2
*/

// Hook for adding admin menus
add_action('admin_menu', 'database_tables_plugin_menu');

// Action function for the above hook
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
        'Update Plugin',                  // Page title
        'Update Plugin',                  // Menu title
        'manage_options',                 // Capability
        'update-database-tables-plugin',  // Menu slug
        'update_database_tables_plugin_page' // Function to display the page content
    );
}

// Display the admin page content
function database_tables_plugin_page_content() {
    global $wpdb; // Access the global $wpdb object

    // Check if a table name is provided in the URL
    if (isset($_GET['table'])) {
        $table_name = sanitize_text_field($_GET['table']);
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        display_table_contents($table_name, $page);
    } else {
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        display_table_list($page);
    }
}

// Function to display the list of database tables with pagination
function display_table_list($page = 1) {
    global $wpdb;
    $tables_per_page = 10;
    $offset = ($page - 1) * $tables_per_page;
    
    // Get the list of tables
    $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);

    echo '<div class="wrap">';
    echo '<h1>Database Tables</h1>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr><th>Table Name</th><th>Entries</th></tr></thead>';
    echo '<tbody>';

    $total_tables = count($tables);
    $total_pages = ceil($total_tables / $tables_per_page);
    $displayed_tables = array_slice($tables, $offset, $tables_per_page);

    foreach ($displayed_tables as $table) {
        $table_name = esc_html($table[0]);
        $url = add_query_arg(array('table' => $table_name), menu_page_url('database-tables-plugin', false));
        
        // Get the number of entries in the table
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        echo '<tr>';
        echo '<td><a href="' . esc_url($url) . '">' . $table_name . '</a></td>';
        echo '<td>' . esc_html($count) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // Pagination
    display_pagination($page, $total_pages, menu_page_url('database-tables-plugin', false));

    echo '</div>';
}

// Function to display the contents of a specific table with pagination
function display_table_contents($table_name, $page = 1) {
    global $wpdb;
    $rows_per_page = 10;
    $offset = ($page - 1) * $rows_per_page;

    // Get total row count
    $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_rows / $rows_per_page);

    echo '<div class="wrap">';
    echo '<h1>Table: ' . esc_html($table_name) . '</h1>';
    echo '<a href="' . esc_url(menu_page_url('database-tables-plugin', false)) . '">&laquo; Back to table list</a>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr>';

    // Get column names
    $columns = $wpdb->get_col("DESC $table_name", 0);
    foreach ($columns as $column) {
        echo '<th>' . esc_html($column) . '</th>';
    }

    echo '</tr></thead>';
    echo '<tbody>';

    // Get table data with pagination
    $results = $wpdb->get_results("SELECT * FROM $table_name LIMIT $offset, $rows_per_page", ARRAY_A);
    foreach ($results as $row) {
        echo '<tr>';
        foreach ($row as $data) {
            echo '<td>' . esc_html($data) . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // Pagination
    display_pagination($page, $total_pages, add_query_arg(array('table' => $table_name), menu_page_url('database-tables-plugin', false)));

    echo '</div>';
}

// Function to display pagination links
function display_pagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) return;

    echo '<div class="tablenav"><div class="tablenav-pages">';
    $base_url = remove_query_arg('paged', $base_url);
    
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        echo '<a class="page-numbers" href="' . esc_url(add_query_arg('paged', $prev_page, $base_url)) . '">&laquo;</a>';
    }

    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            echo '<span class="page-numbers current">' . $i . '</span>';
        } else {
            echo '<a class="page-numbers" href="' . esc_url(add_query_arg('paged', $i, $base_url)) . '">' . $i . '</a>';
        }
    }

    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        echo '<a class="page-numbers" href="' . esc_url(add_query_arg('paged', $next_page, $base_url)) . '">&raquo;</a>';
    }

    echo '</div></div>';
}

// Function to display the update plugin page
function update_database_tables_plugin_page() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['plugin_zip'])) {
        // Handle the file upload
        $uploaded_file = $_FILES['plugin_zip'];
        if ($uploaded_file['error'] == UPLOAD_ERR_OK) {
            $upload_dir = wp_upload_dir();
            $upload_file = $upload_dir['path'] . '/' . basename($uploaded_file['name']);
            
            if (move_uploaded_file($uploaded_file['tmp_name'], $upload_file)) {
                $zip = new ZipArchive;
                if ($zip->open($upload_file) === TRUE) {
                    $zip->extractTo(plugin_dir_path(__FILE__));
                    $zip->close();
                    echo '<div class="notice notice-success is-dismissible"><p>Plugin updated successfully.</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>Failed to extract the ZIP file.</p></div>';
                }
                unlink($upload_file); // Clean up the uploaded ZIP file
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to move the uploaded file.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Failed to upload the file.</p></div>';
        }
    }

    echo '<div class="wrap">';
    echo '<h1>Update Plugin</h1>';
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="plugin_zip" required>';
    echo '<input type="submit" value="Upload" class="button button-primary">';
    echo '</form>';
    echo '</div>';
}
