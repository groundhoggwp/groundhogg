
/**
 * External dependencies
 */
import Button from '@material-ui/core/Button'
import TextField from '@material-ui/core/TextField'

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, Fragment } from '@wordpress/element';
import {
	PinnedItems,
} from '@wordpress/interface';

/**
 * Internal dependencies
 */
import {
	CORE_STORE_NAME,
	EMAILS_STORE_NAME
} from 'data';
import HeaderToolbar from './header-toolbar';
import HeaderPrimary from './header-primary';
import HeaderSecondary from './header-secondary';
import { Spinner } from  'components';

export default function Header( { email } ) {
	const dispatch = useDispatch( EMAILS_STORE_NAME );

	const [ titleToggle, setTitleToggle ] = useState( false );

	const onClick = () => true;

	const toggleTitleEdit = () => {
		setTitleToggle( ! titleToggle )
	}

	const {
		isSaving,
		item
	} = useSelect(
		( select ) => ( {
				isSaving: select( CORE_STORE_NAME ).isItemsUpdating(),
				item: select( EMAILS_STORE_NAME ).getItem( email.ID ),
			} ),
		[]
	);

	const handleTitle = (e) => {
		dispatch.updateItem( email.ID, { data: { title : e.target.value } } );
		toggleTitleEdit();
	}

	if ( ! item.hasOwnProperty( 'ID' ) ) {
		return null;
	}

	return (
		<Fragment>
			<div
				className="groundhogg-header primary-header edit-post-header"
				role="region"
				aria-label={ __( 'Email Editor primary top bar.', 'groundhogg' ) }
				tabIndex="-1"
			>
				<div className="groundhogg-header__toolbar edit-post-header__toolbar">
					<HeaderToolbar>
						<div className="groundhogg-header-toolbar__left edit-post-header-toolbar__left">
							<HeaderPrimary />
							<h1 className="groundhogg-header__title">
								{ ! titleToggle &&
									<span onClick={ toggleTitleEdit }>
										{ __( 'Now Editing  ', 'groundhogg' ) + item.data.title }
									</span>
								}
								{ titleToggle &&
									<TextField defaultValue={ item.data.title } onBlur={ handleTitle } />
								}
							</h1>
							<div className="groundhogg-header__settings edit-post-header__settings">
								{ isSaving && <Spinner /> }
								<Button onClick={onClick} variant="contained" color="secondary">{ __( 'Save Draft' ) }</Button>
								<Button onClick={onClick} variant="contained" color="primary">{ __( 'Publish' ) }</Button>
								<Button onClick={onClick} variant="contained" color="secondary">{ __( 'Close' ) }</Button>
								<PinnedItems.Slot scope="gh/v4/core" />
							</div>
						</div>
					</HeaderToolbar>
				</div>
			</div>
		<div
			className="groundhogg-header secondary-header edit-post-header"
			role="region"
			aria-label={ __( 'Email Editor secondary top bar.', 'groundhogg' ) }
			tabIndex="-1"
		>
			<HeaderToolbar>
				<div className="groundhogg-header-toolbar__left edit-post-header-toolbar__left">
					<HeaderSecondary />
				</div>
			</HeaderToolbar>
		</div>
		</Fragment>
	);
}
