<?php
/**
 * MySer AJAX Reference Class
 * 
 * Handles reference data (brands, devices, components) AJAX requests
 */

namespace MySer\Includes\Ajax;

if (!defined('ABSPATH')) {
    exit;
}

class AjaxReference {
    
    private static $allowed_types = ['brands', 'devices', 'components'];
    
    public static function init() {
        add_action('wp_ajax_myser_get_reference_item', [self::class, 'get_item']);
        add_action('wp_ajax_myser_save_reference_item', [self::class, 'save_item']);
        add_action('wp_ajax_myser_delete_reference_item', [self::class, 'delete_item']);
    }
    
    /**
     * Get a single reference item
     */
    public static function get_item() {
        check_ajax_referer('myser_nonce', 'nonce');
        
        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(['message' => __('Permission denied', 'myser')]);
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if (!in_array($type, self::$allowed_types, true)) {
            wp_send_json_error(['message' => __('Invalid reference type', 'myser')]);
        }
        
        if (!$id) {
            wp_send_json_error(['message' => __('Invalid ID', 'myser')]);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'myser_' . $type;
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            wp_send_json_error(['message' => __('Table not found', 'myser')]);
        }
        
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `$table` WHERE id = %d",
            $id
        ), ARRAY_A);
        
        if (!$item) {
            wp_send_json_error(['message' => __('Item not found', 'myser')]);
        }
        
        wp_send_json_success($item);
    }
    
    /**
     * Save a reference item (create or update)
     */
    public static function save_item() {
        check_ajax_referer('myser_nonce', 'nonce');
        
        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(['message' => __('Permission denied', 'myser')]);
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        
        if (!in_array($type, self::$allowed_types, true)) {
            wp_send_json_error(['message' => __('Invalid reference type', 'myser')]);
        }
        
        if (empty($name)) {
            wp_send_json_error(['message' => __('Name is required', 'myser')]);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'myser_' . $type;
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            wp_send_json_error(['message' => __('Table not found', 'myser')]);
        }
        
        // Check for duplicate name
        $duplicate = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM `$table` WHERE name = %s AND id != %d",
            $name,
            $id
        ));
        
        if ($duplicate > 0) {
            wp_send_json_error(['message' => __('Name already exists', 'myser')]);
        }
        
        $data = [
            'name' => $name,
            'description' => $description
        ];
        
        if ($id > 0) {
            // Update existing
            $updated = $wpdb->update($table, $data, ['id' => $id]);
            if ($updated === false) {
                wp_send_json_error(['message' => __('Failed to update item', 'myser')]);
            }
        } else {
            // Insert new
            $inserted = $wpdb->insert($table, $data);
            if (!$inserted) {
                wp_send_json_error(['message' => __('Failed to create item', 'myser')]);
            }
            $id = $wpdb->insert_id;
        }
        
        wp_send_json_success([
            'message' => __('Saved successfully', 'myser'),
            'id' => $id
        ]);
    }
    
    /**
     * Delete a reference item
     */
    public static function delete_item() {
        check_ajax_referer('myser_nonce', 'nonce');
        
        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(['message' => __('Permission denied', 'myser')]);
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if (!in_array($type, self::$allowed_types, true)) {
            wp_send_json_error(['message' => __('Invalid reference type', 'myser')]);
        }
        
        if (!$id) {
            wp_send_json_error(['message' => __('Invalid ID', 'myser')]);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'myser_' . $type;
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            wp_send_json_error(['message' => __('Table not found', 'myser')]);
        }
        
        $deleted = $wpdb->delete($table, ['id' => $id]);
        
        if ($deleted === false) {
            wp_send_json_error(['message' => __('Failed to delete item', 'myser')]);
        }
        
        wp_send_json_success(['message' => __('Deleted successfully', 'myser')]);
    }
}
