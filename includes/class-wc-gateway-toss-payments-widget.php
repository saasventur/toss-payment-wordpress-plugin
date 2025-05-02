<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Toss_Payments_Widget extends WC_Payment_Gateway
{
    /**
     * @var WC_Logger Logger instance.
     */
    private $log;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id                 = 'toss_payments_widget';
        // $this->icon               = apply_filters('woocommerce_toss_icon', TOSS_WIDGET_URL . 'assets/images/toss-logo.png');
        $this->icon               = '';
        $this->method_title       = __('Toss Payments (Widget)', 'woocommerce');
        $this->method_description = __('Integrates with Toss Payments using their Payment Widget. Supports demo, test, and production modes.', 'woocommerce');
        $this->has_fields         = false;
        $this->supports           = ['products', 'refunds'];
        
        // Load settings
        $this->init_form_fields();
        $this->init_settings();
        
        // Define user set variables
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->mode         = $this->get_option('mode', 'demo'); // Default to 'demo'
        $this->test_client_key  = $this->get_option('test_client_key');
        $this->test_secret_key  = $this->get_option('test_secret_key');
        $this->live_client_key  = $this->get_option('live_client_key');
        $this->live_secret_key  = $this->get_option('live_secret_key');
        $this->debug        = ('yes' === $this->get_option('debug'));
        
        // Set the active keys based on mode
        $this->set_active_keys();
        
        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_api_wc_gateway_toss_payments', [$this, 'handle_callback']);
        
        // Add debug logs if enabled
        if ($this->debug) {
            $this->log = new WC_Logger();
        }
    }
    
    /**
     * Set the active client and secret keys based on the current mode
     */
    private function set_active_keys()
    {
        switch ($this->mode) {
            case 'demo':
                // Doc test keys for demo mode
                $this->client_key = 'test_gck_docs_Ovk5rk1EwkEbP0W43n07xlzm';
                $this->secret_key = 'test_gsk_docs_OaPz8L5KdmQXkzRz3y47BMw6';
                break;
                
            case 'test':
                // Store's test keys
                $this->client_key = $this->test_client_key;
                $this->secret_key = $this->test_secret_key;
                break;
                
            case 'live':
                // Store's live/production keys
                $this->client_key = $this->live_client_key;
                $this->secret_key = $this->live_secret_key;
                break;
                
            default:
                // Fallback to demo mode if invalid mode is set
                $this->client_key = 'test_gck_docs_Ovk5rk1EwkEbP0W43n07xlzm';
                $this->secret_key = 'test_gsk_docs_OaPz8L5KdmQXkzRz3y47BMw6';
        }
    }
    
    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable Toss Payments', 'woocommerce'),
                'default' => 'yes',
            ],
            'title' => [
                'title'       => __('Title', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Title shown at checkout.', 'woocommerce'),
                'default'     => __('Toss Payments', 'woocommerce'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __('Description', 'woocommerce'),
                'type'        => 'textarea',
                'description' => __('Payment method description shown at checkout.', 'woocommerce'),
                'default'     => __('Pay securely using Toss Payments.', 'woocommerce'),
                'desc_tip'    => true,
            ],
            'mode' => [
                'title'       => __('Mode', 'woocommerce'),
                'type'        => 'select',
                'description' => __('Select the payment mode to use.', 'woocommerce'),
                'default'     => 'demo',
                'options'     => [
                    'demo' => __('Demo Mode (Using Toss Docs Test Keys)', 'woocommerce'),
                    'test' => __('Test Mode (Using Your Test Keys)', 'woocommerce'),
                    'live' => __('Live Mode (Using Your Production Keys)', 'woocommerce'),
                ],
                'desc_tip'    => true,
            ],
            'test_client_key' => [
                'title'       => __('Test Client Key', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Your Toss Payments test client key. Required for Test Mode.', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'test_secret_key' => [
                'title'       => __('Test Secret Key', 'woocommerce'),
                'type'        => 'password',
                'description' => __('Your Toss Payments test secret key. Required for Test Mode.', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'live_client_key' => [
                'title'       => __('Live Client Key', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Your Toss Payments live client key. Required for Live Mode.', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'live_secret_key' => [
                'title'       => __('Live Secret Key', 'woocommerce'),
                'type'        => 'password',
                'description' => __('Your Toss Payments live secret key. Required for Live Mode.', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'debug' => [
                'title'       => __('Debug log', 'woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Enable logging', 'woocommerce'),
                'default'     => 'no',
                'description' => __('Log Toss Payments events, such as webhook requests.', 'woocommerce'),
            ],
        ];
    }
    
    /**
     * Process payment at checkout. Returns redirect to the Toss Widget page.
     *
     * @param int $order_id The ID of the order being processed
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
    
        // Save the current timestamp to use in the Toss order ID
        $timestamp = time();
        update_post_meta($order_id, '_toss_payment_timestamp', $timestamp);
        
        // Mark order as pending while waiting for Toss payment.
        $order->update_status('pending', __('Awaiting Toss payment', 'woocommerce'));
    
        // Empty the cart so items don't remain after user "placed" the order.
        WC()->cart->empty_cart();
    
        // Redirect the user to the Toss Widget checkout page.
        return [
            'result'   => 'success',
            'redirect' => add_query_arg(
                'order_id',
                $order_id,
                get_permalink(get_page_by_path('toss-widget-checkout'))
            ),
        ];
    }
    
    /**
     * Handle the callback (return) from Toss.
     * This URL would be something like: https://your-site.com/wc-api/wc_gateway_toss_payments
     */
    public function handle_callback()
    {
        $request = $_GET;
    
        $this->log_info('Toss callback received. Raw request: ' . print_r($request, true));
        
        if (empty($request['order_id'])) {
            $this->log_error('Missing order_id in callback');
            wp_redirect(wc_get_checkout_url());
            exit;
        }
        
        $order_id = sanitize_text_field($request['order_id']);
        $order    = wc_get_order($order_id);
        
        if (!$order) {
            $this->log_error("Order not found: $order_id");
            wp_redirect(wc_get_checkout_url());
            exit;
        }
    
        // ------------------------------------------------
        // 1) Demo Mode Scenario (simulate success/fail)
        // ------------------------------------------------
        if ($this->mode === 'demo' && isset($request['simulate'])) {
            $simulate_outcome = sanitize_text_field($request['simulate']);
    
            if ($simulate_outcome === 'success') {
                $this->log_info("Simulating success payment for order #$order_id in demo mode.");
                $order->payment_complete('demo_payment_key');
                $order->add_order_note(__('Demo Mode: Payment simulated as successful.', 'woocommerce'));
                
                wp_redirect(add_query_arg(
                    'order_id',
                    $order_id,
                    get_permalink(get_page_by_path('toss-widget-success'))
                ));
                exit;
            } else {
                $this->log_info("Simulating failed payment for order #$order_id in demo mode.");
                $order->update_status('failed', __('Demo Mode: Payment simulated as failed.', 'woocommerce'));
                
                wp_redirect(add_query_arg(
                    'order_id',
                    $order_id,
                    get_permalink(get_page_by_path('toss-widget-fail'))
                ));
                exit;
            }
        }
    
        // ------------------------------------------------
        // 2) Test Mode or Live Mode Payment Handling
        // ------------------------------------------------
        if (!empty($request['paymentKey'])) {
            $payment_key = sanitize_text_field($request['paymentKey']);
            $amount = sanitize_text_field($request['amount'] ?? $order->get_total());
            
            // Create the Toss-formatted order ID
            $timestamp = get_post_meta($order_id, '_toss_payment_timestamp', true);
            if (empty($timestamp)) {
                // If we don't have a stored timestamp, create a new one
                // This could happen if the process was interrupted
                $timestamp = time();
                update_post_meta($order_id, '_toss_payment_timestamp', $timestamp);
            }
            
            // Verify the payment with Toss API call
            $verification_success = $this->toss_widget_verify_payment($payment_key, $order_id, $amount);
    
            if ($verification_success) {
                // Payment verified
                $this->log_info("Payment verified for order #$order_id (paymentKey: $payment_key)");
                $order->payment_complete($payment_key);
                $mode_text = ($this->mode === 'test') ? 'Test Mode: ' : '';
                // $order->add_order_note(__($mode_text . 'Payment completed via Toss Payments.', 'woocommerce'));
                $order->add_order_note(sprintf(__('%sPayment completed via Toss Payments.', 'woocommerce'), $mode_text));

                // Redirect to success page
                wp_redirect(add_query_arg(
                    [
                        'order_id' => $order_id,
                        'payment_mode' => $this->mode
                    ],
                    get_permalink(get_page_by_path('toss-widget-success'))
                ));
                exit;
            } else {
                // Payment failed
                $this->log_error("Payment verification failed for order #$order_id (paymentKey: $payment_key).");
                $order->update_status('failed', __('Payment verification failed.', 'woocommerce'));
                
                // Redirect to fail page
                wp_redirect(add_query_arg(
                    [
                        'order_id' => $order_id,
                        'payment_mode' => $this->mode,
                        'error' => 'verification_failed'
                    ],
                    get_permalink(get_page_by_path('toss-widget-fail'))
                ));
                exit;
            }
        }
    
        // If no valid parameters found, just send the user back to the checkout page.
        $this->log_error("No valid paymentKey or simulate parameter for order #$order_id.");
        wp_redirect(wc_get_checkout_url());
        exit;
    }

   /**
     * Verify payment with Toss API.
     *
     * @param string $payment_key The paymentKey from Toss
     * @param string $order_id    The WooCommerce order ID
     * @param float  $amount      The payment amount
     * @return bool  true if verification is successful, false otherwise
     */
    private function toss_widget_verify_payment($payment_key, $order_id, $amount) {
        $url = "https://api.tosspayments.com/v1/payments/confirm";
        $auth = 'Basic ' . base64_encode($this->secret_key . ':');
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        // Use the provided amount or get from the order
        $payment_amount = (!empty($amount)) ? $amount : $order->get_total();
        
        // Create the same formatted order ID as in the checkout page
        $toss_order_id = 'order_' . $order_id . '_' . get_post_meta($order_id, '_toss_payment_timestamp', true);
        
        $args = [
            'headers' => [
                'Authorization' => $auth,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'paymentKey' => $payment_key,
                'orderId'    => $toss_order_id,
                'amount'     => $payment_amount,
            ]),
            'timeout' => 60,
        ];
    
        $this->log_info("Verifying payment for order #$order_id: " . json_encode($args['body']));
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            $this->log_error('WP Remote error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $this->log_info("Toss API response: " . print_r($body, true));
        
        if (isset($body['status']) && $body['status'] === 'DONE') {
            return true;
        }
        
        if (isset($body['message'])) {
            $this->log_error("Payment verification failed: " . $body['message']);
        }
        
        return false;
    }
    /**
     * Utility to log informational messages.
     *
     * @param string $message Log message
     */
    private function log_info($message)
    {
        if ($this->debug && isset($this->log)) {
            $this->log->add('toss_payments', 'INFO: ' . $message);
        }
    }

    /**
     * Utility to log error messages.
     *
     * @param string $message Log message
     */
    private function log_error($message)
    {
        if ($this->debug && isset($this->log)) {
            $this->log->add('toss_payments', 'ERROR: ' . $message);
        }
    }
}
