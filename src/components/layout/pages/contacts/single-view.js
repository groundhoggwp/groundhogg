import { Fragment } from '@wordpress/element'
import {
	useParams
} from "react-router-dom";

export const SingleView = ( props ) => {
	let { id } = useParams();

	const {
		getItem,
		updateItem,
		deleteItem,
	} = props;

	return (
		<Fragment>
			<p>Single view: Contact ID: {id}</p>
		</Fragment>
	)
}
