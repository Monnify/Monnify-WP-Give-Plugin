<?php defined( 'ABSPATH' ) or exit; ?>

<strong>
	<?php _e( 'Activation Error:', 'give-monnify' ); ?>
</strong>
<?php _e( 'You must have', 'give-monnify' ); ?> <a href="https://givewp.com" target="_blank">Give</a>
<?php _e( 'version', 'give-monnify' ); ?> <?php echo GIVE_MONNIFY_MIN_GIVE_VERSION; ?>+
<?php printf( esc_html__( 'for the %1$s add-on to activate', 'give-monnify' ), GIVE_MONNIFY_NAME ); ?>.
