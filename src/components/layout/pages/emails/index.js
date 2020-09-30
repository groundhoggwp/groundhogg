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
import SearchInput from '../../../core-ui/search-input';

/**
 * Internal dependencies
 */
import { EMAILS_STORE_NAME } from '../../../../data';


class Emails extends Component {

	constructor() {
		super( ...arguments );
		// this.onSubmit = this.onSubmit.bind( this );
		// this.setValue = this.setValue.bind( this );
		this.state = {
			tagValue : '',
			tags : []
		};
	}

	// setValue( event ) {
	// 	this.setState( {
	// 		tagValue : event.target.value
	// 	} )
	// }
	//
	// async onSubmit() {
	// 	const {
	// 		tagValue
	// 	} = this.state;
	//
	// 	const {
	// 		updateTags,
	// 		tags
	// 	} = this.props;
	//
	// 	if ( tags.tags.length ) {
	// 		this.setState( { tags : tags } );
	// 	}
	//
	// 	const updatingTags = updateTags( { tags : tagValue } )
	//
	// 	console.log(updatingTags);
	//
	// 	this.setState( { tags : updatingTags } );
	// }

	render() {

		// const { isUpdateRequesting } = this.props;
		const emails = castArray( this.props.emails );

		console.log(emails, this.props)

		return (
				<Fragment>
					<h2>Email</h2>

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

		// const isUpdateRequesting = isTagsUpdating();
		const emails = getEmails();
		console.log(emails)
		return {
			emails,
			// isUpdateRequesting
		};
	} ),
	// withDispatch( ( dispatch ) => {
	// 	const { updateTags } = dispatch( TAGS_STORE_NAME );
	// 	return {
	// 		updateTags
	// 	};
	// } )
)( Emails );
