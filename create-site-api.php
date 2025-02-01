<?php

/**
 * Plugin Name: Custom API for Site Creation
 * Description: Provides a custom API to create multi sites from 1 installation with seprate DB's & Upload Folders.
 * Version: 1.0
 * Author: Muzamal Attiq || Senior Software Engineer
 */
function create_site_api_endpoint() {
    register_rest_route('custom/v1', '/create-site', array(
        'methods' => 'POST',
        'callback' => 'create_site_callback',
        'permission_callback' => '__return_true', // Add proper permissions check
    ));
}
add_action('rest_api_init', 'create_site_api_endpoint');

// Callback function for the create site API
function create_site_callback(WP_REST_Request $request) {
    // Ensure WordPress environment is loaded
    if (!defined('ABSPATH')) {
        require_once(ABSPATH . 'wp-load.php');
    }

    // Database credentials
    global $wpdb;
    $main_db = 'saaswp';

    // Get API data
    $data = json_decode(file_get_contents('php://input'), true);

    // Sanitize and validate inputs
    $subdomain = sanitize_text_field(strtolower(trim($data['subdomain'])));
    $email = sanitize_email($data['email']);
    $admin_user = sanitize_user($data['admin_user']);
    $admin_pass = sanitize_text_field($data['admin_pass']);

    if (!$subdomain || !$email || !$admin_user || !$admin_pass || !is_email($email)) {
        wp_send_json_error(['message' => 'Missing or invalid required fields']);
    }

    $new_db = $main_db . $subdomain;
    $new_url = "http://$subdomain.wpsaas.com";

    // Check if the database already exists
    $existing_db = $wpdb->get_var($wpdb->prepare("SHOW DATABASES LIKE %s", $new_db));
    if ($existing_db) {
        wp_send_json_error(['message' => 'Database already exists']);
    }

    // Create the new database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD);
    if ($conn->connect_error) {
        wp_send_json_error(['message' => 'Connection failed: ' . $conn->connect_error]);
    }

    $conn->query("CREATE DATABASE `$new_db`");

    // Define the SQL file path
    $sql_file = ABSPATH . 'saaswp.sql'; // Adjust the path if necessary

    if (!file_exists($sql_file)) {
        wp_send_json_error(['message' => 'SQL file not found']);
    }

    $sql_query = file_get_contents($sql_file);
    if (empty($sql_query)) {
        wp_send_json_error(['message' => 'SQL file is empty']);
    }

    // Copy default WordPress tables into the new database
    $conn->select_db($new_db);
    if ($conn->multi_query($sql_query)) {
        while ($conn->more_results()) {
            $conn->next_result();
        }
    } else {
        wp_send_json_error(['message' => 'Error importing SQL file: ' . $conn->error]);
    }

    // Update site URL in the new database
    $conn->query("UPDATE wp_options SET option_value='$new_url' WHERE option_name='siteurl' OR option_name='home'");

    // Check if the admin user already exists
    $user = get_user_by('login', $admin_user);
    if ($user) {
        // Update existing user details if necessary
        $user_id = $user->ID;
        wp_update_user([
            'ID' => $user_id,
            'user_email' => $email,
            'user_pass' => wp_hash_password($admin_pass),
        ]);
        update_user_meta($user_id, 'new_site_db', $new_db);
    } else {
        // Create the admin user if it doesn't exist
        $hashed_pass = wp_hash_password($admin_pass);
        $user_id = wp_insert_user([
            'user_login' => $admin_user,
            'user_pass' => $hashed_pass,
            'user_email' => $email,
            'role' => 'administrator',
            'user_registered' => current_time('mysql')
        ]);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => 'Error creating admin user', 'error' => $user_id->get_error_message()]);
        }
    }

    // Create the upload folder for the new site
    $uploads_path = WP_CONTENT_DIR . '/uploads/' . $subdomain;
    if (!file_exists($uploads_path)) {
        if (!wp_mkdir_p($uploads_path)) {
            wp_send_json_error(['message' => 'Failed to create uploads folder']);
        }
    }

    // Set the custom upload path for each site
    $upload_dir = WP_CONTENT_DIR . '/uploads/' . $subdomain;
    $conn->query("UPDATE wp_options SET option_value='$upload_dir' WHERE option_name='upload_path'");

    // Update upload_url_path to reflect new subdomain URL
    $upload_url_path = "http://$subdomain.wpsaas.com/wp-content/uploads/$subdomain";
    $conn->query("UPDATE wp_options SET option_value='$upload_url_path' WHERE option_name='upload_url_path'");

    // Final response Site Created Msg.
    wp_send_json_success([
        'message' => 'Site created successfully',
        'url' => $new_url,
        'uploads_path' => $uploads_path,
        'database' => $new_db,
    ]);

    // Close the database connection
    $conn->close();
}
?>