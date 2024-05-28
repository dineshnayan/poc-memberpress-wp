<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'class-db.php';

if(!class_exists('DashboardFunction')){
    class DashboardFunction {

        private $db;

        public function __construct() {
            $this->db = new PluginDatabase();
        }

        // Display admin notice
        public function admin_notice() {
            if ( $message = get_option( 'basic_functionality_message' ) ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
                delete_option( 'basic_functionality_message' );
            }
        }

        // Add settings page to the admin menu
        public function add_menu() {
            add_menu_page(
                'Ongraph Mpress Settings',
                'Ongraph Mpress',
                'manage_options',
                'ongraph_mpress',
                [ $this, 'settings_page' ]
            );
        }

        // Display the settings page
        public function settings_page() {
            ?>
            <div class="wrap">
                <h1>Ongraph Mpress</h1>
                <h2 class="nav-tab-wrapper">
                    <a href="#tab-basic" class="nav-tab nav-tab-active" id="tab-basic-tab">Basic Settings</a>
                    <a href="#tab-advanced" class="nav-tab" id="tab-advanced-tab">Advanced Settings</a>
                </h2>
                <div id="tab-basic" class="tab-content">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'functionality_options' );
                        do_settings_sections( 'basic-functionality' );
                        submit_button();
                        ?>
                    </form>
                </div>
                <div id="tab-advanced" class="tab-content" style="display: none;">
                    <h2>Advanced Settings</h2>
                    <?php submit_button(); ?>
                </div>
            </div>
            <script>
                jQuery(document).ready(function($) {
                    jQuery('.nav-tab').click(function(event) {
                        event.preventDefault();
                        jQuery('.nav-tab').removeClass('nav-tab-active');
                        jQuery('.tab-content').hide();
                        jQuery($(this).attr('href')).show();
                        jQuery(this).addClass('nav-tab-active');
                    });
                });
            </script>
            <?php
        }

        // Initialize settings
        public function settings_init() {
            register_setting( 'functionality_options', 'functionality_options' );

            add_settings_section(
                'basic_functionality_section',
                'Basic Settings',
                [ $this, 'settings_section_callback' ],
                'basic-functionality'
            );

            add_settings_field(
                'functionality_field',
                'Select Subscription For',
                [ $this, 'settings_field_callback' ],
                'basic-functionality',
                'basic_functionality_section'
            );
        }

        // Section callback
        public function settings_section_callback() {
            echo 'This is a basic settings section.';
        }

        // Field callback
        public function settings_field_callback() {
            $options = get_option( 'functionality_options' );
            ?>
            <div class="subs_pages">
                <select id="subs_pages" name="functionality_options[functionality_field]">
                    <option value="" <?php selected( $options['functionality_field'], 'Select' ); ?>>Select Page</option>
                    <option value="pages" <?php selected( $options['functionality_field'], 'Pages' ); ?>>Pages</option>
                    <!-- Add more options as needed -->
                </select>
            </div>
            <div class="subs_posts">
                <select id="subs_posts" name="functionality_options[functionality_field]">
                    <option value="" <?php selected( $options['functionality_field'], 'Select' ); ?>>Select Post</option>
                    <option value="posts" <?php selected( $options['functionality_field'], 'Posts' ); ?>>Posts</option>
                    <!-- Add more options as needed -->
                </select>
            </div>
            <script>
                // var hostname  = window.location.protocol+'//'+window.location.hostname+'/';
                jQuery(document).ready(function(){
                    jQuery("#subs_pages").on("change", function(){
                        let subs_option = jQuery("#subs_pages").val();
                        if(subs_option != ""){
                        jQuery.ajax({
                            url: 'http://localhost:8002/wp-admin/admin-ajax.php',
                            type: "POST",
                            data: {
                                action: "getAllPages",
                                subs_option: subs_option
                            },
                            success: function(response) {
                                jQuery("#all_pages").html(response);
                            }
                        });
                    }
                    });

                    jQuery("#subs_posts").on("change", function(){
                        let subs_option = jQuery("#subs_posts").val();
                        if(subs_option != ""){
                        jQuery.ajax({
                            url: 'http://localhost:8002/wp-admin/admin-ajax.php',
                            type: "POST",
                            data: {
                                action: "getAllPages",
                                subs_option: subs_option
                            },
                            success: function(response) {
                                jQuery("#all_pages").html(response);
                            }
                        });
                    }
                    });
                });
            </script>
            <?php
        }

        public function getAllPages(){
            $selected_val = $_POST['subs_option'];

            if($selected_val === 'pages'){
                $pages = get_pages(); 
                foreach ( $pages as $page ) {
                    $option = '<option value="' . get_page_link( $page->ID ) . '">';
                    $option .= $page->post_title;
                    $option .= '</option>';
                    echo $option;
                }
            }elseif($selected_val === 'posts'){
                echo "POSTS";
                die;
                $post = get_posts(/* array(
                    'key' => 'premium_post',
                    'value' => 'Yes',
                    'compare' => '='
                    ) */
                ); 
                foreach ( $pages as $page ) {
                    $option = '<option value="' . get_parmalink( $post->ID ) . '">';
                    $option .= $post->the_title;
                    $option .= '</option>';
                    echo $option;
                }
            }
            exit;
        }
    }
}

// Initialize the class and hook into WordPress
$dashboardFunction = new DashboardFunction();
add_action( 'admin_notices', [ $dashboardFunction, 'admin_notice' ] );
add_action( 'admin_menu', [ $dashboardFunction, 'add_menu' ] );
add_action( 'admin_init', [ $dashboardFunction, 'settings_init' ] );
add_action('wp_ajax_getAllPages', [$dashboardFunction, 'getAllPages']);
add_action('wp_ajax_nopriv_getAllPages', [$dashboardFunction, 'getAllPages']);
?>