<?php
if (!defined('ABSPATH')) exit;

check_ajax_referer(WOOCOMMERCE_CUSTOMER_FEEDBACK_SEND_SURVEY_NONCE, 'nonce');

if (isset($_POST['customer_feedback_id'])) {
    $order = new WC_Order((int)$_POST['customer_feedback_id']);
} else {
    global $post;
    $order = new WC_Order((int)$post->ID);
}
$orderId = 0;
if (!empty($order)) {
    $orderId = (int)$order->get_id();
    $user_id = get_post_meta($orderId, '_customer_user', true);
    if (!empty($user_id)) {
        $user =  get_userdata((int)$user_id);
        $user_name = ', '.$user->display_name;
    }
    else{
        $user_name = '';
    }
}

$mail = new WC_Emails();
$nonce = wp_create_nonce(WOOCOMMERCE_CUSTOMER_FEEDBACK_EMAIL_CONTENT_NONCE);
$url_bad = add_query_arg(array(
    'answer' => 'bad',
    'order' => base64_encode($orderId),
    'nonce' => $nonce
), get_permalink(get_page_by_path('customer-feedback')));
$url_middle = add_query_arg(array(
    'answer' => 'middle',
    'order' => base64_encode($orderId),
    'nonce' => $nonce
), get_permalink(get_page_by_path('customer-feedback')));
$url_good = add_query_arg(array(
    'answer' => 'good',
    'order' => base64_encode($orderId),
    'nonce' => $nonce
), get_permalink(get_page_by_path('customer-feedback')));

$message = '
    <b>' . __('Hello','woocommerce_customer_feedback') .$user_name.'.'. '</b>
    <b>' . __('You recently made a purchase in the store', 'woocommerce_customer_feedback') .' '.WOOCOMMERCE_CUSTOMER_FEEDBACK__SITE_NAME. '</b>
    <p>' . __('Your order', 'woocommerce_customer_feedback') . ' № ' . $orderId . '    
    <div class="bad column-left" style=" float: left; width: 33%;     text-align: center;">
        <a href="' . $url_bad .'">
            <img src="' . WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_URL . 'includes/images/bad.png' . '">
            <h2 style="text-align:center;  line-height:0">' . __('Bad', 'woocommerce_customer_feedback') . '</h2>
        </a>
        <span>' . __('I did not like the service', 'woocommerce_customer_feedback') . '</span>
    </div>
    <div class="middle column-center" style="display: inline-block; width: 33%;     text-align: center;">
        <a href="' . $url_middle .'">
            <img src="' . WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_URL . 'includes/images/middle.png' . '">
            <h2 style="text-align:center;  line-height:0">' . __('Usual', 'woocommerce_customer_feedback') . '</h2>
        </a>
        <span>' . __('As everywhere, nothing special', 'woocommerce_customer_feedback') . '</span>
    </div>
    <div class="good column-right" style="float: right; width: 33%;     text-align: center;">
        <a href="' . $url_good . '">
            <img src="' . WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_URL . 'includes/images/good.png' . '">
            <h2 style="text-align:center; line-height:0"> ' . __('Good','woocommerce_customer_feedback') . '</h2>
        </a>
        <span>' . __('Like it','woocommerce_customer_feedback') . '</span>
    </div>';

echo $mail->wrap_message(__(' Estimate the level of service of our store','woocommerce_customer_feedback'), $message);

