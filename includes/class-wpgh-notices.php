<?php
/**
 * Class WPGH_Notices
 *
 * Add notices and display notices related to groundhogg.
 *
 */

class WPGH_Notices
{
    const TRANSIENT = 'wpgh_notices';

    /**
     * Add a notice
     *
     * @param $code string ID of the notice
     * @param $message string message
     * @param string $type
     */
    public function add( $code, $message, $type='success' )
    {
        $notices = get_transient( self::TRANSIENT );

        if ( ! $notices || ! is_array( $notices ) )
        {
            $notices = array();
        }

        $notices[$code][ 'code' ]    = $code;
        $notices[$code][ 'message' ] = $message;
        $notices[$code][ 'type' ]    = $type;

        set_transient( self::TRANSIENT, $notices, 60 );
    }

    /**
     * Get the notices
     */
    public function notices()
    {
        $notices = get_transient( self::TRANSIENT );

        if ( ! $notices )
            return;

        foreach ( $notices as $notice ){
            ?>
            <div id="<?php esc_attr_e( $notice['code'] ); ?>" class="notice notice-<?php esc_attr_e( $notice[ 'type' ] ); ?> is-dismissible"><p><strong><?php echo $notice[ 'message' ]; ?></strong></p></div>
            <?php
        }

        delete_transient( 'wpgh_notices' );
    }

}


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

