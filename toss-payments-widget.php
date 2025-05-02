<?php
/**
 * Plugin Name: Toss Payments for WooCommerce
 * Plugin URI: https://www.saasventur.com/projects/toss-payment-plugin
 * Description: The official Korean Toss Payments integration for WooCommerce. Accept credit cards, virtual accounts & mobile payments with a seamless checkout experience. Supports demo, test, and production modes.
 * Version: 1.1.0
 * Author: SaasVentur
 * Author URI: https://saasventur.com
 * Text Domain: toss-payments-for-woocommerce
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TOSS_WIDGET_VERSION', '1.1.0');
define('TOSS_WIDGET_PATH', plugin_dir_path(__FILE__));
define('TOSS_WIDGET_URL', plugin_dir_url(__FILE__));

// Load our gateway class after WooCommerce is loaded
add_action('plugins_loaded', 'toss_widget_init_gateway', 11);
function toss_widget_init_gateway()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }
    
    // Include our enhanced gateway class
    include_once TOSS_WIDGET_PATH . 'includes/class-wc-gateway-toss-payments-widget.php';
    
    // Register the gateway
    add_filter('woocommerce_payment_gateways', function ($methods) {
        $methods[] = 'WC_Gateway_Toss_Payments_Widget';
        return $methods;
    });
}

// Template redirect for custom pages
add_action('template_redirect', 'toss_widget_template_redirect');
function toss_widget_template_redirect()
{
    // The Checkout page
    if (is_page('toss-widget-checkout')) {
        include TOSS_WIDGET_PATH . 'templates/toss-widget-checkout-template.php';
        exit;
    }
    
    // The Success page
    if (is_page('toss-widget-success')) {
        include TOSS_WIDGET_PATH . 'templates/toss-widget-success-template.php';
        exit;
    }
    
    // The Fail page
    if (is_page('toss-widget-fail')) {
        include TOSS_WIDGET_PATH . 'templates/toss-widget-fail-template.php';
        exit;
    }
}

// Create required pages on activation
register_activation_hook(__FILE__, 'toss_widget_create_pages');
function toss_widget_create_pages()
{
    // Create checkout page if it doesn't exist
    $checkout_page = get_page_by_path('toss-widget-checkout');
    if (!$checkout_page) {
        wp_insert_post([
            'post_title'     => 'Toss Widget Checkout',
            'post_name'      => 'toss-widget-checkout',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
        ]);
    }
    
    // Create success page if it doesn't exist
    $success_page = get_page_by_path('toss-widget-success');
    if (!$success_page) {
        wp_insert_post([
            'post_title'     => 'Payment Successful',
            'post_name'      => 'toss-widget-success',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
        ]);
    }
    
    // Create fail page if it doesn't exist
    $fail_page = get_page_by_path('toss-widget-fail');
    if (!$fail_page) {
        wp_insert_post([
            'post_title'     => 'Payment Failed',
            'post_name'      => 'toss-widget-fail',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
        ]);
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Create directory structure on activation
register_activation_hook(__FILE__, 'toss_widget_create_structure');
function toss_widget_create_structure() {
    // Make sure our directories exist
    $directories = [
        TOSS_WIDGET_PATH . 'includes',
        TOSS_WIDGET_PATH . 'templates',
        TOSS_WIDGET_PATH . 'assets',
        TOSS_WIDGET_PATH . 'assets/images',
        TOSS_WIDGET_PATH . 'assets/css',
        TOSS_WIDGET_PATH . 'assets/js',
    ];
    
    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            wp_mkdir_p($directory);
        }
    }
    
    // Create or update default Toss logo
    $logo_path = TOSS_WIDGET_PATH . 'assets/images/toss-logo.png';
    if (!file_exists($logo_path)) {
        // You would normally copy a default image here
        // For now, we'll just create a placeholder file
        file_put_contents($logo_path, ''); // Empty file as placeholder
    }
}

// Add custom column to orders list for Toss payment info
add_filter('manage_edit-shop_order_columns', 'toss_widget_add_order_column');
function toss_widget_add_order_column($columns)
{
    $new_columns = [];
    
    foreach ($columns as $column_name => $column_info) {
        $new_columns[$column_name] = $column_info;
        
        if ('order_total' === $column_name) {
            $new_columns['toss_payment'] = __('Toss Payment', 'woocommerce');
        }
    }
    
    return $new_columns;
}

// Fill the custom column with data
add_action('manage_shop_order_posts_custom_column', 'toss_widget_order_column_content');
function toss_widget_order_column_content($column)
{
    global $post;
    $order = wc_get_order($post->ID);
    
    if ('toss_payment' === $column) {
        $payment_method = $order->get_payment_method();
        
        if ('toss_payments_widget' === $payment_method) {
            // Get transaction ID if available
            $transaction_id = $order->get_transaction_id();
            if ($transaction_id) {
                // For demo mode, show a label
                if (strpos($transaction_id, 'demo_') === 0) {
                    echo '<span style="background-color: #d4edda; color: #155724; padding: 3px 8px; border-radius: 3px;">Demo</span>';
                } 
                // For test mode, show a label
                else if (get_post_meta($post->ID, '_toss_payment_mode', true) === 'test') {
                    echo '<span style="background-color: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 3px;">Test</span> ';
                    echo esc_html($transaction_id);
                } 
                // For live mode, show the transaction ID
                else {
                    echo esc_html($transaction_id);
                }
            } else {
                echo '<span style="color: #999;">Pending</span>';
            }
        } else {
            echo '—';
        }
    }
}

// Store payment mode in order meta when payment completes
add_action('woocommerce_payment_complete', 'toss_widget_store_payment_mode');
function toss_widget_store_payment_mode($order_id) {
    $order = wc_get_order($order_id);
    
    if ($order && $order->get_payment_method() === 'toss_payments_widget') {
        // Get the payment gateway to check the mode
        $payment_gateways = WC_Payment_Gateways::instance();
        $gateway = $payment_gateways->payment_gateways()['toss_payments_widget'];
        
        if ($gateway) {
            update_post_meta($order_id, '_toss_payment_mode', $gateway->get_option('mode', 'demo'));
        }
    }
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'toss_widget_admin_scripts');
function toss_widget_admin_scripts($hook)
{
    // Only load on WooCommerce settings page or order edit pages
    $screen = get_current_screen();
    if (
        'post.php' !== $hook && 
        'woocommerce_page_wc-settings' !== $screen->id && 
        get_post_type() !== 'shop_order'
    ) {
        return;
    }
    
    wp_enqueue_style(
        'toss-widget-admin-style',
        TOSS_WIDGET_URL . 'assets/css/admin.css',
        [],
        TOSS_WIDGET_VERSION
    );
}

/**
 * Hide/close Elementor cart on success and fail pages
 * Since we're using get_header() in our templates, this ensures
 * the side cart is hidden. If it's already not visible, feel free to remove.
 */
add_action('wp_enqueue_scripts', 'hide_elementor_cart_completely', 99);
function hide_elementor_cart_completely() {
    if (is_page('toss-widget-success') || is_page('toss-widget-fail')) {
        // Hide the overlay and cart container
        wp_add_inline_style(
            'woocommerce-inline',
            '.elementor-menu-cart__container.elementor-lightbox[aria-hidden="true"],
             .elementor-menu-cart__overlay {
                display: none !important;
                pointer-events: none !important;
                z-index: -9999 !important;
             }'
        );
    }
}

add_action('wp_footer', 'force_close_elementor_cart');
function force_close_elementor_cart() {
    if (is_page('toss-widget-success') || is_page('toss-widget-fail')) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.remove('elementor-menu-cart--open');
        });
        </script>
        <?php
    }
}

// Add a notice if required keys are missing for the selected mode
add_action('admin_notices', 'toss_widget_admin_notices');
function toss_widget_admin_notices() {
    $screen = get_current_screen();
    
    // Only show on WooCommerce settings pages
    if (!$screen || 'woocommerce_page_wc-settings' !== $screen->id) {
        return;
    }
    
    // Only check if we're on the payment methods tab
    if (
        !isset($_GET['tab']) || 
        'checkout' !== $_GET['tab'] || 
        (isset($_GET['section']) && 'toss_payments_widget' !== $_GET['section'])
    ) {
        return;
    }
    
    // Get our gateway settings
    $gateway_settings = get_option('woocommerce_toss_payments_widget_settings');
    
    if (!$gateway_settings) {
        return;
    }
    
    $mode = $gateway_settings['mode'] ?? 'demo';
    $missing_keys = false;
    $message = '';
    
    if ('demo' === $mode) {
        if (empty($gateway_settings['demo_client_key']) || empty($gateway_settings['demo_secret_key'])) {
            $missing_keys = true;
            $message = __('Toss Payments is in Demo Mode but demo keys are missing. Please provide both Demo Client Key and Demo Secret Key.', 'woocommerce');
        }
    } else if ('test' === $mode) {
        if (empty($gateway_settings['test_client_key']) || empty($gateway_settings['test_secret_key'])) {
            $missing_keys = true;
            $message = __('Toss Payments is in Test Mode but test keys are missing. Please provide both Test Client Key and Test Secret Key.', 'woocommerce');
        }
    } else if ('live' === $mode) {
        if (empty($gateway_settings['live_client_key']) || empty($gateway_settings['live_secret_key'])) {
            $missing_keys = true;
            $message = __('Toss Payments is in Live Mode but production keys are missing. Please provide both Live Client Key and Live Secret Key.', 'woocommerce');
        }
    }
    
    if ($missing_keys) {
        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
    }
}

// ADDING webhook for Payment callbacks

// Register webhook endpoint
add_action('init', 'register_toss_webhook_endpoint');
function register_toss_webhook_endpoint() {
    add_rewrite_rule(
        'toss-webhook/?$',
        'index.php?toss_webhook=1',
        'top'
    );
}

// Add query var for webhook
add_filter('query_vars', 'add_toss_webhook_query_var');
function add_toss_webhook_query_var($vars) {
    $vars[] = 'toss_webhook';
    return $vars;
}

// Handle webhook requests
add_action('template_redirect', 'handle_toss_webhook');
function handle_toss_webhook() {
    if (get_query_var('toss_webhook')) {
        // Get the webhook data
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        // Get gateway instance for logging
        $payment_gateways = WC_Payment_Gateways::instance();
        $gateway = $payment_gateways->payment_gateways()['toss_payments_widget'];
        
        // Verify the webhook signature if provided
        $signature = isset($_SERVER['HTTP_TOSS_SIGNATURE']) ? $_SERVER['HTTP_TOSS_SIGNATURE'] : '';
        
        if ($gateway->debug) {
            $gateway->log->add('toss_payments', 'Webhook received: ' . print_r($data, true));
        }
        
        // Handle different event types
        if (isset($data['eventType'])) {
            switch ($data['eventType']) {
                case 'PAYMENT_STATUS_CHANGED':
                    handle_payment_status_changed($data, $gateway);
                    break;
                    
                case 'DEPOSIT_CALLBACK':
                    handle_deposit_callback($data, $gateway);
                    break;
                    
                case 'CANCEL_STATUS_CHANGED':
                    handle_cancel_status_changed($data, $gateway);
                    break;
            }
        }
        
        // Send 200 response to acknowledge receipt
        status_header(200);
        exit;
    }
}

/**
 * Handle payment status change events
 */
function handle_payment_status_changed($data, $gateway) {
    if (!isset($data['payment']['orderId'])) {
        return;
    }
    
    // Extract order ID from Toss orderId (format: order_123_timestamp)
    $order_id_parts = explode('_', $data['payment']['orderId']);
    if (count($order_id_parts) < 2) {
        return;
    }
    $order_id = $order_id_parts[1];
    
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    
    $payment_status = $data['payment']['status'];
    
    switch ($payment_status) {
        case 'DONE':
            // Payment completed
            if ($order->get_status() === 'pending') {
                $order->payment_complete($data['payment']['paymentKey']);
                $order->add_order_note(
                    sprintf(__('Toss Payment completed via webhook. Payment Key: %s', 'woocommerce'), 
                    $data['payment']['paymentKey'])
                );
                
                if ($gateway->debug) {
                    $gateway->log->add('toss_payments', 
                        sprintf('Payment completed for order #%s via webhook', $order_id)
                    );
                }
            }
            break;
            
        case 'CANCELED':
            // Payment cancelled
            if ($order->get_status() === 'pending') {
                $order->update_status('cancelled', __('Payment cancelled via Toss webhook', 'woocommerce'));
                
                if ($gateway->debug) {
                    $gateway->log->add('toss_payments', 
                        sprintf('Payment cancelled for order #%s via webhook', $order_id)
                    );
                }
            }
            break;
            
        case 'FAILED':
            // Payment failed
            if ($order->get_status() === 'pending') {
                $order->update_status('failed', __('Payment failed via Toss webhook', 'woocommerce'));
                
                if ($gateway->debug) {
                    $gateway->log->add('toss_payments', 
                        sprintf('Payment failed for order #%s via webhook', $order_id)
                    );
                }
            }
            break;
    }
}

/**
 * Handle virtual account deposit events
 */
function handle_deposit_callback($data, $gateway) {
    if (!isset($data['payment']['orderId'])) {
        return;
    }
    
    // Extract order ID
    $order_id_parts = explode('_', $data['payment']['orderId']);
    if (count($order_id_parts) < 2) {
        return;
    }
    $order_id = $order_id_parts[1];
    
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    
    if (isset($data['deposit']['status']) && $data['deposit']['status'] === 'DONE') {
        // Virtual account deposit completed
        if ($order->get_status() === 'pending') {
            $order->payment_complete($data['payment']['paymentKey']);
            $order->add_order_note(
                sprintf(__('Virtual account deposit completed via webhook. Payment Key: %s', 'woocommerce'),
                $data['payment']['paymentKey'])
            );
            
            if ($gateway->debug) {
                $gateway->log->add('toss_payments',
                    sprintf('Virtual account deposit completed for order #%s via webhook', $order_id)
                );
            }
        }
    }
}

/**
 * Handle payment cancellation events
 */
function handle_cancel_status_changed($data, $gateway) {
    if (!isset($data['payment']['orderId'])) {
        return;
    }
    
    // Extract order ID
    $order_id_parts = explode('_', $data['payment']['orderId']);
    if (count($order_id_parts) < 2) {
        return;
    }
    $order_id = $order_id_parts[1];
    
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    
    if (isset($data['cancel']['status']) && $data['cancel']['status'] === 'DONE') {
        // Payment cancelled
        $order->update_status('cancelled', __('Payment cancelled via Toss webhook', 'woocommerce'));
        
        if ($gateway->debug) {
            $gateway->log->add('toss_payments',
                sprintf('Payment cancelled for order #%s via webhook', $order_id)
            );
        }
    }
}

// Flush rewrite rules on plugin activation
register_activation_hook(__FILE__, 'flush_toss_webhook_rules');
function flush_toss_webhook_rules() {
    register_toss_webhook_endpoint();
    flush_rewrite_rules();
}
