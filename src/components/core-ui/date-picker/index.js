import React from 'react';
import { makeStyles } from '@material-ui/core/styles';
import TextField from '@material-ui/core/TextField';
import { createMuiTheme } from '@material-ui/core/styles';
import {getLuxonDate} from "utils/index";
import { useState, useRef, useEffect, Fragment } from '@wordpress/element';

const useStyles = makeStyles((theme) => ({
  textField: {
    minWidth: '150px',
    marginLeft: '30px',    
    // Parking this for later, a great example of how we can avoid using classes
    // "& .MuiInputBase-input": {
    //         display: "none"
    //  }
  },
  input: {
    "&:before": {
      border: 'none'
    }
}
}));

export default function DatePickers({selectedDate, dateChange, label, id}) {
  const classes = useStyles();

  const [date, setDate] = useState( selectedDate );

  const validDateChange = (newDate) => {

    // The date picker can move forward months and years back and forth these conditions block updates and improves the UX
    // More conditions may be needed
    if(getLuxonDate('one_month_back', date) === newDate){
      return false;
    }
    if(getLuxonDate('one_month_forward', date) === newDate){
      return false;
    }
    if(getLuxonDate('one_year_back', date) === newDate){
      return false;
    }
    if(getLuxonDate('one_year_forward', date) === newDate){
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
        InputProps={{
            className: classes.input,
        }}
      />
    </form>
  );
}
