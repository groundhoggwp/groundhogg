/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
  BlockEditorKeyboardShortcuts,
  BlockEditorProvider,
  BlockList,
  BlockInspector,
  WritingFlow,
  ObserveTyping,
  Typewriter,
  CopyHandler,
  BlockSelectionClearer,
  MultiSelectScrollIntoView,
} from "@wordpress/block-editor";

import PerfectScrollbar from "react-perfect-scrollbar";

/**
 * External dependencies
 */
import { makeStyles } from "@material-ui/core/styles";
import { useEffect, useState } from "@wordpress/element";
import {
  Card,
  Button,
  FormControl,
  NativeSelect,
  FormHelperText,
  InputLabel,
  MenuItem,
  Select,
  TextField,
} from "@material-ui/core";

/**
 * Internal dependencies
 */
import EmailControls from "./components/email-controls";
import BlocksPanel from "./components/blocks-panel";
import Notes from "./components/notes";

import { createTheme } from "../../../../../theme";
const theme = createTheme({});

const Sidebar = ({
  sideBarBlockDisplayType,
  sendTestEmail,
  handleViewTypeChange,
  replyTo,
  handleSetReplyTo,
  from,
  handleSetFrom,
  messageType,
  handleMessageType,
  emailAlignment,
  handleEmailAlignmentChange,
  notes,
  handleChangeNotes,
}) => {
  const useStyles = makeStyles((theme) => ({
    root: {
      position: "absolute",
      top: "127px",
      right: "0px",
      width: "320px",
      borderRadius: "7px",
      margin: "20px",
      "& .scrollbar-container": {
        height: "100%",
      },
    },
  }));

  const classes = useStyles();

  return (
    <div
      className={classes.root}
      role="region"
      aria-label={__("Groundhogg Email Sidebar advanced settings.")}
      tabIndex="-1"
    >
      <EmailControls
        sendTestEmail={sendTestEmail}
        handleViewTypeChange={handleViewTypeChange}
        messageType={messageType}
        handleMessageType={handleMessageType}
        from={from}
        handleSetFrom={handleSetFrom}
        replyTo={replyTo}
        handleSetReplyTo={handleSetReplyTo}
        emailAlignment={emailAlignment}
        handleEmailAlignmentChange={handleEmailAlignmentChange}
      />

      <BlocksPanel sideBarBlockDisplayType={sideBarBlockDisplayType} />

      <Notes notes={notes} handleChangeNotes={handleChangeNotes} />
    </div>
  );
};

export default Sidebar;
