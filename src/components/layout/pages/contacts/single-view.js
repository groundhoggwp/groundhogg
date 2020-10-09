import { Fragment } from '@wordpress/element'
import {
	useParams
} from "react-router-dom";

export const SingleView = ( props ) => {
	let { id } = useParams();
	return (
		<Fragment>
			<p>Single view: Contact ID: {id}</p>
		</Fragment>
	)
}
