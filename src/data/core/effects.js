/**
 * WordPress dependencies
 */
import { dispatch } from '@wordpress/data';
import { speak } from '@wordpress/a11y';
import { __ } from '@wordpress/i18n';

const effects = {

	SWITCH_MODE( action ) {
		// Unselect blocks when we switch to the code editor.
		if ( action.mode !== 'visual' ) {
			dispatch( 'core/block-editor' ).clearSelectedBlock();
		}

		const message =
			action.mode === 'visual'
				? __( 'Visual editor selected' )
				: __( 'Code editor selected' );
		speak( message, 'assertive' );
	},
};

export default effects;