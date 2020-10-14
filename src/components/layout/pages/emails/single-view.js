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

	const email = getItem( id );

	return (
		<Fragment>
			<BlockEditor email={email} />
		</Fragment>
	)
}
