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
 	TAGS_STORE_NAME
} from '../../../../../../../data';
import Tag from "components/svg/Tag/";
import { Toggle } from "components/core-ui/toggle/";
import { DropDown } from "components/core-ui/drop-down/";
import { DynamicForm } from "components/core-ui/dynamic-form/";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { createTheme }  from "../../../../../../../theme";

const STEP_TYPE = "remove_tag";

const theme = createTheme({});
const useStyles = makeStyles((theme) => ({
  root: {
    width: "calc(100% - 50px)",
    padding: "33px 25px 18px 25px"
  },
}));

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Remove Tag",

  icon: <Tag />,

  read: ({ data, meta, stats }) => {
    return <>Apply Tag</>;
  },

  edit: ({ data, meta, stats }) => {
    const {
      items
    } = useSelect((select) => {
      const store = select(TAGS_STORE_NAME)

      return {
        items: store.getItems(),
      }
    }, [] )

    const {
      createItem,
      fetchItems,
      deleteItems
    } = useDispatch( TAGS_STORE_NAME );

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


    const tagsFormatted = items.map((tag)=>{
      return {
        display: email.data.title,
        value: email.ID
      }
    })

    const formElements = [
      {
      label: 'Remove Tag',
      component: <><DropDown id={'email'} options={tagsFormatted} value={formData['email']} onChange={hanldeFormChange}/>
      <br/>Add new tags by hitting [enter] or by typing a [comma].</>
    },
      {
      label: 'Enable conditional logic:',
      component: <Toggle id={'conditional-logic'} checked={formData['conditional-logic']} onChange={hanldeFormChange} backgroundColor={theme.palette.primary.main} name="checked" />
    }

  ]
    return <Card className={classes.root}>
      <div className={classes.actionLabel}>
        Remove Tag
      </div>

      <DynamicForm children={formElements} hanldeFormChange={hanldeFormChange}/>

    </Card>

  },
};

registerStepType(STEP_TYPE, stepAtts);
