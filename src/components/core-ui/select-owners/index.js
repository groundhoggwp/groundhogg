import Select from '@material-ui/core/Select'
import MenuItem from '@material-ui/core/MenuItem';
import FormControl from '@material-ui/core/FormControl';
import { makeStyles } from '@material-ui/core/styles';
import { select } from '@wordpress/data'
import { __ } from '@wordpress/i18n'

const useStyles = makeStyles((theme) => ({
  root: {
    width: 500,
    '& > * + *': {
      marginTop: theme.spacing(3),
    },
  },
  formControl: {
    margin: theme.spacing(1),
    minWidth: 120,
  },
  selectEmpty: {
    marginTop: theme.spacing(2),
  },

}));

export default function selectOwners(props) {
  const classes = useStyles();

  const {
	  defaultValue,
	  value,
	  onChange
  } = props;

  const adminUsers = select( 'core' ).getUsers( { roles : [ 'administrator' ] } );

  if ( ! adminUsers ) {
	  return null;
  }

  if ( ! adminUsers.length ) {
	  return null;
  }

  return (
	<div className={classes.root}>
		<FormControl className={classes.formControl}>
			<Select
				value={value}
				defaultValue={defaultValue}
				onChange={onChange}
				className={classes.selectEmpty}
				inputProps={{ 'aria-label': 'Without label' }}
			>
				<MenuItem value={''}>{ __( 'Please select an owner.' ) }</MenuItem>
				{adminUsers.map( ( user ) => ( <MenuItem value={user.id}>{ `${user.username}(${user.email})` }</MenuItem> ) ) }
			</Select>
		</FormControl>
    </div>
  );
}
