<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('Woocommerce_Customer_Feedback_Admin')) {
    class Woocommerce_Customer_Feedback_Admin
    {
        public $dashboard_error_msg;
        public $customer_feedback_plugin_name = 'woocommerce_customer_feedback';

        public function __construct()
        {
            add_action('admin_enqueue_scripts', array($this, 'customer_feedback_add_script'));
        }

        public function add_dashboard_page()
        {
            add_submenu_page('woocommerce', __('Customer feedback', $this->customer_feedback_plugin_name), __('Customer feedback', $this->customer_feedback_plugin_name), 'manage_options', $this->customer_feedback_plugin_name, array($this, 'render_customer_feedback_list_page'));

        }

        public static function customer_feedback_add_script()
        {
            wp_enqueue_style('woocommerce-customer-feedback-css', WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_URL . 'includes/css/woocommerce-customer-feedback.css');

            wp_enqueue_script('jquery');
            wp_enqueue_script('rating', WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_URL . 'includes/js/rating.js');
            wp_enqueue_script('woocommerce-customer-feedback-script', WOOCOMMERCE_CUSTOMER_FEEDBACK__PLUGIN_URL . 'includes/js/script.js');
            wp_localize_script('woocommerce-customer-feedback-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));

        }

        public function render_customer_feedback_list_page()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "applyke_wc_feedback_survey";
            $table_total_values = $wpdb->prefix . "applyke_wc_feedback_survey_summ_value";

            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                $survey_answers = $wpdb->get_results("SELECT * FROM {$table_name}  ORDER BY {$table_name}.`created_at` DESC ");
                $survey_total_values = $wpdb->get_results("SELECT * FROM {$table_total_values}  ORDER BY {$table_total_values}.`created_at` DESC LIMIT 1 ");
            }

            echo '<div class="wrap"><h1 class="wp-heading-inline">' . __('Customer feedback', $this->customer_feedback_plugin_name) . '</h1>';
            if (!empty($survey_total_values)) {
                $total = $survey_total_values[0]; ?>
                <p><?php echo __('Percent of \'good\'', $this->customer_feedback_plugin_name) . ':  ' . $total->summ_main_answer_good . ' %' ?></p>
                <p><?php echo __('Percent of \'middle\'', $this->customer_feedback_plugin_name) . ':  ' . $total->summ_main_answer_middle . ' %' ?></p>
                <p><?php echo __('Percent of \'bad\'', $this->customer_feedback_plugin_name) . ':  ' . $total->summ_main_answer_bad . ' %' ?></p>
                <b><?php _e('Average values of indicators for additional issues', $this->customer_feedback_plugin_name) ?> </b>
                <p><?php echo __('Convenience of choosing product', $this->customer_feedback_plugin_name) . ':  ' . $total->summ_choose_product . __(' of 5', $this->customer_feedback_plugin_name) ?></p>
                <p><?php echo __('Manager consultation', $this->customer_feedback_plugin_name) . ':  ' . $total->summ_manager_cons . __(' of 5', $this->customer_feedback_plugin_name) ?></p>
                <p><?php echo __('Product delivery', $this->customer_feedback_plugin_name) . ':  ' . $total->summ_product_delivery . __(' of 5', $this->customer_feedback_plugin_name) ?></p>
                <p><?php echo __('Payment process', $this->customer_feedback_plugin_name) . ':  ' . $total->summ_payment_process . __(' of 5', $this->customer_feedback_plugin_name) ?></p>
                <?php
            }
            if (!empty($survey_answers)) {
                $apkAdminListTable = new Woocommerce_Customer_Feedback_Admin_Page();
                $apkAdminListTable->prepare_items(); ?>
                <form id="events-filter" method="get">
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
                    <?php $apkAdminListTable->display(); ?>
                </form>
                <?php
                echo '</div>';
            } else {
                _e('No answers', $this->customer_feedback_plugin_name);
            }
        }
    }
}

if (!class_exists('WP_List_Table')) require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

class Woocommerce_Customer_Feedback_Admin_Page extends WP_List_Table
{
    public $customer_feedback_plugin_name = 'woocommerce_customer_feedback';

    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'id' => 'id',
            'client_ip' => __('Client ip', $this->customer_feedback_plugin_name),
            'user_id' => __('Customer', $this->customer_feedback_plugin_name),
            'order_id' => __('Order №', $this->customer_feedback_plugin_name),
            'main_answer' => __('General Answer', $this->customer_feedback_plugin_name),
            'answer_choose_product' => __('Convenience of choosing product', $this->customer_feedback_plugin_name),
            'comment_choose_product' => __('Convenience of choosing product', $this->customer_feedback_plugin_name) . '<br>' . __('Review', $this->customer_feedback_plugin_name),
            'answer_manager_cons' => __('Manager consultation', $this->customer_feedback_plugin_name),
            'comment_manager_cons' => __('Manager consultation', $this->customer_feedback_plugin_name) . '<br>' . __('Review', $this->customer_feedback_plugin_name),
            'answer_product_delivery' => __('Product delivery', $this->customer_feedback_plugin_name),
            'comment_product_delivery' => __('Product delivery', $this->customer_feedback_plugin_name) . '<br>' . __('Review', $this->customer_feedback_plugin_name),
            'answer_payment_process' => __('Payment process', $this->customer_feedback_plugin_name),
            'comment_payment_process' => __('Payment process', $this->customer_feedback_plugin_name) . '<br>' . __('Review', $this->customer_feedback_plugin_name),
            'comment_all' => __('Comment', $this->customer_feedback_plugin_name),
            'send_comments_form_date' => __('send_comments_form_date', $this->customer_feedback_plugin_name),
            'send_service_feedback_email_date' => __('send_service_feedback_email_date', $this->customer_feedback_plugin_name),
            'send_comments_form' => __('send_comments_form', $this->customer_feedback_plugin_name),
            'created_at' => __('Date', $this->customer_feedback_plugin_name),
        );
        return $columns;
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="book[]" value="%s" />', $item['id']
        );
    }

    public function get_bulk_actions()
    {
        $actions = array(
            'delete' => __('Delete', $this->customer_feedback_plugin_name)
        );
        return $actions;
    }

    public function process_bulk_action()
    {
        if ('delete' === $this->current_action() && current_user_can('administrator')) {
            global $wpdb;
            $table_name = $wpdb->prefix . "applyke_wc_feedback_survey";
            if (isset($_GET['book'])) {
                if (isset($_POST['_wpnonce']) && !empty($_POST['_wpnonce'])) {
                    $nonce = filter_input(INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING);
                    $action = 'bulk-' . $this->_args['plural'];
                    if (!wp_verify_nonce($nonce, $action))
                        wp_die('Nope! Security check failed!');
                }
                $idsToDelete = implode(',', array_values($_GET['book']));
                $sql = "DELETE FROM {$table_name} WHERE {$table_name}.`id` IN ({$idsToDelete})";
                $wpdb->query($sql);
                $service_object = new Woocommerce_Customer_Feedback_Service();
                $service_object::update_average_values();

                $redirect = admin_url('admin.php?page=' . $this->customer_feedback_plugin_name);
                wp_redirect($redirect);
                exit;
            }
        }
    }


    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'user_id':
                if ($item[$column_name] == 0) {
                    return __('anonymous', $this->customer_feedback_plugin_name);
                } else {
                    $user_info = get_userdata($item[$column_name]);
                    return '<a href="' . admin_url('user-edit.php?user_id=' . $item[$column_name], 'http') . '">
                       ' . $user_info->user_email . '<br></a>';
                }
                break;
            case 'order_id':
                return '<a href="' . admin_url('post.php?post=' . $item[$column_name] . '&action=edit') . '"> ' . $item[$column_name] . '</a>';
                break;
            case 'answer_choose_product':
            case 'answer_manager_cons':
            case 'answer_product_delivery':
            case 'answer_payment_process':
                return $this->display_item($item[$column_name]);
                break;
            case 'id':
            case 'comment_payment_process':
            case 'comment_all':
            case 'created_at':
            case 'main_answer':
            case 'comment_choose_product':
            case 'comment_manager_cons':
            case 'comment_product_delivery':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    public function display_item($item)
    {
        switch ($item) {
            case '1':
            case '2':
                return '<span class="item-red">' . $item . __(' of 5', $this->customer_feedback_plugin_name) . '</span>';
                break;
            case '3':
                return '<span  class="item-yellow">' . $item . __(' of 5', $this->customer_feedback_plugin_name) . '</span>';
                break;
            case '4':
                return '<span  class="item-blue">' . $item . __(' of 5', $this->customer_feedback_plugin_name) . '</span>';
                break;
            case '5':
                return '<span  class="item-green">' . $item . __(' of 5', $this->customer_feedback_plugin_name) . '</span>';
                break;
        }
        return false;
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $this->_column_headers = array($columns, $hidden);
        global $wpdb;
        $table_name = $wpdb->prefix . "applyke_wc_feedback_survey";
        $query = "SELECT * FROM {$table_name} WHERE {$table_name}.`main_answer` IS NOT NULL  ORDER BY {$table_name}.`created_at` DESC ";

        $totalitems = $wpdb->query($query);
        $perpage = 15;

        $totalpages = ceil($totalitems / $perpage);
        $data = $wpdb->get_results($query, ARRAY_A);

        $paged = !empty($_GET["paged"]) ? ($_GET["paged"]) : '';
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        $current_page = $this->get_pagenum();

        $this->process_bulk_action();
        $data = array_slice($data, (($current_page - 1) * $perpage), $perpage);

        $this->items = $data;
        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ));

    }

    public function get_hidden_columns()
    {
        return array(
            'client_ip',
            'send_comments_form_date',
            'send_service_feedback_email_date',
            'send_comments_form'
        );
    }

}