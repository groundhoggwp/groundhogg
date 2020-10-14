/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { makeStyles } from '@material-ui/core/styles'


const useStyles = makeStyles((theme) => ( {
  root: {
		marginTop: '50px'
  },
	h1:{
		fontSize: '24px'
	}
}));

//TODO: Get closer to edit-post
export default function Header( { email } ) {
	const classes = useStyles();
	if ( ! email ) {
		return null;
	}

	return (
		<div className={`${classes.root} groundhogg-header`}

			role="region"
			aria-label={ __( 'Email Editor top bar.', 'groundhogg' ) }
			tabIndex="-1"
		>
			<h1 className={`${classes.h1} groundhogg-header__title`}>
				{ __( 'Edit ', 'groundhogg' ) + email.data.title }
			</h1>
		</div>
	);
}
