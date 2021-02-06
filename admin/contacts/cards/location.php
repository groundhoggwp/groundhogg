<?php
use function Groundhogg\get_date_time_format;
?>
<div class="local-time" style="float: right; padding: 10px;font-size: 18px;">
	<?php _ex( 'Local Time:', 'groundhogg' ); ?>
    <span style="font-family: Georgia, Times New Roman, Bitstream Charter, Times, serif;font-weight: 400;"><?php echo date_i18n( get_date_time_format(), $contact->get_local_time() ); ?>
        </span>
</div>

