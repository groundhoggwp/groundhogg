import Snackbar from "@material-ui/core/Snackbar";
import MuiAlert from '@material-ui/lab/Alert';
import {
	useSelect,
	useDispatch
} from '@wordpress/data'
import { CORE_STORE_NAME } from '../../data'

const Alert = (props) => {
	return <MuiAlert elevation={6} variant="filled" {...props} />;
}

export const SnackbarArea = () => {
	const { message, severity, isOpen } = useSelect( (select) => {
		const store = select( CORE_STORE_NAME );

		return {
			message: store.getSnackbarMessage(),
			severity: store.getSnackbarSeverity(),
			isOpen: store.getSnackbarMenuOpen()
		}
	} );

	const { clearSnackbar } = useDispatch( CORE_STORE_NAME );

	function handleClose() {
		clearSnackbar();
	}

  return (
    <Snackbar
      anchorOrigin={{
        vertical: "bottom",
        horizontal: "left"
      }}
      open={isOpen}
      autoHideDuration={6000}
      onClose={handleClose}
    >
		<Alert onClose={handleClose} severity={severity}>{ message }</Alert>
	</Snackbar>
  );
}