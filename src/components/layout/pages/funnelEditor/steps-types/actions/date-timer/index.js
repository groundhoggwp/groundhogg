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
import DatePicker from "components/core-ui/date-picker";
import Toggle from "components/core-ui/toggle/";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { createTheme }  from "../../../../../../../theme";

const STEP_TYPE = "add_note";

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

  name: "Date Timer",

  icon: <Tag />,
  read: ({ data, meta, stats }) => {
    return <>Apply Tag</>;
  },

  edit: ({ data, meta, stats }) => {
    const classes = useStyles();

    const [date, setDate] = React.useState(getLuxonDate("today"));

    const dateChange = (date) => {
      setSelectedDate(date);
    };

    console.log(data, meta, stats, items)


    return <Card className={classes.root}>


            <div className={classes.inputRow}>
              <DatePicker
                dateChange={dateChange}
                selectedDate={date}
                label={"start"}
                id={"start"}
              />
              <label>Content:</label>

            </div>
            <div className={classes.inputRow}>
              <label>Enable conditional logic:</label>
              <Toggle checked={conditionalLogic} onChange={toggleConditionalLogic} name="checked" />
            </div>
          </Card>

  },
};

registerStepType(STEP_TYPE, stepAtts);