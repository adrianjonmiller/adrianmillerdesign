<?php
// Incoming vars: $backupFile, $step
if ( ! current_user_can( pb_backupbuddy::$options['role_access'] ) ) {
	die( 'Error #473623. Access Denied.' );
}
//pb_backupbuddy::verify_nonce();
pb_backupbuddy::load_script( 'jquery' );


$restoreData = unserialize( base64_decode( pb_backupbuddy::_POST( 'restoreData' ) ) );
require_once( pb_backupbuddy::plugin_path() . '/classes/rollback.php' );
$rollback = new backupbuddy_rollback( $restoreData );

$status = $rollback->extractDatabase();
if ( false === $status ) {
	$errors = $rollback->getErrors();
	if ( count( $errors ) > 0 ) {
		pb_backupbuddy::alert( 'Errors were encountered: ' . implode( ', ', $errors ) . ' If seeking support please click to Show Advanced Details above and provide a copy of the log.' );
	}
	return;
}

$restoreData = $rollback->getState();
?>


<?php _e( 'Continuing to next step... You should be redirected momentarily.', 'it-l10n-backupbuddy' ); ?>
<br><br>


<script>
	pb_status_undourl( "<?php echo $restoreData['undoURL']; ?>" ); // Show undo URL.
	jQuery(document).ready(function() {
		jQuery( '#pb_backupbuddy_rollback_form' ).submit();
	});
</script>


<form id="pb_backupbuddy_rollback_form" method="post" action="?action=pb_backupbuddy_rollback&step=2&archive=<?php echo basename( $restoreData['archive'] ); ?>">
	<?php pb_backupbuddy::nonce(); ?>
	<input type="hidden" name="restoreData" value="<?php echo base64_encode( serialize( $restoreData ) ); ?>">
	<input type="submit" name="submitForm" class="button button-primary" value="<?php echo __('Next Step') . ' &raquo;'; ?>">
</form>
