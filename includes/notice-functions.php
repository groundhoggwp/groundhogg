<?php
/**
 * Created by PhpStorm.
 * User: Adrian
 * Date: 2018-09-08
 * Time: 1:21 PM
 */

function wpgh_notices()
{
	$notices = get_transient( 'wpgh_notices' );

	if ( ! $notices )
		return;

	foreach ( $notices as $notice ){
		?>
		<div id="<?php esc_attr_e( $notice['code'] ); ?>" class="notice notice-<?php esc_attr_e( $notice[ 'type' ] ); ?> is-dismissible"><p><strong><?php echo $notice[ 'message' ]; ?></strong></p></div>
		<?php
	}

	delete_transient( 'wpgh_notices' );
}

/**
 * @param $code
 * @param $message
 * @param string $type
 */
function wpgh_add_notice( $code, $message, $type='success' )
{
	$notices = get_transient( 'wpgh_notices' );

	if ( ! $notices || ! is_array( $notices ) )
	{
		$notices = array();
	}

	$notices[$code][ 'code' ]    = $code;
	$notices[$code][ 'message' ] = $message;
	$notices[$code][ 'type' ]    = $type;

	set_transient( 'wpgh_notices', $notices, 60 );
}

