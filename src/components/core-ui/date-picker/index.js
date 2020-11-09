import React from 'react';
import { makeStyles } from '@material-ui/core/styles';
import TextField from '@material-ui/core/TextField';
import { ThemeProvider } from '@material-ui/core/styles';
import { createMuiTheme } from '@material-ui/core/styles';
import { DateTime } from 'luxon';
import { useState, useRef, useEffect, Fragment } from '@wordpress/element';

const useStyles = makeStyles((theme) => ({
  textField: {
    minWidth: '150px'
  },
}));

export default function DatePickers({selectedDate, dateChange, label, id}) {
  const classes = useStyles();

  const [date, setDate] = useState( selectedDate );

  const handleChange = (ele) => {
    const newValue = ele.target.value;

    if(newValue === DateTime.fromISO(date).plus({ months: 1 }).toISODate()) {
      console.log('month forward')
    } else if(newValue === DateTime.fromISO(date).minus({ months: 1 }).toISODate()) {
      console.log('month back')
    } else {
      console.log('new date')
      dateChange(id, newValue);
    }

    setDate(newValue);
  };
  return (
    <form  noValidate>
      <TextField
        type='date'
        className={classes.textField}
        id={id}
        label={label}
        value={selectedDate}
        onChange={handleChange}
        KeyboardButtonProps={{
          'aria-label': 'change date',
        }}
      />
    </form>
  );
}
