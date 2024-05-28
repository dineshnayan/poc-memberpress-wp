<?php
if (!class_exists('PremiumContent')) {
    class PremiumContent {
        private $stripe_secret_key;
        private $stripe_publishable_key;
        private $lms_post_types;

        public function __construct() {
            // Set your Stripe API keys
            $this->stripe_secret_key = 'sk_test_51PGEyXSHUOngqgQlMFUAm3Cz3PMYtSnsqcEhc9Fv1sM6MKtC5SnZfngDrOpo4ckjGvWTD0MKfqwM8omDbRKte3mc00C65rylwp';
            $this->stripe_publishable_key = 'pk_test_51PGEyXSHUOngqgQltGt39PNfw5KU4Zereh9EwQ5JMDDMBetfEIsMjVJzTnBQFmALtDeKXqqeAlZARC7UZmnEJDXY00VJXZzrMp';

            // Define LMS post types
            $this->lms_post_types = ['course', 'lesson', 'quiz'];

            // Hooks
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('add_meta_boxes', array($this, 'add_premium_meta_box'));
            add_action('save_post', array($this, 'save_premium_meta_box_data'));
            add_action('the_content', array($this, 'restrict_premium_content'));
            add_action('wp_ajax_handle_payment', array($this, 'handle_payment'));
            add_action('wp_ajax_nopriv_handle_payment', array($this, 'handle_payment'));
            add_action('template_redirect', array($this, 'handle_payment_success'));

            // Add title filter
            add_filter('the_title', array($this, 'add_premium_flag_to_title'), 10, 2);
        }

        public function enqueue_scripts() {
            wp_enqueue_script('jquery');
            wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/');
            wp_enqueue_script('premium-content', get_stylesheet_directory_uri() . '/js/custom.js', array('jquery'), null, true);
            wp_localize_script('premium-content', 'premiumContentAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'is_user_logged_in' => is_user_logged_in(),
                'login_url' => wp_login_url()
            ));
        }

        public function add_premium_meta_box() {
            $post_types = array_merge(['post'], $this->lms_post_types);
            foreach ($post_types as $post_type) {
                add_meta_box(
                    'premium_meta_box',
                    __('Premium Content', 'textdomain'),
                    array($this, 'render_premium_meta_box'),
                    $post_type,
                    'side',
                    'high'
                );
            }
        }

        public function render_premium_meta_box($post) {
            // Add a nonce field for security
            wp_nonce_field('premium_meta_box_nonce', 'premium_meta_box_nonce');

            // Get the current value
            $value = get_post_meta($post->ID, '_is_premium', true);

            // Display the form field
            echo '<label for="is_premium">';
            echo '<input type="checkbox" id="is_premium" name="is_premium" value="1" ' . checked(1, $value, false) . ' />';
            _e('Make this post premium', 'textdomain');
            echo '</label>';
        }

        public function save_premium_meta_box_data($post_id) {
            // Check if our nonce is set.
            if (!isset($_POST['premium_meta_box_nonce'])) {
                return;
            }

            // Verify that the nonce is valid.
            if (!wp_verify_nonce($_POST['premium_meta_box_nonce'], 'premium_meta_box_nonce')) {
                return;
            }

            // If this is an autosave, our form has not been submitted, so we don't want to do anything.
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            // Check the user's permissions.
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            // Sanitize the user input.
            $is_premium = isset($_POST['is_premium']) ? 1 : 0;

            // Update the meta field in the database.
            update_post_meta($post_id, '_is_premium', $is_premium);
        }

        public function restrict_premium_content($content) {
            if (is_singular(array_merge(['post'], $this->lms_post_types))) {
                $post_id = get_the_ID();
                $is_premium = get_post_meta($post_id, '_is_premium', true);

                if ($is_premium && !is_user_logged_in()) {
                    ob_start();
                    ?>
                    <div id="premium-content-message">
                        <p>This content is for premium users only. Please log in or make a payment to access this content.</p>
                        <button id="stripe-pay-button">Pay Now</button>
                    </div>
                    <script>
                        var stripe = Stripe('<?php echo $this->stripe_publishable_key; ?>');
                    </script>
                    <?php
                    return ob_get_clean();
                } else if ($is_premium && !get_user_meta(get_current_user_id(), '_has_paid', true)) {
                    ob_start();
                    ?>
                    <div id="premium-content-message">
                        <p>This content is for premium users only. Please make a payment to access this content.</p>
                        <button id="stripe-pay-button">Pay Now</button>
                    </div>
                    <script>
                        var stripe = Stripe('<?php echo $this->stripe_publishable_key; ?>');
                    </script>
                    <?php
                    return ob_get_clean();
                }
            }
            return $content;
        }

        public function handle_payment() {
            if (!is_user_logged_in()) {
                wp_send_json_error(['message' => 'You must be logged in to make a payment.']);
                return;
            }

            require_once '/var/www/html/wp-content/uploads/stripe-php-master/init.php';
            \Stripe\Stripe::setApiKey($this->stripe_secret_key);

            $referrer = isset($_POST['referrer']) ? sanitize_text_field($_POST['referrer']) : home_url();

            try {
                $session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'inr',
                            'product_data' => [
                                'name' => 'Premium Content Access',
                            ],
                            'unit_amount' => 100278, // amount in cents
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'success_url' => home_url('/payment-success?session_id={CHECKOUT_SESSION_ID}&referrer=' . urlencode($referrer)),
                    'cancel_url' => home_url('/payment-cancel'),
                ]);

                wp_send_json_success(['id' => $session->id]);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                wp_send_json_error(['message' => $e->getMessage()]);
            } catch (Exception $e) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
        }

        public function handle_payment_success() {
            if (isset($_GET['session_id'])) {
                require_once '/var/www/html/wp-content/uploads/stripe-php-master/init.php';
                \Stripe\Stripe::setApiKey($this->stripe_secret_key);

                $session_id = sanitize_text_field($_GET['session_id']);
                $session = \Stripe\Checkout\Session::retrieve($session_id);

                if ($session && $session->payment_status == 'paid') {
                    $user_id = get_current_user_id();
                    update_user_meta($user_id, '_has_paid', true);
                    $referrer = isset($_GET['referrer']) ? esc_url_raw($_GET['referrer']) : home_url('/premium-content');
                    wp_redirect($referrer);
                    exit;
                }
            }
        }

        public function add_premium_flag_to_title($title, $post_id) {
            if (is_admin()) {
                return $title;
            }

            $is_premium = get_post_meta($post_id, '_is_premium', true);
            
            if ($is_premium) {
                $title .= ' <span class="premium-flag">[Premium]</span>';
            }

            return $title;
        }
    }

    new PremiumContent();
}