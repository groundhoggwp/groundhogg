/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	NavigableToolbar,
} from '@wordpress/block-editor';

function HeaderToolbar( { children } ) {

	const displayBlockToolbar = true; // May connect to GH core state.

	const toolbarAriaLabel = displayBlockToolbar
		? /* translators: accessibility text for the editor toolbar when Top Toolbar is on */
			__( 'Document and block tools' )
		: /* translators: accessibility text for the editor toolbar when Top Toolbar is off */
			__( 'Document tools' );

	return (
		<NavigableToolbar
			className="groundhogg-header-toolbar edit-post-header-toolbar"
			aria-label={ toolbarAriaLabel }
		>
			{ children }
		</NavigableToolbar>
	);
}

export default HeaderToolbar;