<?php
/*
Plugin Name: WPDM - Advanced Access Control
Plugin URI: https://www.wpdownloadmanager.com/download/advanced-access-control/
Description: Advanced Access Control add-on will help you to control user specific access to your files and document downloads
Author: WordPress Download Manager
Version: 3.2.1
Author URI: https://www.wpdownloadmanager.com/
Text Domain: wpdm-advanced-access-control
Domain Path: /languages
Update URI: wpdm-advanced-access-control
*/

if(defined('WPDM_VERSION')) {

    define("WPDM_AAC_TEXT_DOMAIN", 'wpdm-advanced-access-control');

    add_action("plugin_loaded", function (){
        load_plugin_textdomain('wpdm-advanced-access-control', WP_PLUGIN_URL . "/wpdm-advanced-access-control/languages/", 'wpdm-advanced-access-control/languages/');
    });

    function wpdm_cal_remove_role()
    {
        if (!is_admin()) return;
        $core_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
        if (in_array($_POST['wpdm_cal_role_id'], $core_roles)) die(__("System default role can't be deleted!", 'wpdm-advanced-access-control'));
        remove_role($_POST['wpdm_cal_role_id']);
        echo 'done!';
        die();
    }

    function wpdm_cal_create_new_role()
    {
        global $wpdb;
        $option_name = "{$wpdb->prefix}user_roles";
        $roles = maybe_unserialize(get_option($option_name));
        $nrk = preg_replace('/([^a-z,0-9]+)/is', '_', strtolower($_POST['wpdm_cal_new_role']));
        if (!@array_key_exists($nrk, $roles) && $nrk != '') {
            $role_obj = new WP_Roles();
            $role_obj->add_role($nrk, $_POST['wpdm_cal_new_role'], array('read' => 1, 'level_0' => 1));
            echo $_POST['wpdm_cal_new_role'];
            die();
        } else {
            if (@array_key_exists($nrk, $roles))
                die(__('Role name already exists', 'wpdm-advanced-access-control'));
            if ($nrk == '')
                die(__('Invalid Role name', 'wpdm-advanced-access-control'));
        }
    }

    function wpdm_cal_my_downloads($params = array())
    {
        $current_user = wp_get_current_user();

        $pageurl = get_permalink();
        if (!is_user_logged_in())
            return do_shortcode("[wpdm_login_form redirect='{$pageurl}']");
        else {


            $args = array(
                'post_type' => 'wpdmpro',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => array(
                    //'relation' => 'AND',
                    array(
                        'key' => '__wpdm_user_access',
                        'value' => '"' . $current_user->user_login . '"',
                        'compare' => 'LIKE',
                    )
                )

            );


            // All files inherited from user roles
            if(wpdm_valueof($params, 'role', 'int') === 1) {
                foreach ($current_user->roles as $role) {
                    $args['meta_query'][] = array(
                        'key' => '__wpdm_access',
                        'value' => $role,
                        'compare' => 'LIKE',
                    );
                }
            }

            // All files assigned to ALL VISITORS
            /*$args['meta_query'][] = array(
                'key' => '__wpdm_access',
                'value' => 'guest',
                'compare' => 'LIKE',
            );*/


            $files = new WP_Query($args);
            $_files = null;

            if(wpdm_valueof($params, 'cats', 'int') === 1) {

                $categories = get_terms(array(
                    'taxonomy' => 'wpdmcategory',
                    'hide_empty' => false,
                ));
                $_categories = $cat_ids = array();
                foreach ($categories as $category) {
                    $access = maybe_unserialize(get_term_meta($category->term_id, '__wpdm_user_access', true));
                    if (!is_array($access)) $access = array();
                    if (in_array($current_user->user_login, $access)) {
                        $_categories[] = $category;
                        $cat_ids[] = $category->term_id;
                    }
                }

                $categories = $_categories;

                if (count($cat_ids) > 0) {

                    $args = array(
                        'post_type' => 'wpdmpro',
                        'posts_per_page' => -1,
                    );


                    $args['tax_query'] = array(
                        'relation' => 'OR',
                        array(
                            'taxonomy' => 'wpdmcategory',
                            'field' => 'term_id',
                            'terms' => $cat_ids,
                            'operator' => 'IN',
                        )
                    );
                    $_files = new WP_Query($args);

                }
            }

            $files = $files->get_posts();

            if (is_object($_files) && $_files->post_count > 0)
                $files = $files + $_files->get_posts();

            foreach ($files as $id => $file){
                if(function_exists('wpdmpp_effective_price') && (double)wpdmpp_effective_price($file->ID) > 0) unset($files[$id]);
            }

            if (is_array($params)) extract($params);
            $login = isset($login) ? $login : 0;

            ob_start();

            if (!isset($template))
                include(wpdm_tpl_path("wpdm-my-downloads.php", dirname(__FILE__) . '/tpls'.WPDM()->bsversion));
            else
                include(wpdm_tpl_path("wpdm-my-downloads-wlt.php", dirname(__FILE__) . '/tpls'.WPDM()->bsversion));

            $data = ob_get_clean();

            return $data;
        }
    }

    function wpdm_cal_suggest_members()
    {
        global $wpdb, $blog_id;
        if (!current_user_can('edit_posts')) return;
        $q = esc_attr($_GET['term']);
        $prefix = str_replace("{$blog_id}_", "", $wpdb->prefix);
        $data = $wpdb->get_results("select user_login as `value`, user_login as name from {$prefix}users where `user_login` LIKE '%$q%' or `user_email` LIKE '%$q%'", ARRAY_A);
        if (is_multisite()) {
            $users = array();
            foreach ($data as $item) {
                if (is_user_member_of_blog($item->ID, get_current_blog_id()))
                    $users[] = $item;
            }
            $data = $users;
        }
        wp_send_json($data);
        die();
    }

    function wpdm_cal_update_access($id, $package)
    {

        $package['access'] = maybe_unserialize($package['access']);
        //if($package['access']['0']=='selected_members_only'){
        update_option("wpdm_package_selected_members_only_" . $id, $_POST['allowed_members']);
        //}
    }

    function wpdm_cal_interface_admin()
    {
        if (get_post_type() != 'wpdmpro') return;

        ?>
        <script type="text/javascript">

            jQuery(function () {
                var uacc = '<?php

                    $susers = maybe_unserialize(get_post_meta(get_the_ID(), '__wpdm_user_access', true));
                    if ($susers) {
                        if(!is_array($susers)) $susers = explode(",", $susers);
                        foreach ($susers as $suser) {
                            echo '<span class="btn btn-info btn-sm" id="uaco-' . sanitize_title($suser) . '"><input type="hidden" name="file[user_access][]" value="' . $suser . '" /> <a class="uaco-del" onclick="jQuery(this.rel).remove()" rel="#uaco-' . sanitize_title($suser) . '"><i class="far fa-times-circle"></i></a>&nbsp;' . $suser . '</span>';
                        }
                    }

                    ?>';
                jQuery('<tr id="select_members_row"><td><nobr><?php echo __('Select Members:', 'wpdm-advanced-access-control'); ?></nobr></td>  <td><div id="uaco" style="float: left">' + uacc + '<div style="clear: both"></div></div><input type="text" class="form-control input-sm" style="width: 150px" size="10" id="maname" name="mname" placeholder="<?=__('Enter name or email', 'wpdm-advanced-access-control'); ?>" title="<?=__('Enter name or email', 'wpdm-advanced-access-control'); ?>"></td></tr><tr><td><?php echo __( "Notify", 'wpdm-advanced-access-control' ) ?></td><td><label><input type="checkbox" value="1" name="notify_users" > <?php echo __( "Send email notification to selected members", "wpdm-advanced-access-control" ) ?></label></td></tr>').insertAfter('#access_row');

                function split(val) {
                    return val.split(/,\s*/);
                }

                function extractLast(term) {
                    return split(term).pop();
                }

                jQuery("#maname")
                    .bind("keydown", function (event) {
                        if (event.keyCode === jQuery.ui.keyCode.TAB &&
                            jQuery(this).data("ui-autocomplete").menu.active) {
                            event.preventDefault();
                        }
                    })
                    .autocomplete({
                        source: function (request, response) {
                            jQuery.getJSON(ajaxurl + '?action=wpdm_cal_suggest_members', {
                                action: 'wpdm_cal_suggest_members',
                                term: extractLast(request.term)
                            }, response);
                        },
                        search: function () {

                            var term = extractLast(this.value);
                            if (term.length < 2) {
                                return false;
                            }
                        },
                        focus: function () {

                            return false;
                        },
                        select: function (event, ui) {
                            /*
                             var terms = split( this.value );

                             terms.pop();

                             terms.push( ui.item.value );

                             terms.push( "" );
                             this.value = terms.join( ", " );
                             return false;
                             */
                            //alert(ui.item.value);
                            jQuery('#uaco').prepend('<span  class="btn btn-info btn-sm" id="uaco-' + ui.item.value.replace(/[^a-zA-Z]/ig, '-') + '"><input type="hidden" name="file[user_access][]" value="' + ui.item.value + '" /> <a class="uaco-del" onclick="jQuery(this.rel).remove()" rel="#uaco-' + ui.item.value.replace(/[^a-zA-Z]/ig, '-') + '"><i class="far fa-times-circle"></i></a>&nbsp;' + ui.item.value + '</span>');
                            this.value = "";
                            return false;
                        }
                    });


            });

            function createrole() {
                if (jQuery('#wpdm_cal_new_role').val() == '') {
                    alert('Enter a valid role name!');
                    return false;
                }
                jQuery('#pwt').fadeIn();
                jQuery.post(ajaxurl, {
                    action: 'wpdm_cal_create_new_role',
                    wpdm_cal_new_role: jQuery('#wpdm_cal_new_role').val()
                }, function (res) {
                    jQuery('#roletable tbody tr:last-child').before("<tr><td>" + res + "</td><td></td></tr>");
                    jQuery('#pwt').fadeOut();
                });
                return false;
            }

            function delete_role(role) {
                if (!confirm('Are you sure?')) return false;
                jQuery('#tr' + role + " td:last-child input").val('Deleting...').attr('disabled', 'disabled');
                jQuery.post(ajaxurl, {action: 'wpdm_cal_remove_role', wpdm_cal_role_id: role}, function (res) {
                    if (res == 'done!')
                        jQuery('#tr' + role).fadeOut();
                    else alert(res);
                });
            }

            function edit_role(role) {
                if (jQuery('#tr' + role + " td:last-child input.button-primary").val() == 'Edit')
                    jQuery('#tr' + role + " td:last-child input.button-primary").val('Cancel Edit');
                else
                    jQuery('#tr' + role + " td:last-child input.button-primary").val('Edit');
                jQuery('#edit-' + role).slideToggle();
                /*jQuery.post(ajaxurl,{action:'wpdm_cal_remove_role',wpdm_cal_role_id:role},function(res){
                 if(res=='done!')
                 jQuery('#tr'+role).fadeOut();
                 else alert(res);
                 });  */
            }

        </script>

        <style>
            #uaco > span {
                line-height: 16px;
                border-radius: 3px;
                display: inline-block;
                margin-right: 5px;
                margin-bottom: 5px;
            }

            #uaco > span a {
                border-right: 1px dotted #fff;
                margin-right: 4px;
            }

            #uaco > span .far {
                margin-right: 6px;
                cursor: pointer;
                color: #ffffff;
                -webkit-transition: ease-in-out 300ms;
                -moz-transition: ease-in-out 300ms;
                -ms-transition: ease-in-out 300ms;
                -o-transition: ease-in-out 300ms;
                transition: ease-in-out 300ms;
            }

            #uaco > span .far:hover {
                transform: scale(1.2);
            }

            .form-control.ui-autocomplete-input {
                background-position: 125px center !important;
            }

            .ui-autocomplete.ui-front.ui-menu.ui-widget.ui-widget-content {
                max-height: 300px;
                overflow: auto;
            }

            .ui-autocomplete.ui-front.ui-menu.ui-widget.ui-widget-content li:not(:last-child) {
                border-bottom: 1px solid #dddddd !important;
                margin-top: -1px;
            }

            .ui-autocomplete.ui-front.ui-menu.ui-widget.ui-widget-content li {
                padding: 7px 10px;
                width: calc(100% - 22px);
            }

            .ui-autocomplete.ui-front.ui-menu.ui-widget.ui-widget-content li.ui-state-focus {
                border: 1px solid #aaaaaa !important;
            }

        </style>

        <?php
    }

    function wpdm_cal_interface_front()
    {

        global $post;

        ?>
        <script type="text/javascript">

            jQuery(function () {
                var uacc = '<?php
                    $susers = maybe_unserialize(get_post_meta(get_the_ID(), '__wpdm_user_access', true));
                    if ($susers) {
                        if(!is_array($susers)) $susers = explode(",", $susers);
                        foreach ($susers as $suser) {
                            echo '<span class="btn btn-info btn-sm" id="uaco-' . sanitize_title($suser) . '"><input type="hidden" name="file[user_access][]" value="' . $suser . '" /> <a class="uaco-del" onclick="jQuery(this.rel).remove()" rel="#uaco-' . sanitize_title($suser) . '"><i class="far fa-times-circle"></i></a>&nbsp;' . $suser . '</span>';
                        }
                    }
                    ?>';

                jQuery('<div id="select_members_row" class="form-group row"><div class="col-md-3"><nobr><?php _e('Select Members:', 'wpdm-advanced-access-control'); ?></nobr></div><div class="col-md-9"><div id="uaco" style="float: left">' + uacc + '<div style="clear: both"></div></div><input type="text" class="form-control" size="10" id="maname" name="mname" placeholder="<?php _e('Enter name or email', 'wpdm-advanced-access-control'); ?>" title="<?php _e('Enter name or email', 'wpdm-advanced-access-control'); ?>"></div></div>').insertAfter('#access_row');

                function split(val) {
                    return val.split(/,\s*/);
                }

                function extractLast(term) {
                    return split(term).pop();
                }

                jQuery("#maname")
                    .bind("keydown", function (event) {
                        if (event.keyCode === jQuery.ui.keyCode.TAB &&
                            jQuery(this).data("ui-autocomplete").menu.active) {
                            event.preventDefault();
                        }
                    })
                    .autocomplete({
                        source: function (request, response) {
                            jQuery.getJSON(ajaxurl + '?action=wpdm_cal_suggest_members', {
                                action: 'wpdm_cal_suggest_members',
                                term: extractLast(request.term)
                            }, response);
                        },
                        search: function () {

                            var term = extractLast(this.value);
                            if (term.length < 2) {
                                return false;
                            }
                        },
                        focus: function () {

                            return false;
                        },
                        select: function (event, ui) {
                            jQuery('#uaco').prepend('<span  class="btn btn-info btn-sm" id="uaco-' + ui.item.value.replace(/[^a-zA-Z]/ig, '-') + '"><input type="hidden" name="file[user_access][]" value="' + ui.item.value + '" /> <a class="uaco-del" onclick="jQuery(this.rel).remove()" rel="#uaco-' + ui.item.value.replace(/[^a-zA-Z]/ig, '-') + '"><i class="far fa-times-circle"></i></a>&nbsp;' + ui.item.value + '</span>');
                            this.value = "";
                            return false;
                        }
                    });
            });

        </script>

        <style>
            #uaco > span {
                line-height: 16px;
                border-radius: 3px;
                display: inline-block;
                margin-right: 5px;
                margin-bottom: 5px;
            }

            #uaco > span a {
                border-right: 1px dotted #fff;
                margin-right: 4px;
            }

            #uaco > span .far {
                margin-right: 6px;
                cursor: pointer;
                color: #ffffff;
                -webkit-transition: ease-in-out 300ms;
                -moz-transition: ease-in-out 300ms;
                -ms-transition: ease-in-out 300ms;
                -o-transition: ease-in-out 300ms;
                transition: ease-in-out 300ms;
            }

            #uaco > span .far:hover {
                transform: scale(1.2);
            }

            .form-control.ui-autocomplete-input {
                background-position: 125px center !important;
            }

            .ui-autocomplete.ui-front.ui-menu.ui-widget.ui-widget-content {
                max-height: 300px;
                overflow: auto;
            }

            .ui-autocomplete.ui-front.ui-menu.ui-widget.ui-widget-content li:not(:last-child) {
                border-bottom: 1px solid #dddddd !important;
                margin-top: -1px;
            }

            .ui-autocomplete.ui-front.ui-menu.ui-widget.ui-widget-content li {
                padding: 7px 10px;
                width: calc(100% - 22px);
            }

            .ui-autocomplete.ui-front.ui-menu.ui-widget.ui-widget-content li.ui-state-focus {
                border: 1px solid #aaaaaa !important;
            }
        </style>

        <?php
    }

    function wpdm_block_dllink($package, $template, $type)
    {

        global $wpdb;
        $current_user = wp_get_current_user();
        $uroles = array_keys($current_user->caps);
        $urole = array_shift($uroles);
        $wpdm_download_button_class = wpdm_download_button_style($type === 'page', $package['ID']);

        $users = maybe_unserialize(get_post_meta($package['ID'], '__wpdm_user_access', true));
        if(!is_array($users) && $users !== '') $users = explode(",", $users);
        if (!isset($package['access'])) $package['access'] = array();

        if (!$users || (is_user_logged_in() && count(array_intersect($current_user->roles, $package['access'])) > 0) || in_array('guest', $package['access']) || WPDM()->package->isLocked($package['ID'])) return $package;


        if (is_user_logged_in() && count($users) > 0 && in_array($current_user->user_login, $users)) {
            $dkey = is_array($package['files']) ? md5(serialize($package['files'])) : md5($package['files']);
            $package['access'] = array('guest');
            $package['download_url'] = wpdm_download_url($package, '');
            if (WPDM()->package->userDownloadLimitExceeded($package['ID'])) {
                $package['download_url'] = '#';
                $package['link_label'] = 'Limit Exceeded';
            }
            $package['download_link'] = $package['download_link_extended'] = "<a class='wpdm-download-link $wpdm_download_button_class' rel='noindex nofollow' href='{$package['download_url']}'>{$package['link_label']}</a>";
            return $package;
        } else {
            $package['download_url'] = "#";
            $package['access'] = array();
            $package['download_link'] = $package['download_link_extended'] = stripslashes(get_option('wpdm_permission_msg'));
            if (get_option('_wpdm_hide_all', 0) == 1) {

                $package['download_link'] = $package['download_link_extended'] = 'blocked';
                if (!is_user_logged_in()) $package['download_link'] = $package['download_link_extended'] = 'loginform';

            }
        }

        return $package;
    }

    function wpdm_cal_check_permission($package)
    {
        global $wpdb, $current_user;
        $uroles = array_keys($current_user->caps);
        $urole = array_shift($uroles);

        $users = maybe_unserialize(get_post_meta($package['ID'], '__wpdm_user_access', true));
        if (!is_array($users)) $users = array();
        $terms = get_terms($package['ID']);
        foreach ($terms as $term) {
            if(isset($term->term_id)) {
                $susers = maybe_unserialize(get_term_meta($term->term_id, '__wpdm_user_access', true));
                if (is_array($susers))
                    $users = $users + $susers;
            }

        }

        $shared_cats = AdvancedAccessControl::sharedCats();
        foreach ($shared_cats as &$shared_cat) {
            $shared_cat = $shared_cat->term_id;
        }
        $package_cats = wp_get_post_terms($package['ID'], 'wpdmcategory');
        foreach ($package_cats as &$package_cat) {
            $package_cat = $package_cat->term_id;
        }

        $in_cats = array_intersect($shared_cats, $package_cats);


        if (count($users) == 0) return $package;

        if (is_user_logged_in() && (in_array($current_user->user_login, $users) || count($in_cats) > 0)) {
            $package['access'] = array('guest');
            $_GET['masterkey'] = $package['masterkey'] = 1;
        }
        return $package;
    }

    function wpdm_cal_capabilities_html()
    {
        $html = wpdm_cal_default_capabilities();
    }

    function get_editable_roles_()
    {
        global $wp_roles;

        $all_roles = $wp_roles->roles;
        $editable_roles = apply_filters('editable_roles', $all_roles);

        return $editable_roles;
    }

    function wpdm_cal_default_capabilities()
    {

        $defaults = array(
            'activate_plugins',
            'add_users',
            'create_users',
            'delete_others_pages',
            'delete_others_posts',
            'delete_pages',
            'delete_plugins',
            'delete_posts',
            'delete_private_pages',
            'delete_private_posts',
            'delete_published_pages',
            'delete_published_posts',
            'delete_users',
            'edit_dashboard',
            'edit_files',
            'edit_others_pages',
            'edit_others_posts',
            'edit_pages',
            'edit_plugins',
            'edit_posts',
            'edit_private_pages',
            'edit_private_posts',
            'edit_published_pages',
            'edit_published_posts',
            'edit_theme_options',
            'edit_themes',
            'edit_users',
            'import',
            'install_plugins',
            'install_themes',
            'list_users',
            'manage_categories',
            'manage_links',
            'manage_options',
            'moderate_comments',
            'promote_users',
            'publish_pages',
            'publish_posts',
            'read',
            'read_private_pages',
            'read_private_posts',
            'remove_users',
            'switch_themes',
            'unfiltered_html',
            'unfiltered_upload',
            'update_core',
            'update_plugins',
            'update_themes',
            'upload_files'
        );

        /* Return the array of default capabilities. */
        return $defaults;
    }


    function wpdm_cal_enqueue_scripts()
    {
        if (get_post_type() == 'wpdmpro' || wpdm_query_var('taxonomy') === 'wpdmcategory') {
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-autocomplete');
        }

    }

    function wpdm_cal_tobyte($p_sFormatted)
    {
        $aUnits = array('B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8);
        $sUnit = strtoupper(trim(substr($p_sFormatted, -2)));
        if (intval($sUnit) !== 0) {
            $sUnit = 'B';
        }
        if (!in_array($sUnit, array_keys($aUnits))) {
            return false;
        }
        $iUnits = trim(substr($p_sFormatted, 0, strlen($p_sFormatted) - 2));
        if (!intval($iUnits) == $iUnits) {
            return false;
        }
        return $iUnits * pow(1024, $aUnits[$sUnit]);
    }

    function wpdm_cal_custom_data($data)
    {
        global $current_user;
        if (is_user_logged_in() && isset($data['user_access']) && is_array($data['user_access']) && in_array($current_user->user_login, $data['user_access']))
            $data['access'] = array('guest');
        return $data;
    }

    function wpdm_cal_check_lock($locked, $id)
    {
        global $current_user;
        $user_access = maybe_unserialize(get_post_meta($id, '__wpdm_user_access', true));
        if (is_user_logged_in() && is_array($user_access) && in_array($current_user->user_login, $user_access))
            $locked = '';
        return $locked;
    }

    function wpdm_cal_category_query_params($params)
    {
        unset($params['meta_query']);
        return $params;
    }


    if (is_admin()) {

        //add_action('wp_ajax_wpdm_cal_create_new_role','wpdm_cal_create_new_role');
        //add_action('wp_ajax_wpdm_cal_remove_role','wpdm_cal_remove_role');
        add_action('wp_ajax_wpdm_cal_suggest_members', 'wpdm_cal_suggest_members');
        add_action("admin_head", 'wpdm_cal_interface_admin');
        add_action("admin_enqueue_scripts", 'wpdm_cal_enqueue_scripts');
    }

    if (!is_admin()) {
        add_action('wp_ajax_wpdm_cal_suggest_members', 'wpdm_cal_suggest_members');
        add_action("wp_enqueue_scripts", 'wpdm_cal_enqueue_scripts');
        add_action('wpdm-package-form-left', 'wpdm_cal_interface_front');
    }


    class AdvancedAccessControl
    {

        function __construct()
        {
            //add_filter('wpdm_meta_box', array($this, 'metaBox'));
            //add_action( 'wpdm-package-form-left', array( $this, 'accessControlOptionPanel' ) );

            add_filter("wpdm_allowed_roles", array($this, 'allowedRoles'), 10, 2);
            add_filter("wpdm_email_templates", array($this, 'emailTemplate'), 10, 2);
            add_filter("wpdm_csv_import/user_access", array($this, 'csvImport'), 10, 2);

            add_action('wpdmcategory_add_form_fields', array($this, 'categoryAccess'), 10, 2);
            add_action('wpdmcategory_edit_form_fields', array($this, 'categoryAccess'), 10, 2);
            add_filter("wpdm_user_dashboard_menu", array($this, "dashboardMenu"));
            add_action('save_post', array($this, 'notifyMembers'), 999);
            add_action('wpdm_file_hosting_create_package', array($this, 'notifyMembers'), 999);

        }

        function emailTemplate($templates)
        {
            $admin_email = get_option( 'admin_email' );
            $sitename    = get_option( "blogname" );
            $templates['notify-users-new'] = array(
                'label'   => __( "New Item Notification" , 'wpdm-advanced-access-control' ),
                'for'     => 'customer',
                'default' => array(
                    'subject'    => __( "New Item: [#package_name#]" , 'wpdm-advanced-access-control' ),
                    'from_name'  => get_option( 'blogname' ),
                    'from_email' => $admin_email,
                    'message'    => 'New item is available to download at [#sitename#]<br/>Please click on following link to review:<br/><b><a style="display: block;text-align: center" class="button green" href="[#package_url#]">View Item</a></b><br/><br/><br/>Best Regards,<br/>Support Team<br/><b>[#sitename#]</b>'
                )
            );
            $templates['notify-users-update'] = array(
                'label'   => __( "Item Update Notification" , 'wpdm-advanced-access-control' ),
                'for'     => 'customer',
                'default' => array(
                    'subject'    => __( "Updated: [#package_name#]" , 'wpdm-advanced-access-control' ),
                    'from_name'  => get_option( 'blogname' ),
                    'from_email' => $admin_email,
                    'message'    => 'A new update for [#package_name#] is available to download at [#sitename#]<br/>Please click on following link to review:<br/><b><a style="display: block;text-align: center" class="button green" href="[#package_url#]">View Item</a></b><br/><br/><br/>Best Regards,<br/>Support Team<br/><b>[#sitename#]</b>'
                )
            );

            return $templates;

        }

        function notifyMembers($post)
        {
            if(get_post_type($post) === 'wpdmpro' && isset($_REQUEST['notify_users'], $_REQUEST['file'], $_REQUEST['file']['user_access']) && count($_REQUEST['file']['user_access']) > 0){
                $members = $_REQUEST['file']['user_access'];
                $email = array();
                $to_email = get_option('admin_email');
                $etemplate = wpdm_query_var('originalaction') === 'editpost' ? 'notify-users-update' : 'notify-users-new';
                $params = array(
                    'package_name' => get_the_title($post),
                    'package_url' => get_permalink($post),
                    'to_email' => $to_email

                );
                foreach ($members as $i => $member){
                    $user = get_user_by('login', $member);
                    $params['to_email'] = $user->user_email;
                    $params['name'] = $user->display_name;
                    \WPDM\__\Email::send($etemplate, $params);
                    time_nanosleep(0, 100000000);
                }
            }
        }

        function notifyMembersFrontend($post)
        {
            if(get_post_type($post) === 'wpdmpro' && isset($_REQUEST['file']['user_access']) && count($_REQUEST['file']['user_access']) > 0){
                $members = $_REQUEST['file']['user_access'];
                $email = array();
                $to_email = get_option('admin_email');
                foreach ($members as $i => $member){
                    $user = get_user_by('login', $member);
                    if($i===0)
                        $to_email = $user->user_email;
                    else
                        $email[] = $user->user_email;
                }
                $params = array(
                    'package_name' => get_the_title($post),
                    'package_url' => get_permalink($post),
                    'to_email' => $to_email

                );
                if(count($email) > 0)
                    $params['bcc'] = implode(",", $email);

                \WPDM\__\Email::send("notify-users-new", $params);
            }
        }

        function dashboardMenu($items)
        {
            $items = array_splice($items, 0, count($items) - 1) + array('my-downloads' => array('name' => __('My Downloads', 'wpdm-advanced-access-control'), 'callback' => 'wpdm_cal_my_downloads')) + array_splice($items, count($items) - 1);
            $items['my-downloads']['icon'] = 'fas fa-arrow-down color-purple';
            return $items;
        }

        /**
         * Reconfigure allowed roles for a package
         * @param $roles
         * @param $id
         * @return array
         */
        function allowedRoles($roles, $id)
        {
            global $current_user;
            $users = maybe_unserialize(get_post_meta($id, '__wpdm_user_access', true));
            $users = is_array($users) ? $users : array();

            $shared_cats = AdvancedAccessControl::sharedCats();
            foreach ($shared_cats as &$shared_cat) {
                $shared_cat = $shared_cat->term_id;
            }
            $package_cats = wp_get_post_terms($id, 'wpdmcategory');
            foreach ($package_cats as &$package_cat) {
                $package_cat = $package_cat->term_id;
            }

            $in_cats = array_intersect($shared_cats, $package_cats);


            if (is_user_logged_in() && (in_array($current_user->user_login, $users) || count($in_cats) > 0))
                $roles = array('guest');
            return $roles;
        }

        /**
         * Get shared categories for any specified or logged in user
         * @param null $user_login
         * @return array
         */
        static function sharedCats($user_login = null)
        {
            global $current_user;
            $user_login = $user_login ? $user_login : $current_user->user_login;
            $_cats = get_terms(array('taxonomy' => 'wpdmcategory', 'hide_empty' => false));
            $cats = array();
            foreach ($_cats as $cat) {
                $users = maybe_unserialize(get_term_meta($cat->term_id, '__wpdm_user_access', true));
                if (is_array($users) && in_array($user_login, $users))
                    $cats[] = $cat;

            }
            return $cats;
        }

        function categoryAccess()
        {
            ?>

            <?php if (wpdm_query_var('tag_ID') > 0){ ?>
            <tr class="form-field">
                <th>
                    <?php _e('User Access:', 'wpdm-advanced-access-control'); ?>
                </th>
                <td>
                    <?php } ?>
                    <div class="w3eden">
                        <div class="form-field">
                            <fieldset class="panel panel-default">
                                <?php if (!wpdm_query_var('tag_ID')) { ?>
                                    <div class="panel-heading"
                                         style="background: #ffffff;border-bottom: 1px solid #dddddd !important"><?php _e('User Access:', 'wpdm-advanced-access-control'); ?>
                                    </div>
                                <?php } ?>
                                <div class="panel-body" id="uaco">
                                    <input type="hidden" name="__wpdmcategory[user_access][]" value="" />
                                    <?php
                                    $susers = maybe_unserialize(get_term_meta(wpdm_query_var('tag_ID'), '__wpdm_user_access', true));
                                    if ($susers && is_array($susers)) {
                                        foreach ($susers as $suser) {
                                            if($suser !== '') {
                                                echo '<span class="btn btn-info btn-sm" id="uaco-' . sanitize_title($suser) . '"><input type="hidden" name="__wpdmcategory[user_access][]" value="' . $suser . '" /> <a class="uaco-del" onclick="jQuery(this.rel).remove()" rel="#uaco-' . sanitize_title($suser) . '"><i class="far fa-times-circle"></i></a>&nbsp;' . $suser . '</span>';
                                            }
                                        }
                                    }
                                    ?>

                                </div>
                                <div class="panel-footer" style="background: #ffffff"><input id="maname"
                                                                                             placeholder="<?php _e('Start typing to search members...','wpdm-advanced-access-control'); ?>"
                                                                                             style="width: 100%"
                                                                                             type="text"
                                                                                             class="form-control"></div>
                            </fieldset>
                        </div>
                    </div>
                    <?php if (wpdm_query_var('tag_ID') > 0){ ?>

                </td>
            </tr>

        <?php } ?>


            <script type="text/javascript">

                jQuery(function () {
                    var uacc = '';

                    function split(val) {
                        return val.split(/,\s*/);
                    }

                    function extractLast(term) {
                        return split(term).pop();
                    }

                    jQuery("#maname")
                        .bind("keydown", function (event) {
                            if (event.keyCode === jQuery.ui.keyCode.TAB &&
                                jQuery(this).data("ui-autocomplete").menu.active) {
                                event.preventDefault();
                            }
                        })
                        .autocomplete({
                            source: function (request, response) {
                                jQuery.getJSON(ajaxurl + '?action=wpdm_cal_suggest_members', {
                                    action: 'wpdm_cal_suggest_members',
                                    term: extractLast(request.term)
                                }, response);
                            },
                            search: function () {

                                var term = extractLast(this.value);
                                if (term.length < 2) {
                                    return false;
                                }
                            },
                            focus: function () {

                                return false;
                            },
                            select: function (event, ui) {
                                jQuery('#uaco').prepend('<span  class="btn btn-info btn-sm" id="uaco-' + ui.item.value.replace(/[^a-zA-Z]/ig, '-') + '"><input type="hidden" name="__wpdmcategory[user_access][]" value="' + ui.item.value + '" /> <a class="uaco-del" onclick="jQuery(this.rel).remove()" rel="#uaco-' + ui.item.value.replace(/[^a-zA-Z]/ig, '-') + '"><i class="far fa-times-circle"></i></a>&nbsp;' + ui.item.value + '</span>');
                                this.value = "";
                                return false;
                            }
                        });
                });

            </script>

            <style>
                #uaco > span {
                    line-height: 16px;
                    border-radius: 3px;
                    display: inline-block;
                    margin-right: 5px;
                    margin-bottom: 5px;
                }

                #uaco > span a {
                    border-right: 1px dotted #fff;
                    margin-right: 4px;
                }

                #uaco > span .far {
                    margin-right: 6px;
                    cursor: pointer;
                    color: #ffffff;
                    -webkit-transition: ease-in-out 300ms;
                    -moz-transition: ease-in-out 300ms;
                    -ms-transition: ease-in-out 300ms;
                    -o-transition: ease-in-out 300ms;
                    transition: ease-in-out 300ms;
                }

                #uaco > span .far:hover {
                    transform: scale(1.2);
                }

                .form-control.ui-autocomplete-input {
                    background-position: 125px center !important;
                }

                .ui-autocomplete.ui-front.ui-menu.ui-widget.ui-widget-content {
                    max-height: 300px;
                    overflow: auto;
                }

                .ui-autocomplete.ui-front.ui-menu.ui-widget.ui-widget-content li:not(:last-child) {
                    border-bottom: 1px solid #dddddd !important;
                    margin-top: -1px;
                }

                .ui-autocomplete.ui-front.ui-menu.ui-widget.ui-widget-content li {
                    padding: 7px 10px;
                    width: calc(100% - 22px);
                }

                .ui-autocomplete.ui-front.ui-menu.ui-widget.ui-widget-content li.ui-state-focus {
                    border: 1px solid #aaaaaa !important;
                }
            </style>


            <?php
        }

        function csvImport($value, $ID)
        {
            return explode(",", $value);
        }

        function metaBox($metaboxes)
        {
            $metaboxes['advanced-access-control'] = array('title' => __('Who Can Download?', 'wpdm-advanced-access-control'), 'callback' => array($this, 'accessControlOption'), 'position' => 'normal', 'priority' => 'core');
            return $metaboxes;
        }

        function accessControlOption()
        {
            echo "OK";
        }

        function accessControlOptionPanel()
        {
            echo "OK";
        }
    }


    new AdvancedAccessControl();


//add_filter("wpdm_check_lock", "wpdm_cal_check_lock", 10, 2);
    add_filter("wdm_before_fetch_template", "wpdm_block_dllink", 10, 3);
    add_shortcode('wpdm_my_downloads', 'wpdm_cal_my_downloads');
    add_filter("before_download", 'wpdm_cal_check_permission');
    add_filter("wpdm_custom_data", 'wpdm_cal_custom_data');
    add_filter("wpdm_embed_category_query_params", 'wpdm_cal_category_query_params');


    add_filter("update_plugins_wpdm-advanced-access-control", function ($update, $plugin_data, $plugin_file, $locales){
        $id = 'wpdm-advanced-access-control';
        $latest_versions = WPDM()->updater->getLatestVersions();
        $latest_version = wpdm_valueof($latest_versions, $id);
        $access_token = wpdm_access_token();
        $update = [];
        $update['id']           = $id;
        $update['slug']         = $id;
        $update['url']          = wpdm_valueof($plugin_data, 'PluginURI');
        $update['tested']       = true;
        $update['version']      = $latest_version;
        $update['package'] = $access_token !== '' ? "https://www.wpdownloadmanager.com/?wpdmpp_file={$id}.zip&access_token={$access_token}" : '';
        return $update;
    }, 10, 4);


}
