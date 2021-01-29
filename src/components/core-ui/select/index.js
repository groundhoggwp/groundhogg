import Select from '@material-ui/core/Select'
import MenuItem from '@material-ui/core/MenuItem';
import FormControl from '@material-ui/core/FormControl';
import { makeStyles } from '@material-ui/core/styles';

const useStyles = makeStyles((theme) => ({
  root: {
    width: '100%',
    '& > * + *': {
      marginTop: theme.spacing(3),
    },
  },
  formControl: {
    margin: theme.spacing(1),
    minWidth: 120,
  },
  selectEmpty: {
    width: '100%',
    marginTop: theme.spacing(2),
  },

}));

export default function ghSelect(props) {
  const classes = useStyles();

  const {
	  defaultValue,
	  value,
	  onChange,
	  atts
  } = props;

  if ( ! atts.options.length ) {
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
				{atts.options.map( ( key, value ) => ( <MenuItem value={key}>{value}</MenuItem> ) ) }
			</Select>
		</FormControl>
    </div>
  );
}
