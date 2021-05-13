import React from 'react';
import { makeStyles } from '@material-ui/core/styles';
import TextField from '@material-ui/core/TextField';
import { createMuiTheme } from '@material-ui/core/styles';
// import { getLuxonDate } from "utils/index";
import { useState, useRef, useEffect, Fragment } from '@wordpress/element';

// const useStyles = makeStyles((theme) => ({
//
// }));

export const DatePicker = withStyles((theme) => ({
  root: {
    minWidth: '150px',
    marginLeft: '30px',
  },
  input: {
    "&:before": {
      border: 'none'
    }
}
}))(({ classes, ...rest }) => {

  // const [date, setDate] = useState( selectedDate );

  const validDateChange = (newDate) => {

    // The date picker can move forward months and years back and forth these conditions block updates and improves the UX
    // More conditions may be needed
  //   if(getLuxonDate('one_month_back', date) === newDate){
  //     return false;
  //   }
  //   if(getLuxonDate('one_month_forward', date) === newDate){
  //     return false;
  //   }
  //   if(getLuxonDate('one_year_back', date) === newDate){
  //     return false;
  //   }
  //   if(getLuxonDate('one_year_forward', date) === newDate){
  //     return false;
  //   }
  //   return true;
  }

  const handleChange = (ele) => {
    // const newDate = ele.target.value;

    // if(validDateChange(newDate)){
    //   dateChange(id, newDate);
    // }
    //
    // setDate(newDate);
  };
  return (
    // value=selectedDate
    <form  noValidate>
      <TextField
        type='date'
        className={classes.root}
        id={id}
        label={label}
        value={'2020-20-01'}
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
});


DatePicker.propTypes = {
  /**
   * is the on or off
   */
  text: PropTypes.bool,
  backgroundColor: PropTypes.string
};

DatePicker.defaultProps = {
  text: false,
  backgroundColor: '2020-12-01'
};
