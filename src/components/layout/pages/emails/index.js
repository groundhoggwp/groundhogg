/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import TextField from '@material-ui/core/TextField';
import { castArray } from 'lodash';
import Spinner from '../../../core-ui/spinner';

/**
 * Internal dependencies
 */
import { EMAILS_STORE_NAME } from '../../../../data';

class Emails extends Component {

	constructor() {
		super( ...arguments );

		this.state = {
		};
	}

	render() {
		const emails = castArray( this.props.emails.emails );

		return (
				<Fragment>
					<ol>
					{
						emails.map( ( email ) => {
							return( <li>Email Title: {email.data.title}</li> )
						} )
					}
					</ol>

				</Fragment>
		);
	}
}
// default export
export default compose(
	withSelect( ( select ) => {
		const {
			getEmails,
			// isTagsUpdating
		} = select( EMAILS_STORE_NAME );

		const emails = getEmails();

		return {
			emails,
			// isUpdateRequesting
		};
	} )
	// } ),
	// withDispatch( ( dispatch ) => {
	// 	const { updateTags } = dispatch( EMAILS_STORE_NAME );
	// 	return {
	// 		updateTags
	// 	};
	// } )
)( Emails );
