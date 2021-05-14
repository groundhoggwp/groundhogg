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
import {
  KeyboardTimePicker,
} from '@material-ui/pickers';
/**
 * Internal dependencies
 */
import Tag from "components/svg/Tag/";
import AddWithBorder from "components/svg/AddWithBorder/";
import Trash from "components/svg/Trash/";
import Toggle from "components/core-ui/toggle/";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { createTheme }  from "../../../../../../../theme";

const STEP_TYPE = "http_post";

const theme = createTheme({});
const useStyles = makeStyles((theme) => ({
  root: {
    width: "calc(100% - 50px)",
    padding: "33px 25px 18px 25px"
  },
  inputRow:{
    margin: '10px 0px 0px 15px'
  },
  customHeaderBtn: {
    display: "inline-block",
    width: "20px",
    height: "20px",
    margin: '0',
    stroke: "#000",
    fill: "#000",
    marginRight: '25px'
  },
  addButton:{
    stroke: theme.palette.secondary.main,
    fill: theme.palette.secondary.main,
  },
  trashButton:{
    stroke: theme.palette.error.dark,
    fill: theme.palette.error.dark,
  }
}));

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "HTTP Post",

  icon: <Tag />,
  read: ({ data, meta, stats }) => {
    return <>Delay Timer</>;
  },

  edit: ({ data, meta, stats }) => {
    const classes = useStyles();





    const [formData, setFormData] = React.useState({});



    console.log(keyPairSection)
    const handleChange = (e) => {
      console.log(e.target.name, e.target.value)
      formData[e.target.id] = e.target.value
      setFormData(formData)
    }






    return <Card className={classes.root}>





            <div className={classes.inputRow}>
              <label>Method:</label>
              <TextField
                 name="method"
                 value={formData['method']}
                  onChange={handleChange}
                 defaultValue="Default Value"
               />
            </div>
            <div className={classes.inputRow}>
              <label>Target URL:</label>
              <TextField
                 name="target-url"
                 value={formData['target-url']}
                  onChange={handleChange}
                 defaultValue="Default Value"
               />
            </div>
            <div className={classes.inputRow}>
              <label>toggle:</label>
              <Toggle checked={formData['toggle']} onChange={handleChange} name="toggle" />
            </div>
          </Card>
  },
};

registerStepType(STEP_TYPE, stepAtts);
