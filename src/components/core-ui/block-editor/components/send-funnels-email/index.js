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
import { Button, Card, TextField } from "@material-ui/core";
import { makeStyles } from "@material-ui/core/styles";
import ArrowBackIosIcon from "@material-ui/icons/ArrowBackIos";
import ReplayIcon from "@material-ui/icons/Replay";
/**
 * Internal dependencies
 */
import Step from "./components/step";
import Mail from "components/svg/Mail/";

import { createTheme } from "../../../../../theme";
const theme = createTheme({});

const useStyles = makeStyles((theme) => ({
  root: {
    width: "265px",
    position: "absolute",
    top: "144px",
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
}));

const SendEmailComponent = withStyles((theme) => ({
  root: {
  },
  sendEmailComponent:{
    position: 'absolute',
    top: '147px',
    left: editorType === 'email' ? '0px' : '305px',
    width: editorType === 'email' ? 'calc(100% - 412px)' : 'calc(100% - 729px)',
    padding: '33px 25px 18px 25px'
  },
  sendEmailComponentLabel:{
    color: '#102640',
    width: '250px',
    display: 'inline-block',
    marginBottom: '20px',
    fontSize: '16px',
    fontWeight: '500'
  },
  newEmailButton:{
    display: 'flex',
    alignItems: 'center',
    fontSize: '14px',
    fontWeight: '400',
    color: theme.palette.primary.main,
    float: 'right',
    '& svg':{
      border: `0.3px solid ${theme.palette.primary.main}`,
      borderRadius: '4px',
      marginRight: '5px',
      padding: '4px'
    }
  },
  sendEmailSelect:{
    // Importants are needed while we are still inside wordpress, remove this later
    display: 'block',
    width: 'calc(100%) !important',
    maxWidth: 'calc(100%) !important',
    padding: '5px 20px 5px 20px',
    border: '1.2px solid rgba(16, 38, 64, 0.15) !important'
  },
  subTitleContainer: {
    position: 'absolute',
    top: '347px',
    left: editorType === 'email' ? '20px' : '320px',
    "& label": {
      fontSize: "12px",
    },
    "& svg": {
      cursor: 'pointer',
      marginTop: '10px'
    },
    '& input[type="text"], & input[type="text"]:focus': {
      color: '#000',
      background: 'none',
      fontSize: "16px",
      outline: "none",
      border: "none",
      boxShadow: "none",
      padding: "0",
      marginLeft: "-1px",
    },
  },
  selectInsertReplacement:{
    position: 'absolute',
    minWidth: '209px',
    top: '345px',
    left: '747px',
    fontSize: '13px !important',
    lineHeight: '13px !important',
    fontWeight: '400',
    padding: '6.5px 16.5px 6.5px 16.5px !important',//!Importants can be removed once we\re out of the wordpress space
    borderRadius: '7px !important',
    border: '1.2px solid rgba(16, 38, 64, 0.15) !important',
    boxShadow: 'none',
    outline: 'none',
    '&:focus':{
      border: 'none'
    }
  },
}))(({ classes, ...props }) => {
  return (
    <>

    <Card className={classes.sendEmailComponent}>

      <div className={classes.sendEmailComponentLabel}>Select an email to send:</div>

      <div className={classes.newEmailButton}>
        <svg width="6" height="6" viewBox="0 0 6 6" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M5.64932 2.46589V2.31589H5.49932H3.62312V0.389893V0.239893H3.47312H2.48331H2.33331V0.389893V2.31589H0.46875H0.31875V2.46589V3.54589V3.69589H0.46875H2.33331V5.60989V5.75989H2.48331H3.47312H3.62312V5.60989V3.69589H5.49932H5.64932V3.54589V2.46589Z" fill="#0075FF" stroke="#0075FF" stroke-width="0.3"/>
        </svg>

        new email
      </div>

      <select
        className={classes.sendEmailSelect}
        value={''}
        onChange={()=>{}}
        label=""
      >
        <option value={10}>none</option>
        <option value={20}>Marketing</option>
      </select>

      <div className={classes.skipEmail}>
        <label>Skip email step if confirmed:</label>
        <IOSSwitch checked={true} onChange={()=>{}} name="checked" />
      </div>
    </Card>
    <div className={classes.subTitleContainer}>
      <TextField
        label=""
        value={subTitle}
        onChange={handleSubTitleChange}
        InputProps={{ disableUnderline: true, disabled: disableSubTitle }}
      />
      <EditPen onClick={toggleSubTitleDisable}/>
    </div>


      <select  onChange={handleInsertReplacement} label="" className={classes.selectInsertReplacement}>
        <option value="" selected disabled hidden>Insert replacement</option>
        <option value={'something'}>somethinhg</option>
      </select>
    </>
  );
});
