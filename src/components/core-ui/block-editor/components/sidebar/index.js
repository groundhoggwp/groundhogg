/**
 * WordPress dependencies
 */
import { createSlotFill, Panel, Box } from "@wordpress/components";
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
import { getBlockTypes } from "@wordpress/blocks";
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
import ExpandablePanel from "../expandable-panel/";
import ArrowDown from "components/svg/ArrowDown/";
import Desktop from "components/svg/Desktop/";
import Phone from "components/svg/Phone/";
import AlignCenter from "components/svg/AlignCenter/";
import AlignLeft from "components/svg/AlignLeft/";
import BlocksDivider from "components/svg/block-editor/BlocksDivider/";
import BlocksHeading from "components/svg/block-editor/BlocksHeading/";
import BlocksImage from "components/svg/block-editor/BlocksImage/";
import BlocksSpacer from "components/svg/block-editor/BlocksSpacer/";
import BlocksText from "components/svg/block-editor/BlocksText/";

import { createTheme } from '../../../../../theme';
const theme = createTheme({});
const { Slot: InspectorSlot, Fill: InspectorFill } = createSlotFill(
  "GroundhoggEmailBuilderSidebarInspector"
);

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
    blockPanel: {
      marginTop: "20px",
      overflow: "visible",
      "&:last-of-type": {
        paddingBottom: "20px",
      },
    },
    notesPanel: {
      marginTop: "20px",
    },
    notesPanelTitle: {
      color: "#102640",
      fontSize: "12px",
      fontWeight: "500",
      margin: "12.5px 0px 12.5px 20.5px",
    },
    notesPanelTextArea: {
      outline: "none",
      border: "none",
      width: "calc(100% - 41px)",
      margin: "0px 20.5px 23px 20.5px",
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
      color: "#0075FF",
      border: "1.2px solid #0075FF",
    },
    viewTypeButton: {
      display: "inline-block",
      border: "1.2px solid rgba(16, 38, 64, 0.15)",
      padding: "5px 5px 4px 5px",
      margin: "0px 0px 10px 15px",
      borderRadius: "5px",
      "&:hover": {
        border: "1.2px solid #0075FF",
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
      background: "#0075ff",
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
    blocksTitles: {
      color: 'rgba(0,0,0,0.7)',
      display: "inline-block",
      fontSize: "18px",
      width: "100%",
      textAlign: "center",
      fontWeight: "500",
      paddingTop: "20px",
      '& span':{
        cursor: 'pointer',
        margin: '18px 5px 5px 5px'
      }
    },
    selectedBlocktitles: {
      color: 'rgba(0,0,0,1.0)'
    },
    block: {
      position: "relative",
      display: "inline-block",
      margin: "10px",
      width: "82px",
      height: "78px",
      border: "1.2px solid rgba(0, 117, 255, 0.2)",
      borderRadius: "5px",
      textAlign: "center",
      fontWeight: "500",
      color: "#102640",
      "& svg, & path": {
        stroke: "#102640",
      },
      "&:hover": {
        color: "#fff",
        background: "#0075FF",
      },
      "&:hover svg, &:hover path": {
        stroke: "#fff",

        color: "#fff",
      },
    },
    blockFillHover: {
      '&:hover svg, &:hover path': {
        fill: "#fff",
      }
    },
    blockIcon: {
      margin: "15px 0 0 0",
    },
    blockName: {
      position: "absolute",
      bottom: "5px",
      width: "100%",
      textAlign: "center",
      fontWeight: "500",
    },
  }));

  const classes = useStyles();
  const [blocks, setBlocks] = useState(getBlockTypes());


  const blockPanel = sideBarBlockDisplayType === 'inspector' ? (
    <InspectorSlot bubblesVirtually />
  ) : (
    <>
      {blocks.map((block) => {
        const title = block.title.replace("Groundhogg - ", "");

        let icon = <BlocksImage />;
        let fillHoverClass = ''
        switch (title) {
          case "Spacer":
            icon = <BlocksSpacer stroke={""} fill={"none"} />;
            break;
          case "Divider":
            icon = (
              <BlocksDivider stroke={""} fill={"#000"} fillSecondary={"#ccc"} />
            );
            break;
          case "HTML":
            icon = <BlocksImage />;
            fillHoverClass = classes.blockFillHover;
            break;
          case "Button":
            icon = <BlocksImage />;
            fillHoverClass = classes.blockFillHover;
            break;
          case "Image":
            icon = <BlocksImage />;
            fillHoverClass = classes.blockFillHover;
            break;
          case "Heading":
            icon = <BlocksHeading />;
            fillHoverClass = classes.blockFillHover;
            break;
          case "paragraph":
            icon = <BlocksText />;
            fillHoverClass = classes.blockFillHover;
            break;
        }

        return (
          <div
            data-block={JSON.stringify(block)}
            className={
              `${classes.block} ${fillHoverClass} block-editor-block side-bar-drag-drop-block`
            }
          >
            <div className={classes.blockIcon}>{icon}</div>
            <div className={classes.blockName}>{title}</div>
          </div>
        );
      })}
    </>
  );

  const toggleAdditionalInfoContainer = () => {};

  // <PerfectScrollbar>
  // </PerfectScrollbar>
  return (
    <div
      className={classes.root}
      role="region"
      aria-label={__("Groundhogg Email Sidebar advanced settings.")}
      tabIndex="-1"
    >
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
          <span className={emailAlignment === 'left' ? classes.alignmentBtnSelected : classes.alignmentBtn} onClick={()=>{handleEmailAlignmentChange('left')}}>
            <AlignLeft />
          </span>
          <span className={emailAlignment === 'center' ? classes.alignmentBtnSelected : classes.alignmentBtn} onClick={()=>{handleEmailAlignmentChange('center')}}>
            <AlignCenter />
          </span>
        </div>

        <div className={classes.messageTypeContainer}>
          <div>{__("Message Type:")}</div>
          <select value={messageType} onChange={handleMessageType} label="">
            <option value={'marketing'}>Marketing</option>
            <option value={'transactional'}>Transactional</option>
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


      <Card className={classes.blockPanel}>
        <div className={classes.blocksTitles}>
        <span className={sideBarBlockDisplayType === 'blocks' ? classes.selectedBlocktitles : ''}>Blocks</span>
        <span className={sideBarBlockDisplayType === 'inspector' ? classes.selectedBlocktitles : ''}>Inspector</span>
        </div>
        {blockPanel}</Card>
      <Card className={classes.notesPanel}>
        <div className={classes.notesPanelTitle}>Notes</div>
        <textarea
          notes={notes}
          onChange={handleChangeNotes}
          className={classes.notesPanelTextArea}
          placeholder="Click here to add a custom note..."
        />
      </Card>
    </div>
  );
};

Sidebar.InspectorFill = InspectorFill;

export default Sidebar;
