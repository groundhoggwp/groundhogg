/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
// email > avatar, email, link,  status, actions,

export const ContactRowPrimaryItem = ( data ) => {
	console.log( data );
	return (
		<Fragment>
			{data.ID}
		</Fragment>
	)
}