
/**
 * External dependencies
 */
import Button from '@material-ui/core/Button'

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
	PinnedItems,
} from '@wordpress/interface';

/**
 * Internal dependencies
 */
import HeaderToolbar from './header-toolbar';
import { CORE_STORE_NAME } from 'data/core';

export default function Header( { email } ) {

	const onClick = () => true;

	const {
		isSaving,
	} = useSelect(
		( select ) => ( {
				isSaving: select( CORE_STORE_NAME ).isItemsUpdating(),
			} ),
		[]
	);

	return (
		<div
			className="groundhogg-header edit-post-header"
			role="region"
			aria-label={ __( 'Email Editor top bar.', 'groundhogg' ) }
			tabIndex="-1"
		>
			<div className="groundhogg-header__toolbar edit-post-header__toolbar">
				<HeaderToolbar />
			</div>
			<div className="groundhogg-header__settings edit-post-header__settings">
				<Button onClick={onClick} variant="contained" color="primary">{ __( 'Publish' ) }</Button>
				<PinnedItems.Slot scope="gh/v4/core" />
			</div>
			<h1 className="groundhogg-header__title">
				{ __( 'Edit ', 'groundhogg' ) + email.data.title }
			</h1>
		</div>
	);
}
