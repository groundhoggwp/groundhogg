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
import Toggle from "../../../components/toggle/";
import { ACTION, ACTION_TYPE_DEFAULTS } from "../../constants";
import { registerStepType } from "data/step-type-registry";
import { createTheme }  from "../../../../../../../theme";

const STEP_TYPE = "apply_tag";

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

  sendEmailComponentLabel: {
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
  sendEmailSelect: {
    // Importants are needed while we are still inside wordpress, remove this later
    display: "block",
    width: "calc(100%) !important",
    maxWidth: "calc(100%) !important",
    padding: "5px 20px 5px 5px",
    borderRadius: "4px",
    border: "1.2px solid rgba(16, 38, 64, 0.15) !important",
  },
  inputRow:{
    margin: '10px 0px 0px 15px'
  }
}));

const stepAtts = {
  ...ACTION_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: ACTION,

  name: "Apply Tag",

  icon: <Tag />,
  read: ({ data, meta, stats }) => {
    return <>Apply Tag</>;
  },

  edit: ({ data, meta, stats }) => {
    const {
      items,
      // totalItems,
      // isRequesting,
      // isCreating
    } = useSelect((select) => {
      const store = select(TAGS_STORE_NAME)

      return {
        items: store.getItems(),
        // totalItems: store.getTotalItems(),
        // isRequesting: store.isItemsRequesting(),
        // isCreating: store.isItemsCreating()
      }
    }, [] )

    const {
      createItem,
      fetchItems,
      deleteItems
    } = useDispatch( TAGS_STORE_NAME );

    const [emailSkip, setEmailSkip] = useState(false);
    const [conditionalLogic, setConditionalLogic] = useState(false);
    const [selectedEmail, setSelectedEmail] = useState(false);
    const classes = useStyles();


    const toggleEmailSkip = () => {
      setEmailSkip(!emailSkip)
    }
    const toggleConditionalLogic = () => {
      setConditionalLogic(!conditionalLogic)
    }
    const handleEmailChange = (e) => {
      setSelectedEmail(e.target.value)
    }

    console.log(data, meta, stats, items)

    return <Card className={classes.root}>
            <div className={classes.sendEmailComponentLabel}>
              Select an email to send:
            </div>

            <div className={classes.newEmailButton}>
              <svg
                width="6"
                height="6"
                viewBox="0 0 6 6"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M5.64932 2.46589V2.31589H5.49932H3.62312V0.389893V0.239893H3.47312H2.48331H2.33331V0.389893V2.31589H0.46875H0.31875V2.46589V3.54589V3.69589H0.46875H2.33331V5.60989V5.75989H2.48331H3.47312H3.62312V5.60989V3.69589H5.49932H5.64932V3.54589V2.46589Z"
                  fill="#0075FF"
                  stroke="#0075FF"
                  stroke-width="0.3"
                />
              </svg>
              new email
            </div>

            <select
              className={classes.sendEmailSelect}
              value={selectedEmail}
              onChange={handleEmailChange}
              label=""
            >
              {
                items.map(item => {
                  // console.log(item)
                  return (<option value={item.ID}>{item.data.title}</option>)
                })
              }
            </select>

            <div className={classes.inputRow}>
              <label>Enable conditional logic:</label>
              <Toggle checked={conditionalLogic} onChange={toggleConditionalLogic} name="checked" />
            </div>
          </Card>

  },
};

registerStepType(STEP_TYPE, stepAtts);
