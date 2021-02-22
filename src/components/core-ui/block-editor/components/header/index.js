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


import { Spinner } from "components";
import ArrowLeft from "components/svg/ArrowLeft/";
import ArrowCurveLeft from "components/svg/ArrowCurveLeft/";
import ArrowCurveRight from "components/svg/ArrowCurveRight/";
import EditPen from "components/svg/EditPen/";
import SendMail from "components/svg/SendMail/";

const useStyles = makeStyles((theme) => ({
  root: {
    position: 'absolute',
    top: '0',
    left: '-20px',
    overflow: "visible",
    borderRadius: "5px",
    margin: "20px",
    width: "calc(100%)",
    overflow: "visible",
    padding: "28px 0px 28px 10px",
  },
  backButton:{
    display: 'inline-block',
    color: '#fff',
    width: '10px',
    margin: '15px 25px 0px 10px'
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
  },
  updateContainer : {
    float: 'right',
    display: 'inline-block',
    marginRight: '20px'
  },
  stepUpdateButton: {
    width: '15px',
    margin: '0px 10px 0px 10px'
  },
  updateButton: {
    width: '320px',
    color: '#fff',
    background: '#9ECE38',
    fontSize: '18px',
    textTransform: 'none',
    marginLeft: '70px',
    borderRadius: '7px',
    '& svg':{
      marginLeft: '145px'
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
              <span className={classes.backButton}><ArrowLeft href="./admin.php?page=gh_emails"/></span>

              <span className={classes.titleContainer}>
                <TextField
                  label="Email Info"
                  value={title}
                  onChange={handleTitleChange}
                  InputProps={{ disableUnderline: true }}
                />
                <EditPen/>
              </span>


            <span className={classes.updateContainer}>
              <span className={classes.stepUpdateButton}><ArrowCurveRight/></span>
              <span className={classes.stepUpdateButton}><ArrowCurveLeft/></span>


              <Button className={classes.updateButton} onClick={updateDoc}>
                {buttonText}
                <SendMail/>
              </Button>
            </span>
            <PinnedItems.Slot scope="gh/v4/core" />

      </div>
    </Card>
  );
}
