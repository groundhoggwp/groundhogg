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
/**
 * Internal dependencies
 */
import  Tag  from "components/svg/Tag/";
import { Toggle } from "components/core-ui/toggle/";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { createTheme }  from "../../../../../../../theme";

const STEP_TYPE = "admin_notification";

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

  name: "Admin Notification",

  icon: <Tag />,
  read: ({ data, meta, stats }) => {
    return <>Apply Tag</>;
  },

  edit: ({ data, meta, stats }) => {
    const classes = useStyles();

    const [smsMode, setSmsMode] = useState(false);
    const [note, setContent] = useState('');
    const [sendTo, setSendTo] = useState('');


    const handleContentChange = () => {
      setContent(e.target.value)
    }
    const toggleSmsMode = () => {
      setSmsMode(!smsMode)
    }
    const handleSendTo = () => {
      setSendTo(e.target.value)
    }

    console.log(data, meta, stats, items)

    return <Card className={classes.root}>


            <div className={classes.inputRow}>
              <label>Send as SMS:</label>
              <Toggle checked={smsMode} onChange={toggleSmsMode} name="checked" />
            </div>
            <div className={classes.inputRow}>
              <label>Send to:</label>
              <TextField
                 id="standard-multiline-static"
                 label="Send To"
                 value={sendTo}
                  onChange={handleSendTo}
                 defaultValue="Default Value"
               />
            </div>
            <div className={classes.inputRow}>
              <label>Content:</label>
              <TextField
                 id="standard-multiline-static"
                 label="Multiline"
                 multiline
                 rows={4}
                 value={note}
                  onChange={handleContentChange}
                 defaultValue="Default Value"
               />
              <div>Use any valid replacement codes</div>
            </div>
            <div className={classes.inputRow}>
              <label>Enable conditional logic:</label>
              <Toggle checked={conditionalLogic} onChange={toggleConditionalLogic} name="checked" />
            </div>
          </Card>

  },
};

registerStepType(STEP_TYPE, stepAtts);
