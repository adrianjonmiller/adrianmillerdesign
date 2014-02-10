<div class="wpv-dialog wpv-dialog-layout-wizard js-wpv-dialog-layout-wizard js-wvp-wizard-loc" data-loc-error="<?php _e('Can\'t insert content in to shortcode') ?>" data-loc-error2="<?php _e('Error occured') ?>" data-loc-insert="<?php _e('Insert') ?>" data-loc-next="<?php _e('Next') ?>">
    <div class="wpv-dialog-header">
        <h2><?php _e('Insert a layout','wpv-views'); ?></h2>
        <i class="icon-remove js-dialog-close"></i>
    </div>

    <ul class="wpv-dialog-nav js-layout-wizard-nav">
        <li class="wpv-dialog-nav-tab">
            <a href="#js-layout-wizard-layout-style" class="active"><?php _e('Layout style','wpv-views') ?></a>
        </li>
        <li class="wpv-dialog-nav-tab">
            <a href="#js-layout-wizard-fields" class="js-tab-not-visited"><?php _e('Choose fields','wpv-views') ?></a>
        </li>
        <li class="wpv-dialog-nav-tab">
            <a href="#js-layout-wizard-insert" class="js-tab-not-visited"><?php _e('Insert to the view','wpv-views') ?></a>
        </li>
    </ul>

    <div class="wpv-dialog-content">

        <div class="wpv-dialog-content-tabs">

            <div class="wpv-dialog-content-tab js-layout-wizard-tab" id="js-layout-wizard-layout-style">
                <h2><?php _e('Select the style of the layout to insert','wpv-views'); ?></h2>
                <ul class="layout-wizard-layout-style">
                    <li>
                        <input type="radio" name="layout-wizard-style" id="layout-wizard-style-unformatted" value="unformatted" />
                        <label for="layout-wizard-style-unformatted">
                            <i class="icon-code"></i>
                         <?php _e('Unformatted','wpv-views'); ?>
                        </label>
                    </li>
                    <li>
                        <input type="radio" name="layout-wizard-style" id="layout-wizard-style-grid" value="table" />
                        <label for="layout-wizard-style-grid">
                            <i class="icon-th"></i>
                            <?php _e('Grid','wpv-views'); ?>
                        </label>

                        <span style="float: right; display: none;" class="js-layout-wizard-num-columns">
                        <?php _e('Number of columns','wpv-views'); ?>:
                        <select name="table_cols">
                            <?php
                                for($i = 2; $i < 11; $i++) {
                                    echo '<option value="'.$i.'">'.$i.'</option>';
                                }
                            ?>
                        </select>
                        </span>
                    </li>
                    <li>
                        <input type="radio" name="layout-wizard-style" id="layout-wizard-style-table" value="table_of_fields" />
                        <label for="layout-wizard-style-table">
                            <i class="icon-table"></i>
                            <?php _e('Table','wpv-views'); ?>
                        </label>
                        <span style="float: right; display: none;" class="js-layout-wizard-include-fields-names">
                            <input id="include_field_names" type="checkbox" name="include_field_names" />
                            <?php _e('Include field names in table headings','wpv-views'); ?>
                        </span>
                    </li>
                    <li>
                        <input type="radio" name="layout-wizard-style" id="layout-wizard-style-ul" value="un_ordered_list" />
                        <label for="layout-wizard-style-ul">
                            <i class="icon-list-ul"></i>
                            <?php _e('Unordered list','wpv-views'); ?>
                        </label>
                    </li>
                    <li>
                         <input type="radio" name="layout-wizard-style" id="layout-wizard-style-ol" value="ordered_list" />
                         <label for="layout-wizard-style-ol">
                            <i class="icon-list-ol"></i>
                            <?php _e('Ordered list','wpv-views'); ?>
                        </label>
                    </li>
                </ul>
            </div>

            <div class="wpv-dialog-content-tab js-layout-wizard-tab" id="js-layout-wizard-fields">
                <h2><?php _e('Select the fields to include in the layout','wpv-views'); ?></h2>
                <ul class="layout-wizard-layout-fields">

                </ul>

                <p>
                    <button class="button button-secondary js-layout-wizard-add-field">
                        <i class="icon-plus"></i> <?php _e('Add field','wpv-views') ?>
                    </button>
                </p>
            </div>

            <div class="wpv-dialog-content-tab js-layout-wizard-tab" id="js-layout-wizard-insert">
                <h2><?php _e('Where do you want to insert this layout?','wpv-views'); ?></h2>
                <ul>
                    <li>
                        <input type="radio" name="layout-wizard-insert" id="layout-wizard-insert-cursor" value="insert_cursor" />
                        <label for="layout-wizard-insert-cursor"><?php _e('In the current cursor position','wpv-views'); ?></label>
                    </li>
                    <li>
                        <input type="radio" name="layout-wizard-insert" id="layout-wizard-insert-replace" value="insert_replace" />
                        <label for="layout-wizard-insert-replace"><?php _e('Replace existing layout','wpv-views'); ?></label>
                    </li>
                </ul>
            </div>

            <?php wp_nonce_field('layout_wizard_nonce', 'layout_wizard_nonce'); ?>
        </div>

    </div>

    <div class="wpv-dialog-footer">
        <button class="button js-dialog-close"><?php _e('Cancel','wpv-views') ?></button>
        <button class="button js-dialog-prev"><?php _e('Previous','wpv-views') ?></button>
        <button class="button button-primary js-insert-layout" data-nonce="<?php echo wp_create_nonce( 'wpv_view_layout_extra_nonce' ); ?>" disabled><?php _e('Next','wpv-views') ?></button>
    </div>

</div>