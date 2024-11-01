<?php
if (!defined('ABSPATH')) exit;

if ( !isset($_GET['order']) || !wp_verify_nonce($_REQUEST['nonce'], WOOCOMMERCE_CUSTOMER_FEEDBACK_EMAIL_CONTENT_NONCE)) {
    exit("Unauthorized access");
}

if (isset($_GET['answer'])) {
    switch (sanitize_text_field($_GET['answer'])) {
        case 'bad': ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.customer-feedback-page').append("<style>.customer-feedback-page::after{ margin-left:15%}</style>");
                });
            </script>
            <?php break;
        case 'middle': ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.customer-feedback-page').append("<style>.customer-feedback-page::after{ margin-left:48%}</style>");
                });
            </script>
            <?php break;
        case 'good': ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.customer-feedback-page').append("<style>.customer-feedback-page::after{ margin-left:82%}</style>");
                });
            </script>
            <?php break;
    }
} else {
    ?>
    <script>
        jQuery(document).ready(function ($) {
            $('.customer-feedback-page').append("<style>.customer-feedback-page::after{display:none}</style>");
        });
    </script>
<?php
}
?>
    <div class="content-survey">
        <div class="customer-feedback-page">
            <p><?php _e('Please rate the work of our store',self::$customer_feedback_plugin_name); ?>  </p>
            <?php if (isset($_GET['order'])) { ?>
                <p><?php _e('Your order',self::$customer_feedback_plugin_name); ?> №<?php echo intval(base64_decode($_GET['order']));?></p>
            <?php } ?>
            <div class="bad column-left">
                <img src="<?php echo WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_URL . 'includes/images/bad.png' ?>">
                <p><?php _e('Bad',self::$customer_feedback_plugin_name); ?></p>
                <span><?php _e('I did not like the service',self::$customer_feedback_plugin_name); ?></span>
            </div>
            <div class="middle column-center">
                <img src="<?php echo WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_URL . 'includes/images/middle.png'?>">
                <p><?php _e('Usual',self::$customer_feedback_plugin_name); ?></p>
                <span><?php _e('As everywhere, nothing special',self::$customer_feedback_plugin_name); ?></span>
            </div>
            <div class="good column-right">
                <img src="<?php echo WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_URL . 'includes/images/good.png' ?>">
                <p><?php _e('Good',self::$customer_feedback_plugin_name); ?></p>
                <span><?php _e('Like it',self::$customer_feedback_plugin_name); ?></span>
            </div>
        </div>
        <form method="post" action="" id="ajaxform" style=" background: #DDDDDD; padding: 40px;">
            <b><?php _e('Clarify your assessment, for us it is very important',self::$customer_feedback_plugin_name); ?></b>
            <hr>
            <div class="question-block">
                <p class="question"><?php _e('Convenience of the choice of goods',self::$customer_feedback_plugin_name); ?> </p>
                <div class="rating-column">
                    <div id="choose_product">
                        <input type="radio" class="rating" name="customer_feedback_choose_product" value="1"/>
                        <input type="radio" class="rating" name="customer_feedback_choose_product" value="2"/>
                        <input type="radio" class="rating" name="customer_feedback_choose_product" value="3"/>
                        <input type="radio" class="rating" name="customer_feedback_choose_product" value="4"/>
                        <input type="radio" class="rating" name="customer_feedback_choose_product" value="5"/>

                    </div>
                    <input type="text" class="comment_choose_product" name="customer_feedback_comments_choose_product" style="display:none"/>
                    <a href="javascript:void(0);" id="comment-choose_product"> <?php _e('Comments',self::$customer_feedback_plugin_name); ?></a>
                </div>
            </div>
            <hr>
            <div class="question-block">
                <p class="question"><?php _e('Consultation of the manager',self::$customer_feedback_plugin_name); ?></p>
                <div class="rating-column">
                    <div id="manager_cons">
                        <input type="radio" class="rating" name="customer_feedback_manager_cons" value="1"/>
                        <input type="radio" class="rating" name="customer_feedback_manager_cons" value="2"/>
                        <input type="radio" class="rating" name="customer_feedback_manager_cons" value="3"/>
                        <input type="radio" class="rating" name="customer_feedback_manager_cons" value="4"/>
                        <input type="radio" class="rating" name="customer_feedback_manager_cons" value="5"/>
                    </div>
                    <input type="text" class="comment_manager_cons" name="customer_feedback_comments_manager_cons" style=" display:none"/>
                    <a href="javascript:void(0);" id="comment-manager_cons"><?php _e('Comments',self::$customer_feedback_plugin_name); ?></a>
                </div>
            </div>
            <hr>
            <div class="question-block">
                <p class="question"><?php _e('Delivery of goods',self::$customer_feedback_plugin_name); ?></p>
                <div class="rating-column">
                    <div id="product_delivery">
                        <input type="radio" class="rating" name="customer_feedback_product_delivery" value="1"/>
                        <input type="radio" class="rating" name="customer_feedback_product_delivery" value="2"/>
                        <input type="radio" class="rating" name="customer_feedback_product_delivery" value="3"/>
                        <input type="radio" class="rating" name="customer_feedback_product_delivery" value="4"/>
                        <input type="radio" class="rating" name="customer_feedback_product_delivery" value="5"/>
                    </div>
                    <input type="text" class="comment_product_delivery" name="customer_feedback_comments_product_delivery" style=" display:none"/>
                    <a href="javascript:void(0);" id="comment-product_delivery"><?php _e('Comments',self::$customer_feedback_plugin_name); ?></a>
                </div>
            </div>
            <hr>
            <div class="question-block">
                <p class="question"><?php _e('Payment Process',self::$customer_feedback_plugin_name); ?></p>
                <div class="rating-column">
                    <div id="payment_process">
                        <input type="radio" class="rating" name="customer_feedback_payment_process" value="1"/>
                        <input type="radio" class="rating" name="customer_feedback_payment_process" value="2"/>
                        <input type="radio" class="rating" name="customer_feedback_payment_process" value="3"/>
                        <input type="radio" class="rating" name="customer_feedback_payment_process" value="4"/>
                        <input type="radio" class="rating" name="customer_feedback_payment_process" value="5"/>
                    </div>
                    <input type="text" class="comment_payment_process" name="customer_feedback_comments_payment_process" style="display:none"/>
                    <a href="javascript:void(0);" id="comment-payment_process"><?php _e('Comments',self::$customer_feedback_plugin_name); ?></a>
                </div>
            </div>
            <hr>
            <div id="comment-all">
                <textarea placeholder="<?php _e('Enter your feedback',self::$customer_feedback_plugin_name) ?>" name="customer_feedback_comment_all" cols="60" rows="5"></textarea>
            </div>
            <input type="submit" class="customer_feedback_submit_button" value="<?php _e('Give a feedback',self::$customer_feedback_plugin_name); ?>"/>
            <span class="submit_button_applyke_wc_feedback_loader" style="display:none;background: url('<?php echo WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_URL?>includes/images/ajax-loader.gif') no-repeat; height:11px; width:16px"></span>
        </form>
    </div>
    <div id="popup1" class="aplk_overlay">
        <div class="aplk_popup"><br>
            <a class="aplk_close" href="#">&times;</a>
            <div class="content_thank">
                <?php _e('Thank you for your feedback!',self::$customer_feedback_plugin_name); ?>
            </div>
        </div>
    </div>

<?php
if ( !isset($_GET['order']) || !wp_verify_nonce($_REQUEST['nonce'], WOOCOMMERCE_CUSTOMER_FEEDBACK_EMAIL_CONTENT_NONCE)) {
    exit("Unauthorized access");
}

if (isset($_GET['answer']) && isset($_GET['order'])) {
    $nonce =  wp_create_nonce(WOOCOMMERCE_CUSTOMER_FEEDBACK_CONTENT_PAGE_NONCE);?>
    <script>
        jQuery(document).ready(function($) {
            $("#ajaxform").submit(function (e) {
                $(".submit_button_applyke_wc_feedback_loader").css('display', 'inline-block');
                e.preventDefault();
                var formdata = $('#ajaxform').serializeArray();
                var data = {};
                $(formdata ).each(function(index, obj){
                    data[obj.name] = obj.value;
                });
                $.ajax({
                    type: "POST",
                    url: ajax_object.ajax_url,
                    data:{
                        action: "save_answers",
                        data: data,
                        answer:"<?php echo sanitize_text_field($_GET['answer']) ?>",
                        orderId:"<?php echo sanitize_text_field($_GET['order']) ?>",
                        nonce:"<?php echo $nonce?>"
                    },
                    success: function (data) {
                        if (data.error !== undefined){
                            $(".content_thank").replaceWith(data.error + '<br>');
                        }
                        $(".aplk_overlay").removeClass('block').removeClass('hidden').addClass('visible');
                        $(".submit_button_applyke_wc_feedback_loader").css('display', 'none');

                    },
                    error: function (xhr, str) {
                        alert(' <?php _e('An error occurred:',self::$customer_feedback_plugin_name); ?> ' + xhr.responseCode);
                    }
                });
            });
        }) ;
    </script>
<?php } ?>