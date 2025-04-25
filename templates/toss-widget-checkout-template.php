<?php
/* Template for Toss Widget Checkout - Supporting Demo, Test and Live modes */
// Optionally remove get_header() and get_footer() for a blank page
// get_header();

$order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';
if (!$order_id) {
    echo 'Invalid order.';
    // get_footer();
    exit;
}

$order = wc_get_order($order_id);
if (!$order) {
    echo 'Order not found.';
    // get_footer();
    exit;
}

// Get payment gateway to access settings
$payment_gateways = WC_Payment_Gateways::instance();
$gateway = $payment_gateways->payment_gateways()['toss_payments_widget'];

// Get the mode and keys
$mode = $gateway->get_option('mode', 'demo');
$client_key = '';
$secret_key = '';

// Set keys based on mode
switch ($mode) {
    case 'demo':
        $client_key = $gateway->get_option('demo_client_key', 'test_gck_docs_Ovk5rk1EwkEbP0W43n07xlzm');
        $secret_key = $gateway->get_option('demo_secret_key', 'test_gsk_docs_OaPz8L5KdmQXkzRz3y47BMw6');
        break;
    case 'test':
        $client_key = $gateway->get_option('test_client_key');
        $secret_key = $gateway->get_option('test_secret_key');
        break;
    case 'live':
        $client_key = $gateway->get_option('live_client_key');
        $secret_key = $gateway->get_option('live_secret_key');
        break;
    default:
        $client_key = $gateway->get_option('demo_client_key', 'test_gck_docs_Ovk5rk1EwkEbP0W43n07xlzm');
        $secret_key = $gateway->get_option('demo_secret_key', 'test_gsk_docs_OaPz8L5KdmQXkzRz3y47BMw6');
}

// Get order details
$amount = $order->get_total();

// If user is logged in, create a stable customerKey
$user_id = get_current_user_id();
if ($user_id > 0) {
    $customer_key = 'user_' . $user_id;
} else {
    // For guests, generate a random key
    $customer_key = 'guest_' . uniqid();
}

// Get order items for display
$items = $order->get_items();

// Create a properly formatted order ID for Toss (must be 6-64 chars, alphanumeric + - _)
$timestamp = time();
update_post_meta($order_id, '_toss_payment_timestamp', $timestamp);
$toss_order_id = 'order_' . $order_id . '_' . $timestamp;

// Set success and fail URLs
$success_url = site_url('/wc-api/wc_gateway_toss_payments') . '?order_id=' . $order_id;
$fail_url = site_url('/toss-widget-fail') . '?order_id=' . $order_id;

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Toss Payments - Checkout</title>
    <script src="https://js.tosspayments.com/v2/standard"></script>
    <style>
        /* Reset margins/padding on html/body */
        html, body {
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
        }
        /* Base body styles */
        body {
            font-family: 'Apple SD Gothic Neo', 'Noto Sans KR', sans-serif;
            line-height: 1.5;
            color: #333;
        }
        /* Centered container */
        .checkout-container {
            max-width: 800px;
            margin: 40px auto; /* auto left/right to center */
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        .header {
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
            font-weight: 600;
        }
        .order-info {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .order-items {
            margin-bottom: 20px;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .item-name {
            flex: 1;
        }
        .item-price {
            text-align: right;
            font-weight: 600;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            font-size: 18px;
            font-weight: 700;
            border-top: 2px solid #eee;
        }
        #payment-method {
            margin-bottom: 20px;
        }
        #agreement {
            margin-bottom: 20px;
        }
        #pay-button {
            background-color: #3182f6;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        #pay-button:hover {
            background-color: #2c5aa0;
        }
        .test-mode-banner {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            text-align: center;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .demo-mode-banner {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            text-align: center;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            .checkout-container {
                margin: 20px auto;
                padding: 15px;
            }
        }
        .demo-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .demo-button {
            flex: 1;
            padding: 10px;
            border-radius: 4px;
            border: none;
            font-weight: 600;
            cursor: pointer;
        }
        .demo-success {
            background-color: #28a745;
            color: white;
        }
        .demo-fail {
            background-color: #dc3545;
            color: white;
        }
    </style>
    <?php wp_head(); ?>
</head>
<body>
    <div class="checkout-container">
        <?php if ($mode === 'demo'): ?>
        <div class="demo-mode-banner">
            <strong>데모 모드:</strong> 이것은 데모 모드입니다. 실제 결제가 이루어지지 않습니다. "성공" 또는 "실패"를 선택하여 결제 프로세스를 시뮬레이션 하세요.
        </div>
        <?php elseif ($mode === 'test'): ?>
        <div class="test-mode-banner">
            <strong>테스트 모드:</strong> 실제 결제가 이루어지지 않습니다. 테스트용으로만 사용하세요.
        </div>
        <?php endif; ?>
        
        <div class="header">
            <h1>주문 결제</h1>
        </div>
        
        <div class="order-info">
            <p><strong>주문 번호:</strong> #<?php echo esc_html($order_id); ?></p>
            <p><strong>주문자:</strong> <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></p>
            <p><strong>이메일:</strong> <?php echo esc_html($order->get_billing_email()); ?></p>
        </div>
        
        <div class="order-items">
            <h3>주문 상품</h3>
            <?php foreach ($items as $item): 
                $product = $item->get_product();
                $item_total = $order->get_line_total($item, true);
            ?>
            <div class="order-item">
                <div class="item-name">
                    <?php echo esc_html($item->get_name()); ?> × <?php echo esc_html($item->get_quantity()); ?>
                </div>
                <div class="item-price">
                    <?php echo number_format($item_total); ?> KRW
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="total-row">
                <div>총 결제 금액</div>
                <div><?php echo number_format($amount); ?> KRW</div>
            </div>
        </div>
        
        <?php if ($mode === 'demo'): ?>
            <!-- Demo Mode - Show success/fail buttons instead of payment widget -->
            <div>
                <h3>데모 모드</h3>
                <p>결제 결과를 시뮬레이션하려면 아래 버튼 중 하나를 선택하세요:</p>
                <div class="demo-buttons">
                    <button class="demo-button demo-success" id="demo-success-button">성공</button>
                    <button class="demo-button demo-fail" id="demo-fail-button">실패</button>
                </div>
            </div>
        <?php else: ?>
            <!-- Test/Live Mode - Show actual payment widget -->
            <div id="payment-method"></div>
            <div id="agreement"></div>
            <button id="pay-button">결제하기</button>
        <?php endif; ?>
    </div>
    
    <script>
    <?php if ($mode === 'demo'): ?>
    // Demo mode script
    document.getElementById('demo-success-button').addEventListener('click', function() {
        window.location.href = '<?php echo site_url('/wc-api/wc_gateway_toss_payments'); ?>?order_id=<?php echo esc_js($order_id); ?>&simulate=success';
    });
    
    document.getElementById('demo-fail-button').addEventListener('click', function() {
        window.location.href = '<?php echo site_url('/wc-api/wc_gateway_toss_payments'); ?>?order_id=<?php echo esc_js($order_id); ?>&simulate=fail';
    });
    <?php else: ?>
    // Test or Live mode script
    (async function() {
      const orderId     = '<?php echo esc_js($toss_order_id); ?>'; // Using our formatted Toss-compliant order ID
      const wooOrderId  = '<?php echo esc_js($order_id); ?>'; // Original WooCommerce order ID for callbacks
      const amount      = <?php echo esc_js($amount); ?>;
      const clientKey   = '<?php echo esc_js($client_key); ?>';
      const customerKey = '<?php echo esc_js($customer_key); ?>';
      const paymentMode = '<?php echo esc_js($mode); ?>';
      
      // Initialize Toss Payments SDK
      const tossPayments = TossPayments(clientKey);
      
      // Payment widget instance
      const widgets = tossPayments.widgets({
        customerKey: customerKey
      });
      
      // Set the total amount
      await widgets.setAmount({
        currency: 'KRW',
        value: amount
      });
      
      // Render Payment UI + Agreement UI
      await Promise.all([
        widgets.renderPaymentMethods({
          selector: '#payment-method',
          variantKey: 'DEFAULT', // the default UI
        }),
        widgets.renderAgreement({
          selector: '#agreement',
          variantKey: 'AGREEMENT',
        }),
      ]);
      
      // Payment Button
      document.getElementById('pay-button').addEventListener('click', async function() {
        try {
          await widgets.requestPayment({
            orderId: orderId,
            orderName: 'Order #' + wooOrderId,
            successUrl: '<?php echo esc_js($success_url); ?>&paymentType=NORMAL',
            failUrl: '<?php echo esc_js($fail_url); ?>',
            customerEmail: '<?php echo esc_js($order->get_billing_email()); ?>',
            customerName: '<?php echo esc_js($order->get_billing_first_name()); ?>'
          });
        } catch (error) {
          console.error('Payment request error:', error);
          alert('결제 요청 중 오류가 발생했습니다. 콘솔을 확인하세요.');
        }
      });
    })();
    <?php endif; ?>
    </script>
    <?php wp_footer(); ?>
</body>
</html>