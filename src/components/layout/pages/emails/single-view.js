/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element'
import {
	useParams
} from "react-router-dom";


/**
 * Internal dependencies
 */
 import BlockEditor from '../../../core-ui/block-editor';

export const SingleView = ( props ) => {
	let { id } = useParams();

	const {
		getItem,
		updateItem,
		deleteItem,
	} = props;

	return (
		<Fragment>
			<p>Single view: Email ID: {id}</p>
			<p>with gutenberg</p>
			<BlockEditor/>
		</Fragment>
	)
}
