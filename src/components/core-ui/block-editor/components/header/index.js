/**
 * External dependencies
 */
import { Button, Card, TextField } from "@material-ui/core";

/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { Fragment } from "@wordpress/element";
import { PinnedItems } from "@wordpress/interface";
import { Inserter } from "@wordpress/block-editor";

/**
 * External dependencies
 */
import { makeStyles } from "@material-ui/core/styles";
import ArrowBackIosIcon from '@material-ui/icons/ArrowBackIos';
import ReplayIcon from '@material-ui/icons/Replay';
/**
 * Internal dependencies
 */

import HeaderToolbar from "./header-toolbar";
import { Spinner } from "components";
import ArrowLeftIcon from "components/svg/ArrowLeftIcon/";
import ArrowCurveLeft from "components/svg/ArrowCurveLeft/";
import ArrowCurveRight from "components/svg/ArrowCurveRight/";
import SendMail from "components/svg/SendMail/";

const useStyles = makeStyles((theme) => ({
  root: {
    // width: "calc(100% - 19px)",
    overflow: "visible",
    borderRadius: "5px",
    padding: "20px",
  },
  button:{
    color: '#fff'
  },
  titleContainer:{
    '& label':{
      fontSize: '12px'
    },
    '& input[type="text"], & input[type="text"]:focus':{
      fontSize: '24px',
      outline: 'none',
      border: 'none',
      boxShadow: 'none',
      padding: '0',
      marginLeft: '-1px'
    }
  }
}));

export default function Header({
  email,
  history,
  updateDoc,
  isSaving,
  handleTitleChange,
  title,
  editorType
}) {
  console.log(editorType)

  const classes = useStyles();
  const buttonText = editorType === 'email' ? __("Update Email") : __("Update Funnel")

  return (
    <Card className={classes.root}>
      <div
        role="region"
        aria-label={__("Email Editor primary top bar.", "groundhogg")}
        tabIndex="-1"
      >
        <HeaderToolbar>
          <Button className={classes.button} href="./admin.php?page=gh_emails">
            <ArrowLeftIcon/>
          </Button>

              <TextField className={classes.titleContainer}
                label="Email Info"
                value={title}
                onChange={handleTitleChange}
                InputProps={{ disableUnderline: true }}
              />
          <span>
            <Button><ArrowCurveRight/></Button>
            <Button><ArrowCurveLeft/></Button>


            <Button className={classes.button} onClick={updateDoc} variant="contained" color="primary">
              {buttonText}
              <SendMail/>
            </Button>
            <PinnedItems.Slot scope="gh/v4/core" />
          </span>
        </HeaderToolbar>
      </div>
    </Card>
  );
}
