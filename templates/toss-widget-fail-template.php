<?php
/* Template for Toss Widget Fail Page - Supporting Demo, Test and Live modes */
// Get header for styling
get_header();

// Get request parameters
$order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';
$payment_mode = isset($_GET['payment_mode']) ? sanitize_text_field($_GET['payment_mode']) : 'live';
$error_code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
$error_message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';

// Get the order if available
$order = ($order_id) ? wc_get_order($order_id) : null;
?>

<div class="toss-fail-container" style="max-width: 800px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="width: 70px; height: 70px; background-color: #dc3545; border-radius: 50%; display: inline-flex; justify-content: center; align-items: center; margin-bottom: 20px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </div>
        <h1 style="color: #333; font-size: 28px; margin-bottom: 10px;">결제 실패</h1>
        <?php if ($payment_mode === 'demo'): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px; text-align: center; border-radius: 6px; margin: 20px 0; font-size: 14px;">
                <strong>데모 모드:</strong> 실제 결제가 이루어지지 않았습니다.
            </div>
        <?php elseif ($payment_mode === 'test'): ?>
            <div style="background-color: #fff3cd; color: #856404; padding: 10px; text-align: center; border-radius: 6px; margin: 20px 0; font-size: 14px;">
                <strong>테스트 모드:</strong> 실제 결제가 이루어지지 않았습니다.
            </div>
        <?php endif; ?>
    </div>
    
    <div style="background: #f9f9f9; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
        <?php if ($order): ?>
            <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 18px;">주문 정보</h3>
            <p><strong>주문 번호:</strong> #<?php echo esc_html($order_id); ?></p>
            <p><strong>주문 날짜:</strong> <?php echo esc_html($order->get_date_created()->date_i18n('Y-m-d H:i')); ?></p>
            <p><strong>결제 금액:</strong> <?php echo esc_html(number_format($order->get_total())); ?> KRW</p>
        <?php endif; ?>
        
        <h3 style="margin-top: 20px; margin-bottom: 15px; font-size: 18px;">오류 정보</h3>
        <?php if ($error_code && $error_message): ?>
            <p><strong>오류 코드:</strong> <?php echo esc_html($error_code); ?></p>
            <p><strong>오류 메시지:</strong> <?php echo esc_html($error_message); ?></p>
        <?php else: ?>
            <p>결제가 취소되었거나 실패했습니다. 다시 시도해 주세요.</p>
        <?php endif; ?>
    </div>
    
    <div style="display: flex; justify-content: center; gap: 15px;">
        <a href="<?php echo esc_url(wc_get_checkout_url()); ?>"
           style="display: inline-block; padding: 12px 20px; background: #eee; color: #333; text-decoration: none; border-radius: 6px; font-weight: 600;">
            결제 다시 시도
        </a>
        <a href="<?php echo esc_url(home_url()); ?>"
           style="display: inline-block; padding: 12px 20px; background: #3182f6; color: white; text-decoration: none; border-radius: 6px; font-weight: 600;">
            쇼핑 계속하기
        </a>
    </div>
</div>
