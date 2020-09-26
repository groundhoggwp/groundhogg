/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TAGS_STORE_NAME } from '../../../../data';

class Dashboard extends Component {

	render() {
		const { tags, isTagsUpdating, isUpdateRequesting } = this.props;
		console.log( 'props' );
		console.log( tags, isTagsUpdating, isUpdateRequesting );
		return ( <p>Dashboard!</p> );
	}
}

// default export
export default compose(
	withSelect( ( select ) => {
		const { getTag, isTagsUpdating } = select( TAGS_STORE_NAME );
		const isUpdateRequesting = isTagsUpdating();
		const tags = getTag();

		return { tags, isTagsUpdating, isUpdateRequesting };
	} ),
	withDispatch( ( dispatch ) => {
		const { updateTags } = dispatch( TAGS_STORE_NAME );
		return {
			updateTags,
		};
	} )
)( Dashboard );
