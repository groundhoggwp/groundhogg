
/**
 * External dependencies
 */
import Button from '@material-ui/core/Button'
import TextField from '@material-ui/core/TextField'

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import {
	PinnedItems,
} from '@wordpress/interface';

/**
 * Internal dependencies
 */

import HeaderToolbar from './header-toolbar';
// import HeaderPrimary from './header-primary';<HeaderPrimary />
import HeaderSecondary from './header-secondary';
import { Spinner } from  'components';

export default function Header( { email, history, saveDraft, publishEmail, closeEditor, isSaving, handleTitleChange, title } ) {
	return (
		<Fragment>
			<div
				className="groundhogg-header primary-header edit-post-header"
				role="region"
				aria-label={ __( 'Email Editor primary top bar.', 'groundhogg' ) }
				tabIndex="-1"
			>
				<HeaderToolbar>
						<h1 className="groundhogg-header__title">
							<form noValidate autoComplete="off">
								<TextField className="groundhogg-header__title" label="Email Title" value={ title } onChange={ handleTitleChange } />
							</form>
						</h1>
						<div className="groundhogg-header__settings edit-post-header__settings">
							{ isSaving && <Spinner /> }
							<Button onClick={saveDraft} variant="contained" color="secondary">{ __( 'Save Draft' ) }</Button>
							<Button onClick={publishEmail} variant="contained" color="primary">{ __( 'Publish' ) }</Button>
							<Button onClick={closeEditor} variant="contained" color="secondary">{ __( 'Close' ) }</Button>
							<PinnedItems.Slot scope="gh/v4/core" />
						</div>
				</HeaderToolbar>
			</div>
		<div
			className="groundhogg-header secondary-header edit-post-header"
			role="region"
			aria-label={ __( 'Email Editor secondary top bar.', 'groundhogg' ) }
			tabIndex="-1"
		>
			<HeaderToolbar>
				<HeaderSecondary />
			</HeaderToolbar>
		</div>
		</Fragment>
	);
}
