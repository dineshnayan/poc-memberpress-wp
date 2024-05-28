<?php
    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    class ActiveMpressPlugin {

        private $table_name;
        private $table_name_user;

        public function __construct() {
            global $wpdb;
            $this->table_name = $wpdb->prefix . 'ongraph_mpress';
            $this->table_name_user = $wpdb->prefix . 'mpress_active_services';
    
            // Hook into the admin menu
            add_action('add_meta_boxes', [ $this, 'add_custom_meta_box' ]);
            add_action('save_post', [ $this, 'save_custom_meta_box' ]);
        }
    
        public function add_custom_meta_box() {
            add_meta_box(
                'custom_meta_box',
                'Premium',
                [ $this, 'display_custom_meta_box' ],
                ['post', 'page'],
                'side',
                'default'
            );
        }
    
        public function display_custom_meta_box($post) {
            // Add a nonce field so we can check for it later.
            wp_nonce_field('custom_meta_box_nonce', 'meta_box_nonce');
    
            $value = get_post_meta($post->ID, '_custom_meta_value_key', true);
    
            echo '<p class="custom_field">Please write "Yes/No" to make it "Premium"!</p>';
            echo '<input type="text" id="custom_field" name="custom_field" value="' . esc_attr($value) . '" />';
        }
    
        public function save_custom_meta_box($post_id) {
            // Check if our nonce is set.
            if (!isset($_POST['meta_box_nonce'])) {
                return $post_id;
            }
    
            $nonce = $_POST['meta_box_nonce'];
    
            // Verify that the nonce is valid.
            if (!wp_verify_nonce($nonce, 'custom_meta_box_nonce')) {
                return $post_id;
            }
    
            // If this is an autosave, our form has not been submitted, so we don't want to do anything.
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return $post_id;
            }
    
            // Check the user's permissions.
            if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
                if (!current_user_can('edit_page', $post_id)) {
                    return $post_id;
                }
            } else {
                if (!current_user_can('edit_post', $post_id)) {
                    return $post_id;
                }
            }
    
            // Sanitize user input.
            $new_value = sanitize_text_field($_POST['custom_field']);
    
            // Update the meta field in the database.
            update_post_meta($post_id, '_custom_meta_value_key', $new_value);
    
            // Insert the data into the custom table
            global $wpdb;
            $table_name = $this->table_name;
            $user_id = get_current_user_id();
            $wpdb->insert($table_name, [
                'UserID' => $user_id,
                'FirstName' => '',
                'LastName' => '',
                'EmailID' => '',
                'StripeDetail' => '',
                'PaymentReceived' => '',
                'PaymentReceivedDate' => '',
                'Subscription_Expire' => '',
                'CustomField' => $new_value
            ]);
        }
    
        // Activation function
        public function activate() {
            global $wpdb
            $charset_collate = $wpdb->get_charset_collate();
    
            $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
                    ID int(11) NOT NULL AUTO_INCREMENT,
                    UserID int(255) NOT NULL,
                    FirstName varchar(255) NOT NULL,
                    LastName varchar(255) NOT NULL,
                    EmailID varchar(255) NOT NULL,
                    StripeDetail varchar(255) NOT NULL,
                    PaymentReceived varchar(255) NOT NULL,
                    PaymentReceivedDate varchar(255) NOT NULL,
                    Subscription_Expire varchar(255) NOT NULL,
                    CustomField varchar(255) NOT NULL,
                    PRIMARY KEY (ID)
            ) $charset_collate;";
    
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
    
            $sql_user = "CREATE TABLE IF NOT EXISTS $this->table_name_user (
                    ID int(11) NOT NULL AUTO_INCREMENT,
                    PremiumType varchar(255) NOT NULL,
                    PremiumTypeValue varchar(255),
                    PRIMARY KEY (ID)
            ) $charset_collate;";
    
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql_user );
    
            update_option( 'basic_functionality_message', 'Ongraph Mpress Plugin Activated!' );
        }
    }
    

    // Initialize the class and hook into WordPress activation hook
    $activeMpressPlugin = new ActiveMpressPlugin();
    register_activation_hook( __FILE__, [ $activeMpressPlugin, 'activate' ] );
?>
