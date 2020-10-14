/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

//TODO: Get closer to edit-post
export default function Header( { email } ) {

	if ( ! email ) {
		return null;
	}

	return (
		<div
			className="groundhogg-header"
			role="region"
			aria-label={ __( 'Email Editor top bar.', 'groundhogg' ) }
			tabIndex="-1"
		>
			<h1 className="groundhogg-header__title">
				{ __( 'Edit ', 'groundhogg' ) + email.data.title }
			</h1>
		</div>
	);
}