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
import { Button, Card, TextField } from "@material-ui/core";

/**
 * Internal dependencies
 */
import ExpandablePanel from "../../expandable-panel/";
import ArrowDown from "components/svg/ArrowDown/";
import Desktop from "components/svg/Desktop/";
import Phone from "components/svg/Phone/";
import AlignCenter from "components/svg/AlignCenter/";
import AlignLeft from "components/svg/AlignLeft/";

export default function ({
  sendTestEmail,
  from,
  handleSetFrom,
  messageType,
  handleMessageType,
  replyTo,
  handleSetReplyTo,
  emailAlignment,
  handleEmailAlignmentChange,
}) {
  const useStyles = makeStyles((theme) => ({
    root: {},
    inputText: {
      fontSize: "12px",
      width: "calc(100% - 10px)",
      padding: "6px 0px 6px 17px",
      marginTop: "10px",
      border: "1.2px solid rgba(16, 38, 64, 0.15)",
      "&:focus": {
        outline: "none",
        border: "1.2px solid rgba(16, 38, 64, 0.15)",
        boxShadow: "none",
      },
    },

    emailControls: {
      height: "auto",
      padding: "20px 22px 0 22px",
    },
    sendTestButton: {
      fontSize: "12px",
      textTransform: "none",
      marginTop: "-9px",
      width: "187px",
      height: "32px",
      color: theme.palette.primary.main,
      border: `1.2px solid ${theme.palette.primary.main}`,
    },
    viewTypeButton: {
      display: "inline-block",
      border: "1.2px solid rgba(16, 38, 64, 0.15)",
      padding: "5px 5px 4px 5px",
      margin: "0px 0px 10px 15px",
      borderRadius: "5px",
      "&:hover": {
        border: `1.2px solid ${theme.palette.primary.main}`,
        cursor: "pointer",
      },
    },
    alignmentContainer: {
      float: "left",
      display: "inline-block",
      margin: "20px 0px 3px 0px",
      width: "115px",
    },
    alignmentBtn: {
      display: "inline-block",
      borderRadius: "7px",
      margin: "7px 15px 20px 0px",
      cursor: "pointer",
      "& svg": {
        stroke: "#707d8c",
        padding: "7px",
      },
    },
    alignmentBtnSelected: {
      display: "inline-block",
      borderRadius: "7px",
      margin: "7px 15px 20px 0px",
      cursor: "pointer",
      background: theme.palette.primary.main,
      "& svg": {
        stroke: "#fff",
        padding: "7px",
      },
    },
    messageTypeContainer: {
      display: "inline-block",
      float: "left",
      marginTop: "21px",
      "& select": {
        fontSize: "12px",
        marginTop: "5px",
        padding: "0px 74px 0px 7px",
        border: "1.5px solid rgba(16, 38, 64, 0.1)",
      },
    },
    clearFloat: {
      clear: "both",
    },
  }));

  const classes = useStyles();

  return (
    <Card className={classes.emailControls}>
      <Button className={classes.sendTestButton} onClick={sendTestEmail}>
        {__("Send test email")}
      </Button>
      <div
        className={classes.viewTypeButton}
        onClick={() => {
          handleViewTypeChange("mobile");
        }}
      >
        <Phone />
      </div>
      <div
        className={classes.viewTypeButton}
        onClick={() => {
          handleViewTypeChange("desktop");
        }}
      >
        <Desktop />
      </div>

      <input
        className={classes.inputText}
        value={from}
        placeholder={"From"}
        onChange={handleSetFrom}
        fullWidth
      />
      <input
        className={classes.inputText}
        value={replyTo}
        placeholder={"Reply to"}
        onChange={handleSetReplyTo}
        fullWidth
      />

      <div className={classes.alignmentContainer}>
        <div>{__("Alignment:")}</div>
        <span
          className={
            emailAlignment === "left"
              ? classes.alignmentBtnSelected
              : classes.alignmentBtn
          }
          onClick={() => {
            handleEmailAlignmentChange("left");
          }}
        >
          <AlignLeft />
        </span>
        <span
          className={
            emailAlignment === "center"
              ? classes.alignmentBtnSelected
              : classes.alignmentBtn
          }
          onClick={() => {
            handleEmailAlignmentChange("center");
          }}
        >
          <AlignCenter />
        </span>
      </div>

      <div className={classes.messageTypeContainer}>
        <div>{__("Message Type:")}</div>
        <select value={messageType} onChange={handleMessageType} label="">
          <option value={"marketing"}>Marketing</option>
          <option value={"transactional"}>Transactional</option>
        </select>
      </div>

      <div className={classes.clearFloat} />
      <ExpandablePanel
        title={"Additional options:"}
        fontSize={"12px"}
        width={"calc(100% + 44px)"}
        margin={"0px 0px 0px -22px"}
      />
    </Card>
  );
}
