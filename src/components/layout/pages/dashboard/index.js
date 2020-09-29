/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import TextField from '@material-ui/core/TextField';
import { castArray } from 'lodash';

/**
 * Internal dependencies
 */
import { TAGS_STORE_NAME } from '../../../../data';

class Dashboard extends Component {

	constructor() {
		super( ...arguments );
		this.onSubmit = this.onSubmit.bind( this );
		this.setValue = this.setValue.bind( this );
		this.state = { value : '' };
	}

	setValue( event ) {
		this.setState( {
			value : event.target.value
		} )
	}

	async onSubmit() {
		const {
			value
		} = this.state;

		const {
			updateTags
		} = this.props;

		updateTags( { tags : value } )
	}

	render() {

		const tags = castArray( this.props.tags.tags );

		return (
				<Fragment>
					<h2>Dashbosard</h2>
					<ol>
					{
						tags.map( ( tag ) => {
							return( <li>{tag.tag_name}</li> )
						} )
					}
					</ol>
					<TextField id="outlined-basic" label="Add sTags" variant="outlined" onKeyUp={ this.setValue } />
					<p onClick={this.onSubmit}>Add</p>
				</Fragment>
		);
	}
}

// default export
export default compose(
	withSelect( ( select ) => {
		const {
			getTags,
			isTagsUpdating
		} = select( TAGS_STORE_NAME );

		const isUpdateRequesting = isTagsUpdating();
		const tags = getTags();

		return {
			tags,
			isUpdateRequesting
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateTags } = dispatch( TAGS_STORE_NAME );
		return {
			updateTags
		};
	} )
)( Dashboard );
