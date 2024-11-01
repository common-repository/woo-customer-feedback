<?php
if (!defined('ABSPATH')) exit;

check_ajax_referer(WOOCOMMERCE_CUSTOMER_FEEDBACK_COMMENTS_FORM_NONCE, 'nonce');

if (isset($_POST['customer_feedback_id'])) {
    $order = new WC_Order((int)$_POST['customer_feedback_id']);
} else {
    global $post;
    $order = new WC_Order((int)$post->ID);
}
$orderId = 0;
$mail = new WC_Emails();
$message = '<h3>'.__('You recently made a purchase in the store', 'woocommerce_customer_feedback').' '. WOOCOMMERCE_CUSTOMER_FEEDBACK__SITE_NAME .'. '.' </h3>
 <h3>'.__('Leave a review about the purchased goods', 'woocommerce_customer_feedback').'</h3>
 <table id="template_body" style="width: 100%;color: #737373;border: 1px solid #e4e4e4;">';

$order_item = $order->get_items();
foreach ($order_item as $product) {
    $product_name = $product['name'];
    $url = get_permalink($product['product_id']);
    $message .= '<tr class="tr" style=" text-align: left;color: #737373;  border: 1px solid #e4e4e4; ">
            <td class="td"  style="font-size: 18px;"> <a href="' . $url . '" >' . $product_name . '</a></td>' .
           '<td style="font-size: 16px;">
            <a href="' . $url . '#tab-reviews'.'" style="font-size: 16px;  display: block;  width: 120px;  height:  17px; background: #71b84d;  padding: 10px; text-align: center;  border-radius: 5px; color: white;
                 font-weight: normal; text-decoration: none;  line-height: 17px;"> ' . __('Leave feedback', 'woocommerce_customer_feedback') . '</a>' .
           '</td>
            <tr>';

}

$message .= '</table>';

echo $mail->wrap_message(__('Leave a review about the purchased goods', 'woocommerce_customer_feedback'), $message);

