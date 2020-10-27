import React from 'react';
import { makeStyles } from '@material-ui/core/styles';
import TextField from '@material-ui/core/TextField';
import { ThemeProvider } from '@material-ui/core/styles';
import { createMuiTheme } from '@material-ui/core/styles';
// import purple from '@material-ui/core/colors/purple';
// import green from '@material-ui/core/colors/green';
// import white from '@material-ui/core/colors/white';

const classes = makeStyles((theme) => ({
  container: {
    display: 'flex',
    // color: 'white',
    flexWrap: 'wrap',
  },
  textField: {
    marginLeft: theme.spacing(1),
    marginRight: theme.spacing(1),
    width: 250,
  },
}));

export default function DatePickers(props) {
  // const classes = useStyles();
  console.log(props);
  return (
    <form className={classes.container} noValidate>
      <TextField
        id={props.label}
        label={props.label}
        type='date'
        // defaultValue="2017-05-24"
        onChange={props.dateChange.bind(this, props.filter)}
        // onChange={(event, x) => {console.log(x, event);}}
        // className={classes.textField}
        // InputLabelProps={{
        //   shrink: true,
        // }}
      />
    </form>
  );
}
