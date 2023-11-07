<?php global $current_user; ?>
<script src="<?php echo plugins_url('download-manager/assets/js/jquery.dataTables.min.js'); ?>"></script>
<script src="<?php echo WPDM_BASE_URL.'assets/js/dataTables.bootstrap4.min.js' ?>"></script>
<link rel="stylesheet" href="<?php echo plugins_url('download-manager/assets/css/jquery.dataTables.css'); ?>" type="text/css" media="all" />
<div class="w3eden">
    <div class="card mb-3">
        <div class="panel-heading card-header">
            <div class="pull-right"><a href="<?php echo wp_logout_url(get_permalink()); ?>"><?php _e('Logout','wpdm-advanced-access-control'); ?></a></div>
            <b><?php echo sprintf(__('Welcome, %s', 'wpdm-advanced-access-control'), $current_user->display_name); ?></b></div>
        <div class="panel-body card-body">
            <?php echo sprintf(__('%d Downloads are shared with you', 'wpdm-advanced-access-control'), count($files)); ?>
        </div>
    </div>

    <?php do_action("wpdm_my_downloads_table_before"); ?>
<div class="row">
    <?php if(wpdm_valueof($params, 'cats', 'int') === 1 && count($categories) > 0){ ?>
    <div class="col-md-3">

        <ul class="cat-nav">
            <li><a href="#" class="my-category" data-category="all"><i class="fas fa-layer-group cat-icon color-purple"></i> <?php _e('All Downloads', 'wpdm-advanced-access-control'); ?></a></li>
            <?php

                foreach ($categories as $category){
                    $icon = \WPDM\Category\CategoryController::icon($category->term_id);
                    $icon = $icon?"<img class='cat-icon' style='width: 13px' src='{$icon}' />":"<i class='fas fa-folder color-blue cat-icon'></i>";
                    ?>
                    <li><a href="#" class="my-category" data-category="<?php echo $category->slug; ?>"><?php echo $icon; ?> <?php echo $category->name; ?></a></li>
                    <?php
                }

            ?>
        </ul>

    </div>
    <?php } ?>
    <div class="col-md-<?php echo (wpdm_valueof($params, 'cats', 'int') === 1 && count($categories) > 0)?9:12; ?>">

        <div class="card">

                <?php $tblid = uniqid(); ?>
                <table id="wpdmmydls-<?php echo $tblid; ?>" style="width: 100%;" class="dtable table table-striped">
                    <thead>
                    <tr class="fetfont">
                        <th><?php _e('Title','wpdm-advanced-access-control'); ?></th>
                        <th class="file-size" style="width: 100px;text-align: right"><?php _e('File Size','wpdm-advanced-access-control'); ?></th>
                        <th  class="download-button" style="width: 100px;text-align: right"><?php _e('Download','wpdm-advanced-access-control'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if($files) {
                        foreach($files as $file):
                            $terms = wp_get_post_terms( $file->ID, "wpdmcategory" );
                            $classes = array();
                        foreach ($terms as $term){
                            $classes[] = $term->slug;
                        }
                        $label = __('Download', 'wpdm-advanced-access-control');
                        ?>
                        <tr class="cat-row all <?php echo implode(" ", $classes); ?>">
                            <td><nobr><a href='<?php echo get_permalink($file); ?>'><strong><?php echo $file->post_title; ?></strong></a> <small class="text-muted"> &mdash; <?php echo $fc = WPDM()->package->fileCount($file->ID) ?> file<?php echo $fc > 1?'s':''; ?></small></nobr></td>
                            <td class="file-size" style="width: 100px;text-align: right"><?php echo wpdm_package_size($file->ID); ?></td>
                            <td class="download-button" style="width: 100px;text-align: right">
                                <?php
                                if(!WPDM()->package->userDownloadLimitExceeded($file->ID)) {
                                    if (WPDM()->package->isLocked($file->ID)) { ?>
                                        <a class="btn btn-info btn-xs" href="<?php echo get_permalink($file); ?>"><?php echo $label; ?></a>
                                    <?php } else { ?>
                                        <a class="btn btn-info btn-xs" href="<?php echo home_url("/?wpdmdl=" . $file->ID); ?>"><?php echo $label; ?></a>
                                    <?php }
                                } else { ?>
                                    <button type="button" class="btn btn-danger btn-xs" disabled ><?php _e('Limit Over!', 'wpdm-advanced-access-control'); ?></button>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php
                        endforeach; wp_reset_query(); } else { echo "<tr><td colspan='3'>". __('Sorry! No files shared for you.', 'wpdm-advanced-access-control')."</td></tr>"; } ?>
                    </tbody>
                </table>

        </div>

    </div>
</div>


    <?php do_action("wpdm_my_downloads_table_after"); ?>

</div>
<style>
    .w3eden .cat-nav, .cat-nav li{
        margin: 0 !important;
        padding: 0;
    }
    .w3eden .cat-nav li{
        list-style: none;
        margin-bottom: 5px;
        font-size: 12px;
    }
    .w3eden .cat-nav li a{
        line-height: 24px;
        display: block;
        height: 24px;
        text-decoration: none;
        -webkit-transition: ease-in-out 300ms;
        -moz-transition: ease-in-out 300ms;
        -ms-transition: ease-in-out 300ms;
        -o-transition: ease-in-out 300ms;
        transition: ease-in-out 300ms;
        color: rgba(69, 89, 122, 0.67);
        font-weight: 600;
    }
    .w3eden .cat-nav li a.active,
    .w3eden .cat-nav li a:hover{
        color: #0aa3f3;
        text-decoration: none;
        outline: none !important;
    }
    .w3eden .cat-nav li a .cat-icon{
        margin-right: 5px;
        box-shadow: none;
    }
    table,td,th{
        border: 0;
    }
    #wpdmmydls-<?php echo $tblid; ?>{
        border-bottom: 1px solid #dddddd;
        border-top: 3px solid #bbb;
        font-size: 10pt;
        min-width: 100%;
        margin: 0;
    }
    #wpdmmydls-<?php echo $tblid; ?> td:first-child, #wpdmmydls-<?php echo $tblid; ?> th:first-child{
        text-align: left !important;
    }

    #wpdmmydls-<?php echo $tblid; ?> .wpdm-download-link img{
        box-shadow: none !important;
        max-width: 100%;
    }
    .w3eden .pagination{
        margin: 0 !important;
    }
    #wpdmmydls-<?php echo $tblid; ?> td:not(:first-child){
        vertical-align: middle !important;
    }
    #wpdmmydls-<?php echo $tblid; ?> td.__dt_col_download_link .btn{
        display: block;
        width: 100%;
    }
    #wpdmmydls-<?php echo $tblid; ?> td.__dt_col_download_link,
    #wpdmmydls-<?php echo $tblid; ?> th#download_link{
        max-width: 100px !important;
        width: 100px;
        text-align: right !important;

    }
    #wpdmmydls-<?php echo $tblid; ?> th{
        background-color: #e8e8e8;
        border-bottom: 0;
    }

    #wpdmmydls-<?php echo $tblid; ?>_filter input[type=search],
    #wpdmmydls-<?php echo $tblid; ?>_length select{
        padding: 5px !important;
        border-radius: 3px !important;
        border: 1px solid #dddddd !important;
        display: inline-block;
        width: 200px;
    }

    #wpdmmydls-<?php echo $tblid; ?> .package-title{
        color:#36597C;
        font-size: 11pt;
        font-weight: 700;
    }
    #wpdmmydls-<?php echo $tblid; ?> .small-txt{
        margin-right: 7px;
    }
    #wpdmmydls-<?php echo $tblid; ?> td{
        min-width: 150px;
        border-bottom: 0;
    }

    #wpdmmydls-<?php echo $tblid; ?> tr th.download-button,
    #wpdmmydls-<?php echo $tblid; ?> tr td.download-button{
        max-width: 100px !important;
        width: 100px !important;
    }


    #wpdmmydls-<?php echo $tblid; ?> .small-txt,
    #wpdmmydls-<?php echo $tblid; ?> small{
        font-size: 9pt;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:active,
    .dataTables_wrapper .dataTables_paginate .paginate_button:focus,
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover,
    .dataTables_wrapper .dataTables_paginate .paginate_button{
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
    }

    #wpdmmydls-<?php echo $tblid; ?>_length select.wpdm-custom-select{
        width: 65px !important;
        padding: 0 0 0 5px !important;
    }


    @media (max-width: 799px) {
        #wpdmmydls-<?php echo $tblid; ?> tr {
            display: block;
            border: 3px solid rgba(0,0,0,0.3) !important;
            margin-bottom: 10px !important;
            position: relative;
        }
        #wpdmmydls-<?php echo $tblid; ?> thead{
            display: none;
        }
        #wpdmmydls-<?php echo $tblid; ?>,
        #wpdmmydls-<?php echo $tblid; ?> td:first-child {
            border: 0 !important;
        }
        #wpdmmydls-<?php echo $tblid; ?> td {
            display: block;
        }
        #wpdmmydls-<?php echo $tblid; ?> td.__dt_col_download_link {
            display: block;
            max-width: 100% !important;
            width: auto !important;

        }
    }


    .dataTables_info,.dataTables_length,.dataTables_filter{ width: 100%; }
    .dataTables_length select{
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background: #ffffff url("<?php echo plugins_url('download-manager/assets/images/sort.svg'); ?>") calc(100% - 6px) center no-repeat !important;
        background-size: 10px !important;
        cursor: pointer;
        width: 50px !important;
        padding: 0 10px !important;
        height: 30px;
        line-height: 18px;
        font-size: 10px;
    }
    .dataTables_filter input{
        padding: 0 10px !important;
        height: 30px;
        line-height: 18px;
        font-size: 10px;
    }
    .dataTables_wrapper .dataTables_info{
        padding: 0;
    }
    .dataTables_wrapper > .row{
        margin: 0;
        padding: 10px 0 7px;
        font-size: 11px;
        font-weight: normal;
    }
    .table + .row{
        margin: 0;
        padding: 10px 0;
        border-top: 1px solid #ddd;
        font-size: 11px;
    }
</style>
<script charset="utf-8">
    /* Default class modification
    jQuery.extend( jQuery.fn.dataTableExt.oStdClasses, {
        "sSortAsc": "header headerSortDown",
        "sSortDesc": "header headerSortUp",
        "sFilterInput":  "form-control form-control-sm",
        "sLengthSelect": "form-control wpdm-custom-select",
        "sSortable": "header"
    } );
    */

    jQuery.fn.dataTable.ext.type.order['file-size-pre'] = function ( data ) {
        var matches = data.match( /^(\d+(?:\.\d+)?)\s*([a-z]+)/i );
        var multipliers = {
            b: 1,
            kb: 1000,
            mb: 1000000,
            gb: 1000000000,
        };

        if (matches) {
            var multiplier = multipliers[matches[2].toLowerCase()];
            return parseFloat( matches[1] ) * multiplier;
        } else {
            return -1;
        }
    };

    jQuery(function($) {


        $('#wpdmmydls-<?php echo $tblid;?>').dataTable({
            responsive: true,
            "order": [[ 0, "asc" ]],
            "language": {
                "lengthMenu": "<?php _e("Display _MENU_ downloads per page",'wpdm-advanced-access-control')?>",
                "zeroRecords": "<?php _e("Nothing _START_ to - sorry",'wpdm-advanced-access-control')?>",
                "info": "<?php _e("Showing _START_ to _END_ of _TOTAL_ downloads",'wpdm-advanced-access-control')?>",
                "infoEmpty": "<?php _e("No downloads available",'wpdm-advanced-access-control')?>",
                "infoFiltered": "<?php _e("(filtered from _MAX_ total downloads)",'wpdm-advanced-access-control');?>",
                "emptyTable":     "<?php _e("No data available in table",'wpdm-advanced-access-control');?>",
                "infoPostFix":    "",
                "thousands":      ",",
                "loadingRecords": "<?php _e("Loading...",'wpdm-advanced-access-control'); ?>",
                "processing":     "<?php _e("Processing...",'wpdm-advanced-access-control'); ?>",
                "search":         "<?php _e("Search:",'wpdm-advanced-access-control'); ?>",
                "paginate": {
                    "first":      "<?php _e("First",'wpdm-advanced-access-control'); ?>",
                    "last":       "<?php _e("Last",'wpdm-advanced-access-control'); ?>",
                    "next":       "<?php _e("Next",'wpdm-advanced-access-control'); ?>",
                    "previous":   "<?php _e("Previous",'wpdm-advanced-access-control'); ?>"
                },
                "aria": {
                    "sortAscending":  " : <?php _e("activate to sort column ascending",'wpdm-advanced-access-control'); ?>",
                    "sortDescending": ": <?php _e("activate to sort column descending",'wpdm-advanced-access-control'); ?>"
                }
            },
            "iDisplayLength": 25,
            "aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "<?php _e("All",'wpdm-advanced-access-control'); ?>"]]
        });

        $('.my-category').on('click', function (e) {
            e.preventDefault();
            $('.my-category').removeClass('active');
            var cat = $(this).data('category');
            console.log(cat);
            $('.cat-row').hide();
            $('.' + cat).show();
            $(this).addClass('active');
        });

    } );
</script>
