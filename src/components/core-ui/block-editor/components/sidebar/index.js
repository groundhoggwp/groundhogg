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
    width: "calc(100% - 10px)",
    padding: '5px 0px 5px 17px',
    marginTop: "10px",
  },
  blockPanel: {
    marginTop: "20px",
    overflow: "visible",
    '&:last-of-type':{
      paddingBottom: '20px'
    }
  },
  emailControls: {
    height: "287px",
    padding: "10px 22px 0 22px",
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
    display: "inline-block",
    marginTop: "20px",
    width: "115px",
  },
  alignmentBtn:{
    display: 'inline-block',
    width: '27px',
    height: '27px',
    borderRadius: '7px',
    margin: '15px 15px 34px 0px',
    cursor: 'pointer'
  },
  messageTypeContainer: {
    display: "inline-block",
    // marginTop: "20px",
    '& select' : {
      marginTop: '5px',
      padding: '7px 74px 7px 17px',
      border: '1.5px solid rgba(16, 38, 64, 0.1)'
    }
  },
  selectMessageType : {
    width: '100px',
    padding: '7px 0px 7px 17px'
  },
  additionalInfoContainer: {
    fontSize: "12px",
    width: "calc(100% + 19px)",
    marginLeft: "-22px",
    borderRadius: "7px",
    padding:"10px 0 13px 25px",
    fontWeight: "600",
    background: "#E7EEFB",
    cursor: 'pointer',
    "& svg": {
      float: 'right',
      margin: '8px 25px'
    },
  },
  blocksTitle:{
    display: 'block',
    fontSize: '18px',
    width: '50px',
    fontWeight: '500',
    margin: '18px auto 5px auto',
    paddingTop: '20px'

  },
  block:{
    position: 'relative',
    display: 'inline-block',
    margin: '10px',
    width: '82px',
    height: '78px',
    border: '1.2px solid rgba(0, 117, 255, 0.2)',
    borderRadius: '5px',
    textAlign: 'center',
    fontWeight: '500',
    color: '#102640',
    '& svg, & path':{
      stroke: '#102640'
    },
    '&:hover':{
      color: '#fff',
      background: '#0075FF'
    },
    '&:hover svg, &:hover path':{
      stroke: '#fff',
      fill: '#fff',
      color: '#fff'
    }
  },
  blockIcon: {
    margin: '15px 0 0 0',

  },
  blockName:{
    position: 'absolute',
    bottom: '5px',
    width: '100%',
    textAlign: 'center',
    fontWeight: '500'
  }
}));

const { Slot: InspectorSlot, Fill: InspectorFill } = createSlotFill(
  "GroundhoggEmailBuilderSidebarInspector"
);

const Sidebar = ({
  isInspecting,
  sendTestEmail,
  handleViewTypeChange,
  replyTo,
  handleSetReplyTo,
  from,
  handleSetFrom,
}) => {
  const classes = useStyles();

  const [blocks, setBlocks] = useState(getBlockTypes());
  const [search, setSearch] = useState("");

  useEffect(() => {
    updateBlocks();
  }, [search]);

  const handleOnChange = (e) => {
    setSearch(e.target.value.trim());
  };

  const updateBlocks = () => {
    if (search === "") {
      setBlocks(getBlockTypes());
    } else {
      const newBlocks = getBlockTypes().filter(
        (block) =>
          block.title.split(" - ")[1].toLowerCase().indexOf(search) !== -1
      );
      setBlocks(newBlocks);
    }
  };

  const blockPanel = isInspecting ? (
    <InspectorSlot bubblesVirtually />
  ) : (
    <>
      <div className={classes.blocksTitle}>Blocks</div>
      {blocks.map((block) => {

        const title = block.title.replace('Groundhogg - ', '')

        let icon = <BlocksImage/>
        switch (title) {
          case 'Spacer':
            icon = <BlocksSpacer stroke={''} fill={'none'}/>
            break;
          case 'Divider':
            icon = <BlocksDivider  stroke={''} fill={'#000'} fillSecondary={'#ccc'}/>
            break;
          case 'HTML':
            icon = <BlocksImage/>
            break;
          case 'Button':
            icon = <BlocksImage/>
            break;
          case 'Image':
            icon = <BlocksImage/>
            break;
          case 'Heading':
            icon = <BlocksHeading/>
            break;
          case 'paragraph':
            icon = <BlocksText/>
            break;
        }

        return (
          <div
            data-block={JSON.stringify(block)}
            className={classes.block+" block-editor-block side-bar-drag-drop-block"}
          >
            <div className={classes.blockIcon}>
              {icon}
            </div>

            <div className={classes.blockName}>
              {title}
            </div>
          </div>
        );
      })}
    </>
  );

  const toggleAdditionalInfoContainer = () => {

  }

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
        <Button
          className={classes.sendTestButton}
          onClick={() => {
            sendTestEmail();
          }}
        >
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
          onChange={handleOnChange}
          fullWidth
        />
        <input
          className={classes.inputText}
          value={replyTo}
          placeholder={"Reply to"}
          onChange={handleOnChange}
          fullWidth
        />

        <div className={classes.alignmentContainer}>
          <div>{__("Alignment:")}</div>
          <span className={classes.alignmentBtn}><AlignLeft /></span>
          <span className={classes.alignmentBtn}><AlignCenter /></span>
        </div>

        <div className={classes.messageTypeContainer}>
          <div>{__("Message Type:")}</div>
          <select
            value={''}
            onChange={()=>{}}
            label=""
          >
            <option value={10}>none</option>
            <option value={20}>Marketing</option>
          </select>
        </div>

        <div className={classes.additionalInfoContainer}>
          <label onClick={toggleAdditionalInfoContainer}>{__("Additional options:")} <ArrowDown/></label>
          <br />
        </div>
      </Card>

      <Card className={classes.blockPanel}>{blockPanel}</Card>
    </div>
  );
};

Sidebar.InspectorFill = InspectorFill;

export default Sidebar;
