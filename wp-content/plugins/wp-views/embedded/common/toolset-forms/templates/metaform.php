<div class="js-wpt-field wpt-field wpt-<?php echo $cfg['type']; ?><?php if ( @$cfg['repetitive'] ) echo ' js-wpt-repetitive wpt-repetitive'; ?><?php do_action('wptoolset_field_class', $cfg); ?>" data-wpt-type="<?php echo $cfg['type']; ?>" data-wpt-id="<?php echo $cfg['id']; ?>" style="<?php do_action('wptoolset_field_style', $cfg); ?>">
    <div class="js-wpt-field-items">
    <?php foreach ( $html as $out ): include 'metaform-item.php';
    endforeach; ?>
    </div>
    <?php if ( @$cfg['repetitive'] ): ?>
        <a href="#" class="js-wpt-repadd wpt-repadd button-primary"><?php _e('Add new field'); ?></a>
<?php endif; ?>
</div>