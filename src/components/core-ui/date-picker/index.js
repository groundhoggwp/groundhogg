import React from 'react';
import { makeStyles } from '@material-ui/core/styles';
import TextField from '@material-ui/core/TextField';
import { ThemeProvider } from '@material-ui/core/styles';
import { createMuiTheme } from '@material-ui/core/styles';
import {getLuxonDate} from "utils/index";
import { useState, useRef, useEffect, Fragment } from '@wordpress/element';

const useStyles = makeStyles((theme) => ({
  textField: {
    minWidth: '150px',
    border: 'none',
    outline: 'none'
  },
}));

export default function DatePickers({selectedDate, dateChange, label, id}) {
  const classes = useStyles();

  const [date, setDate] = useState( selectedDate );

  const validDateChange = (newDate) => {
    // May need to enhance this logic for multi month/year changes
    // console.log(Math.abs(diffInMonths.as('days')) % 30);

    // Block Month Changes
    if(getLuxonDate('one_month_back') === newDate){
      return false;
    }
    if(getLuxonDate('one_month_forward') === newDate){
      return false;
    }

    return true;
  }

  const handleChange = (ele) => {
    const newDate = ele.target.value;

    if(validDateChange(newDate)){
      dateChange(id, newDate);
    }

    setDate(newDate);
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
