<?php
/*
Plugin Name: WooCommerce Customer Feedback
Description: Customer feedback service for WooCommerce. Makes surveys and collects data about your store service level and ordered products. Adds submenu to the WooCommerce menu. There is no hidden payments or ads inside!
Version:   1.0.1
Author: Applyke
Author URI: https://applyke.com
Text Domain: Applyke
*/
if (!defined('ABSPATH')) exit;

define('WOOCOMMERCE_CUSTOMER_FEEDBACK_VERSION', '1.1');
define('WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_URL', plugin_dir_url(__FILE__));
define('WOOCOMMERCE_CUSTOMER_FEEDBACK__SITE_NAME', get_bloginfo('name'));
define('WOOCOMMERCE_CUSTOMER_FEEDBACK_PLUGIN_NAME', 'WooCommerce Customer Feedback');

define('WOOCOMMERCE_CUSTOMER_FEEDBACK_COMMENTS_FORM_NONCE', WOOCOMMERCE_CUSTOMER_FEEDBACK_PLUGIN_NAME . 'comments_form');
define('WOOCOMMERCE_CUSTOMER_FEEDBACK_SEND_SURVEY_NONCE', WOOCOMMERCE_CUSTOMER_FEEDBACK_PLUGIN_NAME . 'send_survey_on_email');
define('WOOCOMMERCE_CUSTOMER_FEEDBACK_EMAIL_CONTENT_NONCE', WOOCOMMERCE_CUSTOMER_FEEDBACK_PLUGIN_NAME . 'email_content');
define('WOOCOMMERCE_CUSTOMER_FEEDBACK_CONTENT_PAGE_NONCE', WOOCOMMERCE_CUSTOMER_FEEDBACK_PLUGIN_NAME . 'content_page');

if (!class_exists('Woocommerce_Customer_Feedback_Service')) {
    class Woocommerce_Customer_Feedback_Service
    {
        public $customer_feedback_initiated = false;
        public static $customer_feedback_plugin_name = 'woocommerce_customer_feedback';

        function __construct()
        {
            if (!in_array('woocommerce/woocommerce.php', (array)get_option('active_plugins', array()))) {
                add_action('admin_notices', array(__CLASS__, 'plugin_activation_error'));
                return;
            }

            register_activation_hook(__FILE__, array(__CLASS__, 'plugin_activation'), 10);
            register_deactivation_hook(__FILE__, array(__CLASS__, 'plugin_deactivation'), 10);
            register_uninstall_hook(__FILE__, array(__CLASS__, 'woocommerce_customer_feedback_delete_plugin'), 20);

            add_action('get_header', array(__CLASS__, 'load_scripts'), 30);
            add_action('admin_footer', array(__CLASS__, 'send_survey_on_email'));

            add_filter('wp_enqueue_scripts', array(__CLASS__, 'hide_title'), 30);
            add_filter('wp_mail_from_name', array(__CLASS__, 'from_mail_name'));
            add_shortcode(self::$customer_feedback_plugin_name, array(__CLASS__, 'woocommerce_customer_feedback_display'));
            add_action('wp_enqueue_scripts', array(__CLASS__, 'customer_feedback_add_javascript'));
            add_action('admin_enqueue_scripts', array(__CLASS__, 'customer_feedback_add_javascript'));

            add_action('plugins_loaded', array(__CLASS__, 'woocommerce_customer_feedback_load_textdomain'));
            add_action('wp_ajax_delete_post', array(__CLASS__, 'delete_post'));
            add_action('phpmailer_init', array(__CLASS__, 'embed_image_in_mail'));

            add_action('wp_ajax_service_feedback_action', array(__CLASS__, 'service_feedback_action'));
            add_action('wp_ajax_save_answers', array(__CLASS__, 'save_answers'));

            add_action('wp_ajax_comments_form', array(__CLASS__, 'comments_form'));
            add_filter('woocommerce_admin_order_data_after_billing_address', array(__CLASS__, 'customer_feedback_order_column'));
            add_filter('woocommerce_before_main_content', array(__CLASS__, 'hide_title'), 30);
            add_action('admin_menu', array(__CLASS__, 'customer_feedback_custom_submenu_page_callback'));

        }


        public static function save_answers()
        {
            global $wpdb;

            check_ajax_referer(WOOCOMMERCE_CUSTOMER_FEEDBACK_CONTENT_PAGE_NONCE, 'nonce');

            if (isset($_POST['orderId']) && isset($_POST['answer'])) {
                $table_name = $wpdb->prefix . "applyke_wc_feedback_survey";
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {

                    $order_id = (int)base64_decode($_POST['orderId']);
                    $survey_answer = $wpdb->get_results("SELECT * FROM {$table_name}  WHERE {$table_name}.`order_id`= {$order_id} LIMIT 1");

                    if (!empty($survey_answer[0]) && empty($survey_answer[0]->main_answer)) {
                        $user_id = get_post_meta((int)base64_decode($_POST['orderId']), '_customer_user', true);
                        if (!empty($user_id)) {
                            $user = get_userdata($user_id);
                            $user_id = $user->ID;
                        } else {
                            $user_id = 0;
                        }
                        $table_name = $wpdb->prefix . "applyke_wc_feedback_survey";

                        $wpdb->update($table_name, array(
                            'client_ip' => $_SERVER['REMOTE_ADDR'],
                            'user_id' => $user_id,
                            'order_id' => (int)base64_decode($_POST['orderId']),
                            'main_answer' => sanitize_text_field($_POST['answer']),
                            'answer_choose_product' => (isset($_POST['data']['customer_feedback_choose_product']) ? (int)$_POST['data']['customer_feedback_choose_product'] : 0),
                            'comment_choose_product' => (isset($_POST['data']['customer_feedback_comments_choose_product']) ? sanitize_text_field($_POST['data']['customer_feedback_comments_choose_product']) : ''),
                            'answer_manager_cons' => (isset($_POST['data']['customer_feedback_manager_cons']) ? (int)$_POST['data']['customer_feedback_manager_cons'] : 0),
                            'comment_manager_cons' => (isset($_POST['data']['customer_feedback_comments_manager_cons']) ? sanitize_text_field($_POST['data']['customer_feedback_comments_manager_cons']) : ''),
                            'answer_product_delivery' => (isset($_POST['data']['customer_feedback_product_delivery']) ? (int)$_POST['data']['customer_feedback_product_delivery'] : 0),
                            'comment_product_delivery' => (isset($_POST['data']['customer_feedback_comments_product_delivery']) ? sanitize_text_field($_POST['data']['customer_feedback_comments_product_delivery']) : ''),
                            'answer_payment_process' => (isset($_POST['data']['customer_feedback_payment_process']) ? (int)$_POST['data']['customer_feedback_payment_process'] : 0),
                            'comment_payment_process' => (isset($_POST['data']['customer_feedback_comments_payment_process']) ? sanitize_text_field($_POST['data']['customer_feedback_comments_payment_process']) : ''),
                            'comment_all' => (isset($_POST['data']['customer_feedback_comment_all']) ? sanitize_text_field($_POST['data']['customer_feedback_comment_all']) : ''),
                            'created_at' => current_time('mysql')), array('order_id' => (int)base64_decode($_POST['orderId'])));
                        self::update_average_values();
                    } elseif (empty($survey_answer[0])) {
                        wp_send_json(array('error' => __('Thank you, but your order doesn\'t exist', self::$customer_feedback_plugin_name)));

                    } else {
                        wp_send_json(array('error' => __('Thank you, but you have already left your review', self::$customer_feedback_plugin_name)));
                    }
                }
            } else {
                wp_send_json(array('error' => __('Number of order or answer doesn\'t exist', self::$customer_feedback_plugin_name)));
            }
        }

        public static function update_average_values()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "applyke_wc_feedback_survey";
            $total_count = $wpdb->get_row("SELECT COUNT(*) AS totalCount FROM {$table_name} WHERE {$table_name}.`main_answer` IS NOT NULL");
            if (!empty($total_count->totalCount)) {
                $main_answers = $wpdb->get_results(" SELECT {$table_name}.`main_answer`, COUNT(*) AS Total , (COUNT(*) / {$total_count->totalCount}) * 100 AS percent  
                            FROM  {$table_name}
                            WHERE {$table_name}.`main_answer` IS NOT NULL
                            GROUP BY {$table_name}.`main_answer`;");
            }
            $persent_bad = 0;
            $persent_middle = 0;
            $persent_good = 0;
            if (!empty($main_answers)) {
                foreach ($main_answers as $main_answer) {
                    switch ($main_answer->main_answer) {
                        case 'bad':
                            $persent_bad = $main_answer->percent;
                            break;
                        case 'middle':
                            $persent_middle = $main_answer->percent;
                            break;
                        case 'good':
                            $persent_good = $main_answer->percent;
                            break;
                    }
                }
            }
            $summ_choose_product = $wpdb->get_row("SELECT AVG({$table_name}.`answer_choose_product`) AS total FROM {$table_name}");
            $summ_manager_cons = $wpdb->get_row("SELECT AVG({$table_name}.`answer_manager_cons`) AS total FROM {$table_name}");
            $summ_product_delivery = $wpdb->get_row("SELECT AVG({$table_name}.`answer_product_delivery`) AS total FROM {$table_name}");
            $summ_payment_process = $wpdb->get_row("SELECT AVG({$table_name}.`answer_payment_process`) AS total FROM {$table_name}");

            $table_name_summ = $wpdb->prefix . 'applyke_wc_feedback_survey_summ_value';
            $saved_result = $wpdb->get_results(
                "SELECT * FROM  {$table_name_summ}  LIMIT 1"
            );
            if (empty($saved_result)) {
                $wpdb->insert($wpdb->prefix . 'applyke_wc_feedback_survey_summ_value', array(
                    'summ_main_answer_good' => $persent_good,
                    'summ_main_answer_middle' => $persent_middle,
                    'summ_main_answer_bad' => $persent_bad,
                    'summ_choose_product' => isset($summ_choose_product) ? $summ_choose_product->total : 0,
                    'summ_manager_cons' => isset($summ_choose_product) ? $summ_manager_cons->total : 0,
                    'summ_product_delivery' => isset($summ_choose_product) ? $summ_product_delivery->total : 0,
                    'summ_payment_process' => isset($summ_choose_product) ? $summ_payment_process->total : 0,
                    'created_at' => current_time('mysql')));
            } else {
                $result_id = $saved_result[0]->id;
                $wpdb->update($wpdb->prefix . 'applyke_wc_feedback_survey_summ_value', array(
                    'summ_main_answer_good' => $persent_good,
                    'summ_main_answer_middle' => $persent_middle,
                    'summ_main_answer_bad' => $persent_bad,
                    'summ_choose_product' => isset($summ_choose_product) ? $summ_choose_product->total : 0,
                    'summ_manager_cons' => isset($summ_choose_product) ? $summ_manager_cons->total : 0,
                    'summ_product_delivery' => isset($summ_choose_product) ? $summ_product_delivery->total : 0,
                    'summ_payment_process' => isset($summ_choose_product) ? $summ_payment_process->total : 0,
                    'created_at' => current_time('mysql')), array('id' => $result_id));
            }


        }

        public static function woocommerce_customer_feedback_load_textdomain()
        {
            load_plugin_textdomain(self::$customer_feedback_plugin_name, false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        public static function plugin_activation_error()
        {
            $message = '<div class="error"><p>Woocommerce need to be at least 4.0 to activate and work  plugin "' . WOOCOMMERCE_CUSTOMER_FEEDBACK_PLUGIN_NAME . '"</p></div>';
            echo $message;
        }

        public static function customer_feedback_order_column()
        {
            global $post, $wpdb;
            $table_name = $wpdb->prefix . "applyke_wc_feedback_survey";

            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                echo '<div class="customer-feedback-admin-order">';
                echo '<h3>Customer Feedback</h3>';
                $order_id = (int)$post->ID;
                $survey_answer = $wpdb->get_results("SELECT * FROM {$table_name}  WHERE {$table_name}.`order_id`= {$order_id} LIMIT 1");

                $product_feedback_is_sent = !empty($survey_answer[0]) && !empty($survey_answer[0]->send_comments_form_date);
                $service_feedback_is_sent = !empty($survey_answer[0]) && !empty($survey_answer[0]->send_service_feedback_email_date);

                echo '<p>';
                if (!$product_feedback_is_sent) {
                    echo '<a data-id="' . $order_id . '" class="send_comments_form button generate-items" href="#" style="margin-bottom: 10px;">' . __('Send Product Feedback Email', self::$customer_feedback_plugin_name) . '</a>';
                    echo '<span class="send_comments_form_applyke_wc_feedback_loader" style="margin-left: 5px;display:none;background: url(' . plugin_dir_url(__FILE__) . 'includes/images/ajax-loader.gif) no-repeat; height:11px; width:16px"></span>';
                    echo '<br>';
                } else {
                    echo __('Product feedback email has being sent on  ', self::$customer_feedback_plugin_name) . $survey_answer[0]->send_comments_form_date . '<br>';
                }
                if (!$service_feedback_is_sent) {
                    echo '<a id="' . $order_id . '" class="send_survey_on_email_button button generate-items" href="#">' . __('Send Service Feedback Email', self::$customer_feedback_plugin_name) . '</a>';
                    echo '<span class="send_survey_on_email_applyke_wc_feedback_loader" style="margin-left: 5px;display:none;background: url(' . plugin_dir_url(__FILE__) . 'includes/images/ajax-loader.gif) no-repeat; height:11px; width:16px"></span>';
                } else {
                    echo __('Service feedback email has being sent on  ', self::$customer_feedback_plugin_name) . $survey_answer[0]->send_service_feedback_email_date . '<br>';
                    if (!empty($survey_answer[0]->main_answer)) {
                        echo '<a href="' . menu_page_url(self::$customer_feedback_plugin_name, false) . '">' . __('View answer', self::$customer_feedback_plugin_name) . '</a>';
                    }
                }
                echo '</p>';
                echo '</div>';
                return true;
            } else {
                return false;
            }
        }

        public static function send_survey_on_email()
        {
            global $post;
            if (isset($post) && current_user_can('administrator')) {
                $nonce_comments_form = wp_create_nonce(WOOCOMMERCE_CUSTOMER_FEEDBACK_COMMENTS_FORM_NONCE);
                $nonce_send_survey_on_email = wp_create_nonce(WOOCOMMERCE_CUSTOMER_FEEDBACK_SEND_SURVEY_NONCE);
                ?>
              <script type="text/javascript">
                jQuery(document).ready(function ($) {
                  $('.send_comments_form').click(function () {
                    var id = <?php echo (int)$post->ID;?>;
                    $(".send_comments_form_applyke_wc_feedback_loader").css('display', 'inline-block');
                    var hidden = $(this);
                    var data = {
                      action: 'comments_form',
                      customer_feedback_id: id,
                      nonce: "<?php echo $nonce_comments_form?>"
                    };
                    $.ajax({
                      type: 'post',
                      url: ajax_object.ajax_url,
                      data: data,
                      success: function (result) {
                        hidden.removeClass('visible').addClass('hidden');
                        $(".send_comments_form_applyke_wc_feedback_loader").replaceWith(result.text + '<br>');
                      }
                    });
                    return false;
                  });

                  $('.send_survey_on_email_button').click(function () {
                    var id = <?php echo (int)$post->ID;?>;
                    $(".send_survey_on_email_applyke_wc_feedback_loader").css('display', 'inline-block');
                    var hidden = $(this);
                    var data = {
                      action: 'service_feedback_action',
                      customer_feedback_id: id,
                      nonce: "<?php echo $nonce_send_survey_on_email?>"
                    };
                    $.ajax({
                      type: 'post',
                      url: ajax_object.ajax_url,
                      data: data,
                      success: function (result) {
                        hidden.removeClass('visible').addClass('hidden');
                        $(".send_survey_on_email_applyke_wc_feedback_loader").replaceWith(result.text + '<br>');
                      }
                    });
                    return false;
                  });
                });
              </script>
            <?php }
        }

        public static function comments_form()
        {
            global $wpdb, $post;

            check_ajax_referer(WOOCOMMERCE_CUSTOMER_FEEDBACK_COMMENTS_FORM_NONCE, 'nonce');

            apply_filters('wp_mail_from_name', get_option('woocommerce_email_from_name'));
            if (isset($_POST['customer_feedback_id'])) {
                $order = new WC_Order((int)$_POST['customer_feedback_id']);
            } else {
                $order = new WC_Order($post->ID);
            }

            $to_email = $order->get_billing_email();
            $headers = "Content-Type: text/html\r\n";
            $content = wc_get_template_html('woocommerce-customer-feedback-comments-form.php', array(
                'order' => $order->get_id(),
                'email_heading' => "Content-Type: text/html  charset=UTF-8\r\n",
                'sent_to_admin' => false,
                'plain_text' => false
            ), WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_DIR . 'emails/', WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_DIR . 'emails/');

            $table_name = $wpdb->prefix . "applyke_wc_feedback_survey";
            $orderId = (int)$order->get_id();
            $saved_order = $wpdb->get_results(
                "SELECT * FROM  {$table_name}  WHERE  {$table_name}.`order_id`= {$orderId} LIMIT 1");

            $date = current_time('mysql');
            $send_email = false;
            if (empty($saved_order)) {
                $wpdb->insert($wpdb->prefix . "applyke_wc_feedback_survey", array(
                    'order_id' => (int)$order->get_id(),
                    'send_comments_form_date' => $date
                ));
                $send_email = true;
            } else {
                if (!$saved_order[0]->send_comments_form_date) {
                    $wpdb->update($wpdb->prefix . "applyke_wc_feedback_survey", array(
                        'send_comments_form_date' => $date
                    ), array('order_id' => (int)$order->get_id()));
                    $send_email = true;
                } else {
                    $date = $saved_order[0]->send_comments_form_date;
                }
            }

            if ($send_email) {
                $mail = new WC_Email();
                $mail->style_inline($content);
                $mail->send($to_email, WOOCOMMERCE_CUSTOMER_FEEDBACK__SITE_NAME . '. ' . __('Leave a review about the purchased goods', self::$customer_feedback_plugin_name), $content, $headers, '');
            }
            if (self::isAjax()) {
                wp_send_json(array('text' => __('Product feedback email has being sent on  ', self::$customer_feedback_plugin_name) . $date));
            }
        }


        public static function hide_title()
        {
            global $post;
            if (isset($post) && $post->post_name == 'customer-feedback') {
                wp_enqueue_script('hide-title', plugin_dir_url(__FILE__) . 'includes/js/hide_title.js');
            }
        }


        public static function customer_feedback_custom_submenu_page_callback($content)
        {
            if (is_admin()) {
                require_once(dirname(__FILE__) . '/admin/class_woocommerce_customer_feedback_admin.php');
                $admin_page = new Woocommerce_Customer_Feedback_Admin;
                $admin_page->add_dashboard_page();
            }
            return $content;
        }

        public static function embed_image_in_mail()
        {
            global $phpmailer;
            $body = $phpmailer->Body;
            $uploadDir = WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_DIR;
            $baseUploadUrl = WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_URL;
            $baseUploadPath = $uploadDir;

            preg_match_all('/<img.*?>/', $body, $matches);
            if (!isset($matches[0])) {
                return;
            }
            $i = 1;
            foreach ($matches[0] as $img) {
                $id = 'img' . ($i++);
                preg_match('/src=\'(.*?)\'/', $img, $m);
                if (empty($m)) {
                    preg_match('/src="(.*?)"/', $img, $m);
                }
                if (!isset($m[1])) {
                    continue;
                }
                if (strpos($m[1], get_bloginfo('url')) === false) {
                    continue;
                }

                $uploadPathSegment = str_ireplace($baseUploadUrl, '', $m[1]);
                $uploadPathSegment = rtrim($uploadPathSegment, '/');
                $completeImagePath = $baseUploadPath . $uploadPathSegment;

                $phpmailer->AddEmbeddedImage($completeImagePath, $id, 'attachment', 'base64');
                $body = str_replace($img, '<img class="prodImage" alt="Product Image" src="cid:' . $id . '" style="height:100px; width:100px;" />', $body);
            }
            $phpmailer->Body = $body;
        }

        public static function from_mail_name()
        {
            return get_option('woocommerce_email_from_name');
        }

        public static function customer_feedback_add_javascript()
        {
            wp_enqueue_script('jquery');
            wp_enqueue_script('rating', plugin_dir_url(__FILE__) . 'includes/js/rating.js');
            wp_enqueue_script('woocommerce-customer-feedback-script', plugin_dir_url(__FILE__) . 'includes/js/script.js');
            wp_localize_script('woocommerce-customer-feedback-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
        }

        public static function load_scripts()
        {
            wp_enqueue_style('woocommerce-customer-feedback-css', plugin_dir_url(__FILE__) . 'includes/css/woocommerce-customer-feedback.css');
            wp_enqueue_style('rating-css', plugin_dir_url(__FILE__) . 'includes/css/woocommerce-customer-feedback-rating.css');
        }


        public static function service_feedback_action()
        {

            check_ajax_referer(WOOCOMMERCE_CUSTOMER_FEEDBACK_SEND_SURVEY_NONCE, 'nonce');

            apply_filters('wp_mail_from_name', get_option('woocommerce_email_from_name'));
            if (isset($_POST['customer_feedback_id'])) {
                $order = new WC_Order((int)$_POST['customer_feedback_id']);
            } else {
                global $post;
                $order = new WC_Order($post->ID);
            }

            $to_email = $order->get_billing_email();
            $headers = "Content-Type: text/html\r\n";
            $content = wc_get_template_html('woocommerce-customer-feedback-content.php', array(
                'order' => $order->get_id(),
                'email_heading' => "Content-Type: text/html  charset=UTF-8\r\n",
                'sent_to_admin' => false,
                'plain_text' => false
            ), WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_DIR . 'emails/', WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_DIR . 'emails/');

            global $wpdb;
            $table_name = $wpdb->prefix . "applyke_wc_feedback_survey";
            $orderID = (int)$order->get_id();
            $saved_order = $wpdb->get_results(
                "SELECT * FROM  {$table_name}  WHERE  {$table_name}.`order_id`= {$orderID} LIMIT 1");

            $date = current_time('mysql');
            $send_email = false;
            if (empty($saved_order)) {
                $wpdb->insert($wpdb->prefix . "applyke_wc_feedback_survey", array(
                    'order_id' => (int)$order->get_id(),
                    'send_service_feedback_email_date' => $date
                ));
                $send_email = true;

            } else {
                if (!$saved_order[0]->send_service_feedback_email_date) {
                    $wpdb->update($wpdb->prefix . "applyke_wc_feedback_survey", array(
                        'send_service_feedback_email_date' => $date
                    ), array('order_id' => (int)$order->get_id()));
                    $send_email = true;
                } else {
                    $date = $saved_order[0]->send_service_feedback_email_date;
                }
            }

            if ($send_email) {
                $mail = new WC_Email();
                $mail->style_inline($content);
                $mail->send($to_email, __('You recently made a purchase in the store', self::$customer_feedback_plugin_name) . ' ' . WOOCOMMERCE_CUSTOMER_FEEDBACK__SITE_NAME . '. ' . __(' Estimate the level of service of our store', self::$customer_feedback_plugin_name), $content, $headers, '');
            }

            if (self::isAjax()) {
                wp_send_json(array('text' => __('Service feedback email has being sent on  ', self::$customer_feedback_plugin_name) . $date));
            }
        }

        public static function isAjax()
        {
            return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        }

        public static function plugin_activation()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "applyke_wc_feedback_survey";
            $result_table_name = $wpdb->prefix . "applyke_wc_feedback_survey_summ_value";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                $create_applyke_wc_feedback_survey_table = "CREATE TABLE IF NOT EXISTS`" . $wpdb->prefix . "applyke_wc_feedback_survey` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `client_ip` VARCHAR(50),
                `user_id` INT(20),
                `order_id` INT(20) NOT NULL,
                `main_answer` VARCHAR(50),
                `answer_choose_product` INT(20),
                `comment_choose_product` VARCHAR(250),
                `answer_manager_cons` INT(20),
                `comment_manager_cons` VARCHAR(250),
                `answer_product_delivery` INT(20),
                `comment_product_delivery` VARCHAR(250),
                `answer_payment_process` INT(20),
                `comment_payment_process` VARCHAR(250),
                `comment_all` TEXT,
                `send_comments_form_date` timestamp NULL DEFAULT NULL ,
                `send_service_feedback_email_date` timestamp NULL DEFAULT NULL ,
                `send_comments_form` BOOLEAN,
                 `created_at` timestamp NULL DEFAULT NULL,
                 PRIMARY KEY (`id`), 
                 INDEX main (`main_answer`), 
                 INDEX order_id (`order_id`) 
                 )  ENGINE=InnoDB DEFAULT CHARSET=utf8 ";

                dbDelta($create_applyke_wc_feedback_survey_table);
            }

            if ($wpdb->get_var("SHOW TABLES LIKE '$result_table_name'") != $result_table_name) {
                $create_applyke_wc_feedback_survey_table_summ = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "applyke_wc_feedback_survey_summ_value` (
                  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                  `summ_main_answer_good` float(20),
                  `summ_main_answer_middle` float(20),
                  `summ_main_answer_bad` float(20),
                  `summ_choose_product` float(20),
                  `summ_manager_cons` float(20),
                  `summ_product_delivery` float(20),
                  `summ_payment_process` float(20),
                  `created_at` timestamp NULL DEFAULT NULL,
                   PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 ";

                dbDelta($create_applyke_wc_feedback_survey_table_summ);
            }

            $new_page = array(
                'slug' => 'customer-feedback',
                'content' => '[woocommerce_customer_feedback]'
            );

            $page = get_page_by_path($new_page['slug']);
            if (!$page) {
                wp_insert_post(array(
                    'post_title' => __('Feedback service', self::$customer_feedback_plugin_name),
                    'post_type' => 'page',
                    'post_name' => $new_page['slug'],
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'post_content' => $new_page['content'],
                    'post_status' => 'publish',
                    'post_author' => 1,
                    'menu_order' => 0
                ));
            }
        }

        public static function plugin_deactivation()
        {
            $page = get_page_by_path('customer-feedback');
            if ($page) {
                wp_delete_post($page->ID, false);
            }
            remove_submenu_page('woocommerce', self::$customer_feedback_plugin_name);
            return true;
        }

        public static function woocommerce_customer_feedback_display()
        {
            require_once('emails/woocommerce-customer-feedback-content-page.php');
        }

        public static function woocommerce_customer_feedback_delete_plugin()
        {
            global $wpdb;
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}applyke_wc_feedback_survey");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}applyke_wc_feedback_survey_summ_value");
        }
    }

    new Woocommerce_Customer_Feedback_Service;
}

