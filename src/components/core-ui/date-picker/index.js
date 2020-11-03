import React from 'react';
import { makeStyles } from '@material-ui/core/styles';
import TextField from '@material-ui/core/TextField';
import { ThemeProvider } from '@material-ui/core/styles';
import { createMuiTheme } from '@material-ui/core/styles';

const useStyles = makeStyles((theme) => ({
  textField: {
    minWidth: '150px'
  },
}));

export default function DatePickers({selectedDate, dateChange, label, id}) {
  const classes = useStyles();

  const handleChange = (ele, here) => {
    console.log('handle change')
  };
  const handleAccept = (ele, here) => {
    console.log(ele.target, here)
    console.log(ele.target.value, here)
    // dateChange.bind(this, id)
    // setAttributes({
    //   text: value,
    // });
  };

  const handleMonthChange = () =>{
    console.log('month change')
  }
        // onChange={dateChange.bind(this, id)}
  return (
    <form  noValidate>
      <TextField
        type='date'
        className={classes.textField}
        id={id}
        label={label}        
        value={selectedDate}
        onChange={handleChange}
        onAccept={handleAccept}
        onMonthChange={handleMonthChange}
        KeyboardButtonProps={{
          'aria-label': 'change date',
        }}
      />
    </form>
  );
}
