/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { hasQueryArg } from '@wordpress/url'
import { makeStyles } from '@material-ui/core/styles';
import { Link } from "react-router-dom";



const useStyles = makeStyles((theme) => ({
	contactRowImage: {
		maxWidth: '55px',
		borderRadius: '50%',
		boxShadow: '1px 1px 4px #ccc',
	},
  }));

const getContactRowActions = () => {
	return 'SlotFill row actions here';
}

const getContactRowStatus = ( data ) => {
	if ( hasQueryArg( 'optin_status' ) ) {
		return null;
	}

	const statuses = {
		1 : __( 'Unconfirmed' ),
		2 : __( 'Confirmed' ),
		3 : __( 'Unsubscribed' ),
		4 : __( 'Weekly' ),
		5 : __( 'Monthly' ),
		6 : __( 'Hard Bounce' ),
		7 : __( 'Spam' ),
		8 : __( 'Complained' ),
	}

	return ` - ${statuses[data.optin_status]}`;
}

export const EmailRowPrimaryItem = ( props ) => {
	const classes = useStyles();
	const { data, ID } = props;
	console.log('data', ID)
	return (
		<Fragment>
			 <Link to={ `/emails/${ID}` }>
				 { data.title }
			 </Link>
			{ getContactRowStatus( data ) }
			<br />
			{ getContactRowActions() }
		</Fragment>
	)
}
