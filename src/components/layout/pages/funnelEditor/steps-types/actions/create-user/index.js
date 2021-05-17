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

const STEP_TYPE = "create_user";

const theme = createTheme({});
const useStyles = makeStyles((theme) => ({
  root: {
    width: "calc(100% - 50px)",
    padding: "33px 25px 18px 25px"
  },
  addStepHoverBtn: {
    position: "relative",
    display: "block",
    width: "265px",
    height: "1px",
    background: theme.palette.primary.main,
    textAlign: "center",
    zIndex: "999",
  },
  addStepHoverPlus: {
    color: theme.palette.primary.main,
    position: "absolute",
    top: "calc(50% - 8px)",
    left: "calc(50% - 8px)",
    width: "16px",
    height: "16px",
    background: "#fff",
    border: `1px solid ${theme.palette.primary.main}`,
    borderRadius: "4px",
  },
  addStepBtn: {
    width: "265px",
    textAlign: "center",
    textTransform: "none",
  },

  actionLabel: {
    color: "#102640",
    width: "250px",
    display: "inline-block",
    marginBottom: "10px",
    fontSize: "16px",
    fontWeight: "500",
  },
  newEmailButton: {
    display: "flex",
    alignItems: "center",
    fontSize: "14px",
    fontWeight: "400",
    color: theme.palette.primary.main,
    float: "right",
    "& svg": {
      border: `0.3px solid ${theme.palette.primary.main}`,
      borderRadius: "4px",
      marginRight: "5px",
      padding: "4px",
    },
  },

}));

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Create User",

  icon: <NewUser />,

  read: ({ data, meta, stats }) => {
    return <>Create User</>;
  },

  edit: ({ data, meta, stats }) => {
    const {
      items
    } = useSelect((select) => {
      const store = select(EMAILS_STORE_NAME)

      return {
        items: store.getItems(),
      }
    }, [] )

    const {
      createItem,
      fetchItems,
      deleteItems
    } = useDispatch( EMAILS_STORE_NAME );

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


    const emailFormated = items.map((email)=>{
      return {
        display: email.data.title,
        value: email.ID
      }
    })

    const formElements = [
      {
      label: 'User Role',
      component: <><DropDown id={'email'} options={emailFormated} value={formData['email']} onChange={hanldeFormChange}/>
      <br/>This role will be added to the new user. If the user already exists, the role will be added in addition to existing roles.</>
    },
      {
      label: 'Enable conditional logic:',
      component: <Toggle id={'conditional-logic'} checked={formData['conditional-logic']} onChange={hanldeFormChange} backgroundColor={theme.palette.primary.main} name="checked" />
    }

  ]
    return <Card className={classes.root}>
      <div className={classes.actionLabel}>
        Create User
      </div>

      <DynamicForm children={formElements} hanldeFormChange={hanldeFormChange}/>

    </Card>

  },
};

registerStepType(STEP_TYPE, stepAtts);
