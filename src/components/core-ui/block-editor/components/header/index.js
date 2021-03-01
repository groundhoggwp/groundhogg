/**
 * External dependencies
 */
import { Button, Card, TextField } from "@material-ui/core";

/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { Fragment, useState } from "@wordpress/element";
import { PinnedItems } from "@wordpress/interface";
import { Inserter } from "@wordpress/block-editor";

/**
 * External dependencies
 */
import { makeStyles } from "@material-ui/core/styles";
import ArrowBackIosIcon from "@material-ui/icons/ArrowBackIos";
import ReplayIcon from "@material-ui/icons/Replay";
/**
 * Internal dependencies
 */

import { Spinner } from "components";
import ArrowLeft from "components/svg/ArrowLeft/";
import ArrowCurveLeft from "components/svg/ArrowCurveLeft/";
import ArrowCurveRight from "components/svg/ArrowCurveRight/";
import EditPen from "components/svg/EditPen/";
import SendMail from "components/svg/SendMail/";
import MoreDots from "components/svg/MoreDots/";

const useStyles = makeStyles((theme) => ({
  root: {
    position: "absolute",
    display: 'flex',
    top: "0",
    left: "-20px",
    overflow: "visible",
    borderRadius: "5px",
    margin: "20px",
    width: "calc(100%)",
    overflow: "visible",
    padding: "23px 0px 28px 5px",
  },
  backButton: {
    display: "inline-block",
    color: "#fff",
    width: "10px",
    margin: "15px 25px 0px 10px",
  },
  titleContainer: {
    "& label": {
      fontSize: "12px",
    },
    '& input[type="text"], & input[type="text"]:focus': {
      color: '#000',
      fontSize: "24px",
      outline: "none",
      border: "none",
      boxShadow: "none",
      padding: "0",
      marginLeft: "-1px",
    },
  },
  editTitleBtn:{
    cursor: 'pointer',
    margin: '20px 0 0 5px'
  },
  moreBtn:{
    display: 'inline-flex',
    justifyContent: 'center',
    alignItems: 'center',
    width: '44px',
    height: '22px',
    border: '0.5px solid rgba(16, 38, 64, 0.25)',
    borderRadius: '5px',
    margin: '15px 0 0 25px',
    cursor: 'pointer'
  },
  updateContainer: {
    display: 'inline-flex',
    alignItems: 'center',
    marginRight: "20px",
  },
  stepUpdateButton: {
    width: "15px",
    margin: "16px 10px 0px 10px",
    justifySelf: 'end',
    cursor: 'pointer'
  },
  stepUpdateButtonFirst:{
    marginLeft: '285px'
  },
  updateButton: {
    width: "320px",
    color: "#fff",
    background: "#9ECE38",
    fontSize: "18px",
    textTransform: "none",
    margin: "9px 0 0 70px",
    borderRadius: "7px",
    justifySelf: 'end',
    "& svg": {
      marginLeft: "145px",
    },
  },
}));

export default function Header({
  email,
  history,
  updateDoc,
  isSaving,
  handleTitleChange,
  title,
  editorType,
  handleOpen,
  emailStepBackward,
  emailStepForward
}) {

  const [disableTitle, setDisableTitle] = useState(true);

  const classes = useStyles();
  const buttonText =
    editorType === "email" ? __("Update Email") : __("Update Funnel");

  const toggleTitle = () =>{
    setDisableTitle(disableTitle ? false : true )
  }

  return (
    <Card className={classes.root}>
        <span className={classes.backButton}>
          <ArrowLeft href="./admin.php?page=gh_emails" />
        </span>

        <span className={classes.titleContainer}>
          <TextField
            label="Email Info"
            value={title}
            onChange={handleTitleChange}
            InputProps={{ disableUnderline: true, disabled: disableTitle }}
          />
        </span>

        <span className={classes.updateContainer}>
          <span className={classes.editTitleBtn} onClick={toggleTitle}><EditPen /></span>

          <span className={classes.moreBtn} onClick={()=>{handleOpen()}}><MoreDots/></span>
          <span className={`${classes.stepUpdateButton} ${classes.stepUpdateButtonFirst}`} onClick={() => {emailStepBackward()}}>
            <ArrowCurveRight />
          </span>
          <span className={classes.stepUpdateButton} onClick={() => {emailStepForward()}}>
            <ArrowCurveLeft />
          </span>

          <Button className={classes.updateButton} onClick={updateDoc}>
            {buttonText}
            <SendMail />
          </Button>
        </span>
        <PinnedItems.Slot scope="gh/v4/core" />
    </Card>
  );
}
