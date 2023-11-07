<?php global $current_user; ?>
<script src="<?php echo plugins_url('wpdm-advanced-access-control/js/loadmore.js'); ?>"></script>
<div class="w3eden">
    <div class="panel panel-default card mb-2">
        <div class="panel-heading card-header">
            <div class="pull-right"><a href="<?php echo wp_logout_url(get_permalink()); ?>"><?php _e('Logout','wpdmpro'); ?></a></div>
            <b><?php echo sprintf(__('Welcome, %s'), $current_user->display_name); ?></b></div>
        <div class="panel-body card-body">
            <?php echo sprintf(__('%d Downloads are shared with you'), count($files)); ?>
        </div>
    </div>

    <?php do_action("wpdm_my_downloads_table_before"); ?>
    <div class="row">
        <?php if(count($categories) > 0){ ?>
            <div class="col-md-3">

                <ul class="cat-nav">
                    <li><a href="#" class="my-category" data-category="all"><i class="fas fa-layer-group cat-icon color-purple"></i> All Downloads</a></li>
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
        <div class="col-md-<?php echo (count($categories) > 0)?9:12; ?>">


                    <?php $tblid = uniqid(); ?>

                    <?php
                    global $post;

                    if($files) {
                        echo "<div class='row'  id='wpdm-package-blocks'>";
                        $cols = isset( $cols ) && in_array( $cols, array(1,2,3,4) ) ? $cols : 1;
                        $cols = 12 / $cols;

                        foreach($files as $file):
                            $terms = wp_get_post_terms( $file->ID, "wpdmcategory" );
                            $classes = array();
                            foreach ($terms as $term){
                                $classes[] = $term->slug;
                            }

                            echo "<div class='col-md-{$cols} cat-block all ".implode(" ", $classes)."'>";
                            echo FetchTemplate($template, (array)$file);
                            echo "</div>";
                        endforeach;

                        echo "</div>";
                    } else {
                        echo "<div class='alert alert-warning'>Sorry! No files shared for you.</div>";
                    } ?>


        </div>
    </div>


    <?php do_action("wpdm_my_downloads_table_after"); ?>

</div>
<style>
    .w3eden .cat-nav, .cat-nav li{
        margin: 0;
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


    jQuery(function($) {


        $('#wpdm-package-blocks').loadmore({
            displayedItems: 9,
            showItems: 3,
            tag: {
                'name': 'div',
                'class': 'cat-block'
            },
            button: {
                'class': 'btn btn-primary',
                'text': 'Load More'
            }

        });

        $('.my-category').on('click', function (e) {
            e.preventDefault();
            $('.my-category').removeClass('active');
            var cat = $(this).data('category');
            console.log(cat);
            $('.cat-block').hide();
            $('.' + cat).show();
            $(this).addClass('active');
        });

    } );
</script>
