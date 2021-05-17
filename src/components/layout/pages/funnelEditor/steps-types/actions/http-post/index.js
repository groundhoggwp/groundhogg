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
 import {
 	EMAILS_STORE_NAME
} from '../../../../../../../data';
import NewUser from "components/svg/NewUser/";
import { Toggle } from "components/core-ui/toggle/";
import { DropDown } from "components/core-ui/drop-down/";
import { DynamicForm } from "components/core-ui/dynamic-form/";
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
  actionLabel: {
    color: "#102640",
    width: "250px",
    display: "inline-block",
    marginBottom: "10px",
    fontSize: "16px",
    fontWeight: "500",
  },
}));

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Delay Timer",

  icon: <NewUser />,

  read: ({ data, meta, stats }) => {
    return <>HTTP Post</>;
  },

  edit: ({ data, meta, stats }) => {

    const timeOptions = [
      {
        display: 'Minutes',
        value: 'minutes'
      },
      {
        display: 'Hours',
        value: 'hours'
      },
      {
        display: 'Days',
        value: 'days'
      },
      {
        display: 'Weeks',
        value: 'weeks'
      },
      {
        display: 'Months',
        value: 'months'
      }
    ]
    const runOptions = [
      {
        display: 'Immediately',
        value: 'immediately'
      },
      {
        display: 'At time of day',
        value: 'at-time-of-day'
      }
    ]

    const [formData, setFormData] = useState({});
    const classes = useStyles();

    const hanldeFormChange = (e) => {
      if(e.target.type === 'checkbox'){
        formData[e.target.id] = e.target.checked
      } else {
        formData[e.target.id] = e.target.value
      }

      setFormData(formData)
    }



    const formElements = [
      {
        label: 'And run:',
        component: <><Toggle checked={formData['toggle']} onChange={hanldeFormChange} name="toggle" /></>
      },
      {
      label: 'Enable conditional logic:',
      component: <Toggle id={'conditional-logic'} checked={formData['conditional-logic']} onChange={hanldeFormChange} backgroundColor={theme.palette.primary.main} name="checked" />
    }
  ]
    return <Card className={classes.root}>
      <div className={classes.actionLabel}>
        HTTP Post
      </div>

      <DynamicForm children={formElements} hanldeFormChange={hanldeFormChange}/>

    </Card>

  },
};

registerStepType(STEP_TYPE, stepAtts);
