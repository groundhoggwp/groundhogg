/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { Fragment, useState } from "@wordpress/element";
import { PinnedItems } from "@wordpress/interface";
import { Inserter } from "@wordpress/block-editor";
import { useSelect, useDispatch } from '@wordpress/data'

/**
 * External dependencies
 */
import { Button, Card, Switch, TextField } from "@material-ui/core";
import { makeStyles } from "@material-ui/core/styles";
import ArrowBackIosIcon from "@material-ui/icons/ArrowBackIos";
import ReplayIcon from "@material-ui/icons/Replay";
import { withStyles } from '@material-ui/core/styles';
// import DateFnsUtils from '@date-io/date-fns';
import {
  // MuiPickersUtilsProvider,
  KeyboardTimePicker,
  // KeyboardDatePicker,
} from '@material-ui/pickers';
/**
 * Internal dependencies
 */
import Tag from "components/svg/Tag/";
import Toggle from "../../../components/toggle/";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { createTheme }  from "../../../../../../../theme";

const STEP_TYPE = "delay_timer";

const theme = createTheme({});
const useStyles = makeStyles((theme) => ({
  root: {
    width: "calc(100% - 50px)",
    padding: "33px 25px 18px 25px"
  },
  inputRow:{
    margin: '10px 0px 0px 15px'
  }
}));

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Delay Timer",

  icon: <Tag />,
  read: ({ data, meta, stats }) => {
    return <>Delay Timer</>;
  },

  edit: ({ data, meta, stats }) => {
    const classes = useStyles();

    const [selectedDate, setSelectedDate] = React.useState(new Date('2014-08-18T21:11:54'));

    const handleDateChange = (date) => {
      setSelectedDate(date);
    };

    console.log(data, meta, stats)

    return <Card className={classes.root}>

    <TextField
      type='time'
    />
          </Card>
    // return <Card className={classes.root}>
    //
    // <MuiPickersUtilsProvider utils={DateFnsUtils}>
    //         <div className={classes.inputRow}>
    //           <label>Content:</label>
    //           <KeyboardTimePicker
    //             margin="normal"
    //             id="time-picker"
    //             label="Time picker"
    //             value={selectedDate}
    //             onChange={handleDateChange}
    //             KeyboardButtonProps={{
    //               'aria-label': 'change time',
    //             }}
    //           />
    //         </div>
    //       </MuiPickersUtilsProvider>
    //       </Card>

  },
};

registerStepType(STEP_TYPE, stepAtts);
