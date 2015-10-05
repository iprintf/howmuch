<?php if (!defined('THINK_PATH')) exit(); if(($close_body_top) != "1"): ?><div class="body_top"><?php endif; ?>
    <?php if(($close_nav) != "1"): ?><div class="page-header body_top_nav <?php echo ($nav_class); ?>">
            <span class="text-muted kyo_header_text">
                <?php echo ($nav); ?>
            </span>
        </div><?php endif; ?>
    
    <?php if(($close_tool) != "1"): echo ($tool); endif; ?>
<?php if(($close_body_top) != "1"): ?></div><?php endif; ?>

<?php if(($close_top_ctrl) != "1"): if(($close_tool) != "1"): ?><div class="row top_ctrl_row hidden-xs">
            <button class="btn btn-default top_ctrl_up">
                <span class="glyphicon glyphicon-chevron-up"></span>
            </button>
        </div><?php endif; endif; ?>

<?php if(($close_data) != "1"): echo ($data); endif; ?>

<?php echo ($main_ext); ?>