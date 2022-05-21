<?php
if (!defined('ABSPATH')) {
    die;
}

global $empemail_applicants_handle;
if (!class_exists('jobsearch_empemail_applicants_handle')) {

    class jobsearch_empemail_applicants_handle
    {

        // hook things up
        public function __construct()
        {
            global $empemail_applicants_handle;
            add_action('wp_ajax_jobsearch_empdash_load_single_eapswith_job_inlist', array($this, 'load_single_apswith_job_inlist'));
            //
            add_action('wp_ajax_jobsearch_empdash_load_email_apswith_job_posts', array($this, 'load_all_jobs_post_data'));
            add_action('wp_ajax_jobsearch_empdash_load_email_apswith_job_apps', array($this, 'load_more_apswith_job_apps'));
            add_action('wp_ajax_jobsearch_empdash_load_email_apswith_apps_lis', array($this, 'load_more_apswith_apps_lis'));
            //
            add_action('wp_ajax_jobsearch_empdash_emailjobs_apps_count_loadboxes', array($this, 'alljobs_apps_count_loadboxes'));
            
            add_action('init', array($this, 'emailjobs_apps_export_report'));
        }

        public function applicants_list()
        {

            global $jobsearch_plugin_options;

            $page_id = $user_dashboard_page = isset($jobsearch_plugin_options['user-dashboard-template-page']) ? $jobsearch_plugin_options['user-dashboard-template-page'] : '';
            $page_id = $user_dashboard_page = jobsearch__get_post_id($user_dashboard_page, 'page');
            $page_url = jobsearch_wpml_lang_page_permalink($page_id, 'page');

            $user_id = get_current_user_id();
            $user_id = apply_filters('jobsearch_in_fromdash_all_emailjobaplics_user_id', $user_id);
            $employer_id = jobsearch_get_user_employer_id($user_id);

            if ($employer_id > 0) {
                $args = array(
                    'post_type' => 'job',
                    'posts_per_page' => 5,
                    'post_status' => array('publish', 'draft'),
                    'fields' => 'ids',
                    'order' => 'DESC',
                    'orderby' => 'ID',
                    'meta_query' => array(
                        array(
                            'key' => 'jobsearch_field_job_posted_by',
                            'value' => $employer_id,
                            'compare' => '=',
                        ),
                        array(
                            'key' => 'jobsearch_job_emailapps_list',
                            'value' => '',
                            'compare' => '!=',
                        ),
                    ),
                );
                $filter_from_date = isset($_GET['alljobs_wafrom_date']) ? $_GET['alljobs_wafrom_date'] : '';
                $filter_to_date = isset($_GET['alljobs_wato_date']) ? $_GET['alljobs_wato_date'] : '';
                
                if ($filter_from_date != '') {
                    $filter_from_date_str = strtotime($filter_from_date);
                    $args['meta_query'][] = array(
                        'key' => 'jobsearch_field_job_publish_date',
                        'value' => $filter_from_date_str,
                        'compare' => '>=',
                    );
                }
                if ($filter_to_date != '') {
                    $filter_to_date_str = strtotime($filter_to_date);
                    $args['meta_query'][] = array(
                        'key' => 'jobsearch_field_job_publish_date',
                        'value' => $filter_to_date_str,
                        'compare' => '<=',
                    );
                }
                
                $get_job_id = isset($_GET['job_id']) ? $_GET['job_id'] : '';
                if ($get_job_id > 0 && get_post_type($get_job_id) == 'job') {
                    $args['post__in'] = array($get_job_id);
                }
                $jobs_query = new WP_Query($args);
                $totl_found_jobs = $jobs_query->found_posts;
                $jobs_posts = $jobs_query->posts;
                ?>

                <div class="jobsearch-typo-wrap">
                    <script>
                        jQuery(document).ready(function () {
                            jobsearch_alljobs_apps_count_load_email();
                            
                            jQuery('.aplics-mnemp-filterbtn').on('click', function() {
                                var this_prnet = jQuery(this).parents('.allapps-monthemp-filtrmain');
                                this_prnet.find('.filtr-loder').removeAttr('style').html('<i class="fa fa-refresh fa-spin"></i>');
                                this_prnet.find('form').submit();
                            });
                            
                            var todayDate = new Date().getDate();
                            jQuery('input[name="alljobs_wafrom_date"]').datetimepicker({
                                maxDate: new Date(new Date().setDate(todayDate)),
                                timepicker: false,
                                format: 'd-m-Y',
                                onSelectDate: function (ct, $i) {

                                }
                            });
                            jQuery('input[name="alljobs_wato_date"]').datetimepicker({
                                maxDate: new Date(new Date().setDate(todayDate)),
                                timepicker: false,
                                format: 'd-m-Y',
                                onSelectDate: function (ct, $i) {

                                }
                            });
                            //
                            jQuery('.allplics-export-excel').on('click', function() {
                                //jQuery(this).parents('.allapps-monthemp-filtrmain').find('.exprt-loder').html('<i class="fa fa-refresh fa-spin"></i>');
                                jQuery('#jobsearch-empmonth-reports-form').submit();
                            });
                        });
                    </script>
                    <div class="jobsearch-employer-box-section">
                        <div class="jobsearch-profile-title">
                            <h2><?php esc_html_e('Applied by Email Applicants', 'wp-jobsearch') ?></h2>
                            <a href="<?php echo add_query_arg(array('tab' => 'all-applicants'), $page_url) ?>"
                               class="applicnts-view-btn"><?php esc_html_e('Internal Applicants', 'wp-jobsearch') ?></a>
                        </div>
                        <div class="jobsearch-allaplicants-holder jobsearch-emailaplicants-holder" data-uid="<?php echo($user_id) ?>" data-eid="<?php echo($employer_id) ?>">

                            <div class="select-appsjob-con">
                                <div class="filter-allreps-mainholdr">
                                    <div class="allapps-selctcounts-holdr">
                                        <div class="allapps-job-label">
                                            <h2><?php esc_html_e('Filter by Job', 'wp-jobsearch') ?></h2>
                                        </div>
                                        <div class="allapps-jobselct-con"
                                             style="display: inline-block; position: relative;">
                                            <?php
                                            $job_selcted_by = '';
                                            self::get_custom_post_field($job_selcted_by, 'job', esc_html__('Jobs', 'wp-jobsearch'), 'email_jobs_wapps_selctor');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="allapps-monthemp-filtrmain">
                                        <form method="get" autocomplete="off">
                                            <input type="hidden" name="tab" value="all-applicants">
                                            <input type="hidden" name="view" value="email-applicants">
                                            <div class="repapps-filtr-fieldscon">
                                                <div class="allapps-mnthefiltr-date date-from">
                                                    <label><?php esc_html_e('From Date', 'wp-jobsearch') ?></label>
                                                    <input type="text" name="alljobs_wafrom_date" value="<?php echo ($filter_from_date) ?>">
                                                </div>
                                                <div class="allapps-mnthefiltr-date date-to">
                                                    <label><?php esc_html_e('To Date', 'wp-jobsearch') ?></label>
                                                    <input type="text" name="alljobs_wato_date" value="<?php echo ($filter_to_date) ?>">
                                                </div>
                                            </div>
                                            <div class="filtr-mnth-btns-sec">
                                                <a href="javascript:void(0);" class="button aplics-mnemp-filterbtn"><?php esc_html_e('Submit', 'wp-jobsearch') ?></a>
                                                <span class="filtr-loder" style="display:none;"></span>
                                                <a href="javascript:void(0);" class="button allplics-export-excel"><?php esc_html_e('Export to Excel', 'wp-jobsearch') ?></a>
                                                <span class="exprt-loder" style="display:none;"></span>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="overall-appcreds-con">
                                    <ul>
                                        <li>
                                            <span class="tot-apps"><?php esc_html_e('Total Applicants ', 'wp-jobsearch') ?></span>
                                            <div class="applicnt-count-box tot-apps"><a class="overall-site-aplicnts">0</a></div>
                                        </li>
                                    </ul>
                                </div>
                                <form id="jobsearch-empmonth-reports-form" method="post" autocomplete="off">
                                    <input type="hidden" name="date_from" value="<?php echo ($filter_from_date) ?>">
                                    <input type="hidden" name="date_to" value="<?php echo ($filter_to_date) ?>">
                                    <?php wp_nonce_field('jobsearch_export_emailapsrept') ?>
                                    <input type="hidden" name="jobsearch_empmnth_frntemailapps_excel_export" value="1">
                                </form>
                            </div>
                            <?php
                            if (!empty($jobs_posts)) {
                                ?>
                                <div class="jobsearch-all-aplicantslst">
                                    <?php
                                    self::load_wapp_jobs_posts($jobs_posts, $employer_id);
                                    ?>
                                </div>
                                <?php
                                if ($totl_found_jobs > 5) {
                                    $total_pages = ceil($totl_found_jobs / 5);
                                    ?>
                                    <div class="lodmoreemil-apps-btnsec">
                                        <a href="javascript:void(0);" class="lodmoreemil-apps-btn"
                                           data-tpages="<?php echo($total_pages) ?>"
                                           data-gtopage="2"><?php esc_html_e('Load More Jobs', 'wp-jobsearch') ?></a>
                                    </div>
                                    <?php
                                }
                            } else {
                                ?>
                                <p><?php esc_html_e('No job found with applicants.', 'wp-jobsearch') ?></p>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <?php
            } else {
                ?>
                <div class="jobsearch-employer-dasboard jobsearch-typo-wrap">
                    <div class="jobsearch-employer-box-section">
                        <div class="jobsearch-profile-title">
                            <h2><?php esc_html_e('Applied by Email Applicants', 'wp-jobsearch') ?></h2>
                        </div>
                        <p><?php esc_html_e('No Applicants found.', 'wp-jobsearch') ?></p>
                    </div>
                </div>
                <?php
            }
        }
        
        public function emailjobs_apps_export_report() {
            if (isset($_POST['jobsearch_empmnth_frntemailapps_excel_export'])) {
                
                $from_date = isset($_POST['date_from']) ? $_POST['date_from'] : '';
                $to_date = isset($_POST['date_to']) ? $_POST['date_to'] : '';

                $user_id = get_current_user_id();
                $user_id = apply_filters('jobsearch_in_fromdash_alljobaplics_user_id', $user_id);
                $employer_id = jobsearch_get_user_employer_id($user_id);
                
                if ($from_date != '') {
                    $from_date = strtotime($from_date);
                }
                if ($to_date != '') {
                    $to_date = strtotime($to_date);
                }
                
                $args = array(
                    'post_type' => 'job',
                    'posts_per_page' => -1,
                    'post_status' => array('publish', 'draft'),
                    'fields' => 'ids',
                    'order' => 'DESC',
                    'orderby' => 'ID',
                    'meta_query' => array(
                        array(
                            'key' => 'jobsearch_field_job_posted_by',
                            'value' => $employer_id,
                            'compare' => '=',
                        ),
                        array(
                            'key' => 'jobsearch_job_emailapps_list',
                            'value' => '',
                            'compare' => '!=',
                        ),
                    ),
                );
                
                if ($from_date != '') {
                    $args['meta_query'][] = array(
                        'key' => 'jobsearch_field_job_publish_date',
                        'value' => $from_date,
                        'compare' => '>=',
                    );
                }
                if ($to_date != '') {
                    $args['meta_query'][] = array(
                        'key' => 'jobsearch_field_job_publish_date',
                        'value' => $to_date,
                        'compare' => '<=',
                    );
                }
                
                $jobs_query = new WP_Query($args);
                $totl_found_jobs = $jobs_query->found_posts;
                $job_posts = $jobs_query->posts;
                
                if (!empty($job_posts)) {
                    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
                    header("Content-Disposition: attachment; filename=applicants-report-" . time() . ".xlsx");
                    header('Cache-Control: max-age=0');

                    header('Cache-Control: max-age=1');

                    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
                    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
                    header('Pragma: public');
                    //
                    include_once("xlsxwriter.class.php");
                    
                    $header = array(
                        'Job Title' => 'string', //text
                        'Employer' => 'string', //text
                        'Employer Email' => 'string', //text
                        'Applicant Name' => 'string',
                        'User Email' => 'string',
                        'Phone' => 'string',
                        'Age' => 'string',
                        'Salary' => 'string',
                        'Apply Date' => 'string',
                    );
                    $rows = array();
                    
                    foreach ($job_posts as $job_id) {
                        
                        $job_applicants_list = get_post_meta($job_id, 'jobsearch_job_emailapps_list', true);

                        $employer_id = get_post_meta($job_id, 'jobsearch_field_job_posted_by', true);
                        $employer_name = get_the_title($employer_id);

                        if (!empty($job_applicants_list)) {
                            foreach ($job_applicants_list as $apply_data) {

                                $app_id = isset($apply_data['app_id']) ? $apply_data['app_id'] : '';
                                $emp_email = isset($apply_data['email']) ? $apply_data['email'] : '';
                                $user_email = isset($apply_data['user_email']) ? $apply_data['user_email'] : '';

                                $obj_email_app = get_post($app_id);
                                $aply_date_time = '';
                                if (isset($obj_email_app->post_date)) {
                                    $email_app_date = $obj_email_app->post_date;
                                    $aply_date_time = strtotime($email_app_date);
                                }
                                if ($aply_date_time != '') {
                                    $aply_date_time = date(get_option('date_format'), $aply_date_time);
                                }
                                
                                $candidate_id = '';
                                if (email_exists($user_email)) {
                                    $_user_obj = get_user_by('email', $user_email);
                                    $_user_id = isset($_user_obj->ID) ? $_user_obj->ID : '';
                                    $candidate_id = jobsearch_get_user_candidate_id($_user_id);
                                }

                                $first_name = isset($apply_data['username']) ? $apply_data['username'] : '';
                                $last_name = isset($apply_data['user_surname']) ? $apply_data['user_surname'] : '';
                                $candidate_phone = isset($apply_data['user_phone']) ? $apply_data['user_phone'] : '';
                                $candidate_salary = isset($apply_data['current_salary']) ? $apply_data['current_salary'] : '';
                                $candidate_age = '-';

                                if ($first_name != '') {
                                    $aplic_name = $first_name;
                                    if ($last_name != '') {
                                        $aplic_name .= ' ' . $last_name;
                                    }
                                }

                                if ($candidate_id > 0) {
                                    $aplic_name = get_the_title($candidate_id);
                                    $candidate_user_id = jobsearch_get_candidate_user_id($candidate_id);
                                    $user_obj = get_user_by('id', $candidate_user_id);
                                    $user_email = isset($user_obj->user_email) ? $user_obj->user_email : '';

                                    if ($candidate_salary == '') {
                                        $candidate_salary = jobsearch_candidate_current_salary($candidate_id);
                                        $candidate_salary = $candidate_salary != '' ? $candidate_salary : '-';
                                    }

                                    $candidate_age = jobsearch_candidate_age($candidate_id);
                                    $candidate_age = $candidate_age != '' ? $candidate_age : '-';

                                    if ($candidate_phone == '') {
                                        $candidate_phone = get_post_meta($candidate_id, 'jobsearch_field_user_phone', true);
                                        $candidate_phone = $candidate_phone != '' ? $candidate_phone : '-';
                                    }
                                }

                                $rows[] = array(
                                    get_the_title($job_id),
                                    $employer_name,
                                    $emp_email,
                                    $aplic_name,
                                    $user_email,
                                    $candidate_phone,
                                    $candidate_age,
                                    $candidate_salary,
                                    $aply_date_time,
                                );
                            }
                        }
                    }
                    $writer = new XLSXWriter();
                    $writer->writeSheetHeader('Sheet1', $header);
                    foreach ($rows as $row) {
                        $writer->writeSheetRow('Sheet1', $row);
                    }
                    echo $writer->writeToString();
                    exit();
                }
            }
        }

        public static function get_custom_post_field($selected_id, $custom_post_slug, $field_label, $field_name, $custom_name = '')
        {
            global $jobsearch_form_fields;
            $custom_post_first_element = esc_html__('All ', 'wp-jobsearch');
            $custom_posts = array(
                '' => $custom_post_first_element . $field_label,
            );
            if ($selected_id) {
                $this_custom_posts = get_the_title($selected_id);
                $custom_posts[$selected_id] = $this_custom_posts;
            }

            $rand_num = rand(1234568, 6867867);
            $field_params = array(
                'classes' => 'job_postemil_cajax_field',
                'id' => 'custom_post_field_' . $rand_num,
                'name' => $field_name,
                'cus_name' => $field_name,
                'options' => $custom_posts,
                'force_std' => $selected_id,
                'ext_attr' => ' data-randid="' . $rand_num . '" data-forcestd="' . $selected_id . '" data-loaded="false" data-posttype="' . $custom_post_slug . '"',
            );
            if (isset($custom_name) && $custom_name != '') {
                $field_params['cus_name'] = $custom_name;
            }
            echo '<div class="jobsearch-profile-select">';
            $jobsearch_form_fields->select_field($field_params);
            echo '</div>';
            ?>
            <span class="jobsearch-field-loader custom_post_loader_<?php echo absint($rand_num); ?>"></span>
            <?php
        }

        public static function list_job_all_apps($_job_id, $employer_id, $apps_start = 0)
        {
            global $jobsearch_plugin_options, $jobsearch_uploding_resume;

            $jobsearch_uploding_resume = true;

            $job_cver_ltrs = get_post_meta($_job_id, 'jobsearch_job_apply_cvrs', true);

            $job_applicants_list = get_post_meta($_job_id, 'jobsearch_job_emailapps_list', true);
            arsort($job_applicants_list);

            if (empty($job_applicants_list)) {
                $job_applicants_list = array();
            }

            //
            $apps_offset = 6;
            if ($apps_start > 0) {
                $apps_start = ($apps_start - 1) * ($apps_offset);
            }
            $job_applicants_list = array_slice($job_applicants_list, $apps_start, $apps_offset);

            if (!empty($job_applicants_list)) {
                foreach ($job_applicants_list as $apply_data) {
                    $app_id = isset($apply_data['app_id']) ? $apply_data['app_id'] : '';
                    $app_obj = get_post($app_id);
                    $app_post_date = isset($app_obj->post_date) ? $app_obj->post_date : '';
                    $job_id = isset($apply_data['id']) ? $apply_data['id'] : '';
                    $email = isset($apply_data['email']) ? $apply_data['email'] : '';
                    $user_email = isset($apply_data['user_email']) ? $apply_data['user_email'] : '';

                    //
                    $first_name = isset($apply_data['username']) ? $apply_data['username'] : '';
                    $last_name = isset($apply_data['user_surname']) ? $apply_data['user_surname'] : '';
                    $user_phone = isset($apply_data['user_phone']) ? $apply_data['user_phone'] : '';
                    $user_msg = isset($apply_data['user_msg']) ? $apply_data['user_msg'] : '';
                    $job_title = isset($apply_data['job_title']) ? $apply_data['job_title'] : '';
                    $current_salary = isset($apply_data['current_salary']) ? $apply_data['current_salary'] : '';
                    $att_file_path = isset($apply_data['att_file_path']) ? $apply_data['att_file_path'] : '';
                    $att_file_args = isset($apply_data['att_file_args']) ? $apply_data['att_file_args'] : '';

                    $current_salary = jobsearch_get_price_format($current_salary, '', 'candidate');

                    $user_def_avatar_url = get_avatar_url($user_email, array('size' => 69));
                    $_candidate_id = '';
                    $user_page_url = 'javascript:void(0);';
                    $candidate_title = $first_name . ' ' . $last_name;

                    if (email_exists($user_email)) {
                        $_user_obj = get_user_by('email', $user_email);
                        $_user_id = isset($_user_obj->ID) ? $_user_obj->ID : '';
                        if (jobsearch_user_is_candidate($_user_id)) {
                            $_candidate_id = jobsearch_get_user_candidate_id($_user_id);
                            $user_page_url = get_permalink($_candidate_id);
                            $candidate_title = get_the_title($_candidate_id);

                        }
                    }
                    $user_def_avatar_url = jobsearch_candidate_img_url_comn($_candidate_id);

                    $_rand_id = rand(1000000, 9999999);
                    ?>
                    <li class="jobsearch-column-12">
                        <script>
                            jQuery(document).on('click', '.jobsearch-modelcvrltr-btn-<?php echo($_rand_id) ?>', function () {
                                jobsearch_modal_popup_open('JobSearchCandCovershwModal<?php echo($_rand_id) ?>');
                            });
                        </script>
                        <div class="jobsearch-applied-jobs-wrap">


                            <a href="<?php echo($user_page_url) ?>" class="jobsearch-applied-jobs-thumb">
                                <img src="<?php echo($user_def_avatar_url) ?>" alt="">
                            </a>
                            <div class="jobsearch-applied-jobs-text">
                                <div class="jobsearch-applied-jobs-left">
                                    <h2 class="jobsearch-pst-title">
                                        <a href="<?php echo($user_page_url) ?>"><?php echo($candidate_title) ?></a>
                                        <?php
                                        if ($user_phone != '') { ?>
                                            <small>
                                                <a href="tel:<?php echo($user_phone) ?>"><?php printf(esc_html__('Phone: %s', 'wp-jobsearch'), $user_phone) ?></a>
                                            </small>
                                        <?php } ?>
                                    </h2>
                                    <?php if ($job_title != '') { ?>
                                        <span> <?php echo($job_title) ?></span>
                                    <?php } ?>
                                    <ul>
                                        <?php
                                        if ($app_post_date != '') {
                                            $aply_date_time = strtotime($app_post_date);
                                            ?>
                                            <li>
                                                <i class="jobsearch-icon jobsearch-calendar"></i> <?php printf(esc_html__('Applied at: %s', 'wp-jobsearch'), (date_i18n(get_option('date_format'), $aply_date_time) . ' ' . date_i18n(get_option('time_format'), $aply_date_time))) ?>
                                            </li>
                                            <?php
                                        }
                                        if ($current_salary != '') { ?>
                                            <li>
                                                <i class="fa fa-money"></i> <?php printf(esc_html__('Salary: %s', 'wp-jobsearch'), $current_salary) ?>
                                            </li>
                                            <?php
                                        }
                                        if ($user_email != '') {
                                            ?>
                                            <li><i class="fa fa-envelope"></i> <a
                                                        href="mailto:<?php echo($user_email) ?>"><?php printf(esc_html__('Email: %s', 'wp-jobsearch'), $user_email) ?></a>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                    </ul>
                                </div>
                                <div class="jobsearch-applied-job-btns">
                                    <ul>
                                        <li><a href="javascript:void(0);"
                                               class="preview-candidate-profile jobsearch-remove-emailaplicnt"
                                               data-id="<?php echo($job_id) ?>" data-email="<?php echo($user_email) ?>"><i
                                                        class="jobsearch-icon jobsearch-rubbish"></i></a></li>
                                        <?php
                                        if ($_candidate_id > 0 && isset($job_cver_ltrs[$_candidate_id]) && $job_cver_ltrs[$_candidate_id] != '') {
                                            ?>
                                            <li><a href="javascript:void(0);"
                                                   class="preview-candidate-profile jobsearch-modelcvrltr-btn-<?php echo($_rand_id) ?>"><?php esc_html_e('Cover Letter', 'wp-jobsearch') ?></a>
                                            </li>
                                            <?php
                                        }
                                        $file_path = get_post_meta($app_id, 'jobsearch_app_att_file_path', true);
                                        if (is_array($file_path) && isset($file_path[0])) {
                                            $file_path = $file_path[0];
                                        }
                                        if (isset($att_file_args['file_url']) && $att_file_args['file_url'] != '' && $app_id > 0 && $file_path != '' && file_exists($file_path)) {
                                            $file_attach_id = $att_file_args['file_id'];
                                            $filename = $att_file_args['file_name'];
                                            $att_file_path = $att_file_args['file_url'];

                                            $file_url = apply_filters('wp_jobsearch_email_cvfile_downlod_url', $att_file_path, $file_attach_id, $app_id);
                                            ?>
                                            <li><a href="<?php echo($file_url) ?>"
                                                   class="preview-candidate-profile btn-downlod-cvbtn"
                                                   oncontextmenu="javascript: return false;"
                                                   onclick="javascript: if ((event.button == 0 && event.ctrlKey)) {return false};"
                                                   download="<?php echo($filename) ?>"><?php esc_html_e('Download CV', 'wp-jobsearch') ?></a>
                                            </li>
                                            <?php
                                        }
                                        echo apply_filters('indash_email_apps_acts_list_after_download_link', '', $app_id, $job_id);
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>
                    <?php
                    $popup_args = array(
                        'job_id' => $_job_id,
                        'rand_num' => $_rand_id,
                        'candidate_id' => $_candidate_id,
                    );
                    add_action('wp_footer', function () use ($popup_args) {

                        global $jobsearch_plugin_options;

                        extract(shortcode_atts(array(
                            'job_id' => '',
                            'rand_num' => '',
                            'candidate_id' => '',
                        ), $popup_args));

                        $job_cver_ltrs = get_post_meta($job_id, 'jobsearch_job_apply_cvrs', true);
                        if (isset($job_cver_ltrs[$candidate_id]) && $job_cver_ltrs[$candidate_id] != '') {
                            ?>
                            <div class="jobsearch-modal jobsearch-typo-wrap jobsearch-candcover-popup fade"
                                 id="JobSearchCandCovershwModal<?php echo($rand_num) ?>">
                                <div class="modal-inner-area">&nbsp;</div>
                                <div class="modal-content-area">
                                    <div class="modal-box-area">
                                        <div class="jobsearch-modal-title-box">
                                            <h2><?php esc_html_e('Cover Letter', 'wp-jobsearch') ?></h2>
                                            <span class="modal-close"><i class="fa fa-times"></i></span>
                                        </div>
                                        <p><?php echo($job_cver_ltrs[$candidate_id]) ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    }, 11, 1);
                }
            }
        }

        public static function load_wapp_jobs_posts($jobs_posts, $employer_id)
        {
            if (!empty($jobs_posts)) {
                foreach ($jobs_posts as $_job_id) {
                    $job_applicants_list = get_post_meta($_job_id, 'jobsearch_job_emailapps_list', true);
                    $job_aplly_email = get_post_meta($_job_id, 'jobsearch_field_job_apply_email', true);

                    if (empty($job_applicants_list)) {
                        $job_applicants_list = array();
                    }

                    $job_applicants_count = !empty($job_applicants_list) ? count($job_applicants_list) : 0;
                    ?>

                    <div class="sjob-aplicants-list">
                        <div class="thjob-title">
                            <h2>
                                <?php echo get_the_title($_job_id) ?>
                                <span class="email-sento"><?php esc_html_e('Email Sent To: ', 'wp-jobsearch') ?> <a
                                            href="mailto:<?php echo($job_aplly_email) ?>"><?php echo($job_aplly_email) ?></a></span>
                            </h2>
                            <div class="total-appcreds-con">
                                <ul>
                                    <li>
                                        <div class="applicnt-count-box tot-apps">
                                            <span><?php esc_html_e('Total Applicants: ', 'wp-jobsearch') ?></span> <?php echo absint($job_applicants_count) ?>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="jobsearch-applied-jobs">
                            <?php
                            if (!empty($job_applicants_list)) {
                                ?>
                                <ul id="job-apps-list<?php echo($_job_id) ?>" class="jobsearch-row">
                                    <?php
                                    self::list_job_all_apps($_job_id, $employer_id);
                                    ?>
                                </ul>
                                <?php
                                if ($job_applicants_count > 6) {
                                    $total_apps_pages = ceil($job_applicants_count / 6);
                                    ?>
                                    <div class="lodmoreemil-jobapps-btnsec">
                                        <a href="javascript:void(0);" class="lodmoreemil-jobapps-btn"
                                           data-jid="<?php echo($_job_id) ?>"
                                           data-tpages="<?php echo($total_apps_pages) ?>"
                                           data-gtopage="2"><?php esc_html_e('Load More Applicants', 'wp-jobsearch') ?></a>
                                    </div>
                                    <?php
                                }
                            } else {
                                ?>
                                <p><?php esc_html_e('No applicant found.', 'wp-jobsearch') ?></p>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
            }
        }

        public function load_all_jobs_post_data()
        {
            $force_std = $_POST['force_std'];
            $posttype = $_POST['posttype'];

            $employer_id = isset($_POST['emp_id']) ? $_POST['emp_id'] : 0;
            $employer_id = absint($employer_id);

            $args = array(
                'posts_per_page' => "-1",
                'post_type' => $posttype,
                'post_status' => array('publish', 'draft'),
                'fields' => 'ids',
                'order' => 'DESC',
                'orderby' => 'ID',
                'meta_query' => array(
                    array(
                        'key' => 'jobsearch_field_job_posted_by',
                        'value' => $employer_id,
                        'compare' => '=',
                    ),
                    array(
                        'key' => 'jobsearch_job_emailapps_list',
                        'value' => '',
                        'compare' => '!=',
                    ),
                ),
            );

            $custom_query = new WP_Query($args);
            $all_records = $custom_query->posts;

            $html = "<option value=\"\">" . esc_html__('Please select job', 'wp-jobsearch') . "</option>" . "\n";
            if (isset($all_records) && !empty($all_records)) {
                foreach ($all_records as $user_var) {
                    $selected = $user_var == $force_std ? ' selected="selected"' : '';
                    $post_title = get_the_title($user_var);
                    $html .= "<option{$selected} value=\"{$user_var}\">{$post_title}</option>" . "\n";
                }
            }
            echo json_encode(array('html' => $html));

            wp_die();
        }

        public function load_more_apswith_job_apps()
        {
            $page_num = $_POST['page_num'];
            $employer_id = isset($_POST['emp_id']) ? $_POST['emp_id'] : 0;
            $employer_id = absint($employer_id);

            $args = array(
                'post_type' => 'job',
                'posts_per_page' => 5,
                'paged' => $page_num,
                'post_status' => array('publish', 'draft'),
                'fields' => 'ids',
                'order' => 'DESC',
                'orderby' => 'ID',
                'meta_query' => array(
                    array(
                        'key' => 'jobsearch_field_job_posted_by',
                        'value' => $employer_id,
                        'compare' => '=',
                    ),
                    array(
                        'key' => 'jobsearch_job_emailapps_list',
                        'value' => '',
                        'compare' => '!=',
                    ),
                ),
            );
            $jobs_query = new WP_Query($args);
            $jobs_posts = $jobs_query->posts;

            ob_start();
            self::load_wapp_jobs_posts($jobs_posts, $employer_id);
            $html = ob_get_clean();
            echo json_encode(array('html' => $html));

            wp_die();
        }

        public function load_more_apswith_apps_lis()
        {
            $page_num = absint($_POST['page_num']);
            $_job_id = absint($_POST['_job_id']);

            $employer_id = isset($_POST['emp_id']) ? $_POST['emp_id'] : 0;
            $employer_id = absint($employer_id);

            ob_start();
            self::list_job_all_apps($_job_id, $employer_id, $page_num);
            $html = ob_get_clean();
            echo json_encode(array('html' => $html));

            wp_die();
        }

        public function load_single_apswith_job_inlist()
        {

            $_job_id = absint($_POST['_job_id']);
            $jobs_posts = array($_job_id);
            ob_start();
            self::load_wapp_jobs_posts($jobs_posts, $employer_id);
            $html = ob_get_clean();
            echo json_encode(array('html' => $html));

            wp_die();
        }

        public function alljobs_apps_count_loadboxes()
        {

            $employer_id = isset($_POST['emp_id']) ? $_POST['emp_id'] : 0;
            $employer_id = absint($employer_id);

            $appcounts = $shappcounts = $rejappcounts = 0;

            $args = array(
                'post_type' => 'job',
                'posts_per_page' => -1,
                'post_status' => array('publish', 'draft'),
                'fields' => 'ids',
                'order' => 'DESC',
                'orderby' => 'ID',
                'meta_query' => array(
                    array(
                        'key' => 'jobsearch_field_job_posted_by',
                        'value' => $employer_id,
                        'compare' => '=',
                    ),
                    array(
                        'key' => 'jobsearch_job_emailapps_list',
                        'value' => '',
                        'compare' => '!=',
                    ),
                ),
            );
            $jobs_query = new WP_Query($args);
            $jobs_posts = $jobs_query->posts;

            if (!empty($jobs_posts)) {
                foreach ($jobs_posts as $_job_id) {
                    $job_applicants_list = get_post_meta($_job_id, 'jobsearch_job_emailapps_list', true);

                    if (empty($job_applicants_list)) {
                        $job_applicants_list = array();
                    }

                    $job_applicants_count = !empty($job_applicants_list) ? count($job_applicants_list) : 0;
                    $appcounts += $job_applicants_count;
                }
            }

            echo json_encode(array('appcounts' => $appcounts));

            wp_die();
        }

    }

    $empemail_applicants_handle = new jobsearch_empemail_applicants_handle();
}
