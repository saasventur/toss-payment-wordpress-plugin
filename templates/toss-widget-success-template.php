<?php
/* Template for Toss Widget Success Page - Supporting Demo, Test and Live modes */
get_header();

// Get request parameters
$order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';
$payment_mode = isset($_GET['payment_mode']) ? sanitize_text_field($_GET['payment_mode']) : 'live';
$payment_key = isset($_GET['paymentKey']) ? sanitize_text_field($_GET['paymentKey']) : '';

// Get the order
$order = ($order_id) ? wc_get_order($order_id) : null;

if (!$order) {
    echo '<div class="woocommerce"><div class="woocommerce-error">Order not found.</div></div>';
    get_footer();
    exit;
}

// Make sure the cart is empty
WC()->cart->empty_cart();

// Set mode-specific text
$mode_tag = '';
if ($payment_mode === 'demo') {
    $mode_tag = '<div style="background-color: #d4edda; color: #155724; padding: 10px; text-align: center; border-radius: 6px; margin: 20px 0; font-size: 14px;">
                    <strong>데모 모드:</strong> 실제 결제가 이루어지지 않았습니다.
                </div>';
} elseif ($payment_mode === 'test') {
    $mode_tag = '<div style="background-color: #fff3cd; color: #856404; padding: 10px; text-align: center; border-radius: 6px; margin: 20px 0; font-size: 14px;">
                    <strong>테스트 모드:</strong> 실제 결제가 이루어지지 않았습니다.
                </div>';
}
?>

<div class="toss-success-container" style="max-width: 800px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="width: 70px; height: 70px; background-color: #3182f6; border-radius: 50%; display: inline-flex; justify-content: center; align-items: center; margin-bottom: 20px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <h1 style="color: #333; font-size: 28px; margin-bottom: 10px;">결제 완료</h1>
        <p style="color: #666; font-size: 16px; margin-bottom: 5px;">주문이 성공적으로 접수되었습니다.</p>
        <?php echo $mode_tag; ?>
    </div>
    
    <div style="background: #f9f9f9; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
        <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 18px;">주문 정보</h3>
        <p><strong>주문 번호:</strong> #<?php echo esc_html($order_id); ?></p>
        <p><strong>주문 날짜:</strong> <?php echo esc_html($order->get_date_created()->date_i18n('Y-m-d H:i')); ?></p>
        <p><strong>결제 금액:</strong> <?php echo esc_html(number_format($order->get_total())); ?> KRW</p>
        <p><strong>결제 방법:</strong> Toss Payments</p>
        <?php if ($payment_key): ?>
            <p><strong>결제 키:</strong> <?php echo esc_html($payment_key); ?></p>
        <?php endif; ?>
    </div>
    
    <div style="margin-bottom: 30px;">
        <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 18px;">배송 정보</h3>
        <p><strong>예상 배송일:</strong> 3-4영업일 이내</p>
        <p>주문하신 상품은 결제 확인 후 3-4영업일 이내에 배송될 예정입니다. 배송 상태는 마이페이지에서 확인하실 수 있습니다.</p>
    </div>
    
    <div style="display: flex; justify-content: center; gap: 15px;">
        <a href="<?php echo esc_url(wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount'))); ?>"
           style="display: inline-block; padding: 12px 20px; background: #eee; color: #333; text-decoration: none; border-radius: 6px; font-weight: 600;">
            주문 내역 보기
        </a>
        <a href="<?php echo esc_url(home_url()); ?>"
           style="display: inline-block; padding: 12px 20px; background: #3182f6; color: white; text-decoration: none; border-radius: 6px; font-weight: 600;">
            쇼핑 계속하기
        </a>
    </div>
</div>

