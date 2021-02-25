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
  FormHelperText,
  InputLabel,
  MenuItem,
  Select,
  TextField,
} from "@material-ui/core";

/**
 * Internal dependencies
 */
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
    marginTop: "10px",
  },
  blockPanel: {
    marginTop: "20px",
  },
  emailControls: {
    height: "350px",
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
  messageTypeContainer: {
    display: "inline-block",
    marginTop: "20px",
  },
  additionalInfoContainer: {
    width: "100%",
    borderRadius: "7px",
    background: "#E7EEFB",
  },
  blocksTitle:{
    display: 'block',
    fontSize: '18px',
    width: '50px',
    fontWeight: '500',
    margin: '18px auto 5px auto'

  },
  block:{
    position: 'relative',
    display: 'inline-block',
    margin: '10px',
    width: '84px',
    height: '80px',
    border: '1.2px solid rgba(0, 117, 255, 0.2)',
    borderRadius: '5px',
    textAlign: 'center',
    fontWeight: '500',
    '&:hover':{
      color: '#fff',
      background: '#0075FF'
    },
    '& svg, & path':{
      fill: '#0075FF'
    }
  },
  blockIcon: {
    margin: '15px 0 0 0',

  },
  blockName:{
    position: 'absolute',
    bottom: '5px',
    width: '100%',
    textAlign: 'center'
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

  // import BlocksDivider from "components/svg/block-editor/BlocksDivider/";
  // import BlocksHeading from "components/svg/block-editor/BlocksHeading/";
  // import BlocksImage from "components/svg/block-editor/BlocksImage/";
  // import BlocksSpacer from "components/svg/block-editor/BlocksSpacer/";
  // import BlocksText from "components/svg/block-editor/BlocksText/";
  const blockPanel = isInspecting ? (
    <InspectorSlot bubblesVirtually />
  ) : (
    <>
      <div className={classes.blocksTitle}>Blocks</div>
      {blocks.map((block) => {
        return (
          <div
            data-block={JSON.stringify(block)}
            className={classes.block+" block-editor-block side-bar-drag-drop-block"}
          >
            <div className={classes.blockIcon}>
              <BlocksImage/>
            </div>

            <div className={classes.blockName}>
              {block.title.replace('Groundhogg - ', '')}
            </div>
          </div>
        );
      })}
    </>
  );

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

        <TextField
          className={classes.inputText}
          value={from}
          placeholder={"From"}
          variant={"outlined"}
          onChange={handleOnChange}
          fullWidth
        />
        <TextField
          className={classes.inputText}
          value={replyTo}
          placeholder={"Reply to"}
          variant={"outlined"}
          onChange={handleOnChange}
          fullWidth
        />

        <div className={classes.alignmentContainer}>
          <label>{__("Alignment")}</label>
          <br />
          <AlignLeft />
          <AlignCenter />
        </div>

        <FormControl className={classes.messageTypeContainer}>
          <InputLabel shrink id="demo-simple-select-placeholder-label-label">
            Message type:
          </InputLabel>
          <Select
            labelId="demo-simple-select-placeholder-label-label"
            id="demo-simple-select-placeholder-label"
            value={5}
            onChange={() => {}}
            displayEmpty
            className={classes.selectEmpty}
          >
            <MenuItem value="">
              <em>None</em>
            </MenuItem>
            <MenuItem value={10}>Marketing</MenuItem>
          </Select>
          <FormHelperText>Label + placeholder</FormHelperText>
        </FormControl>

        <div className={classes.additionalInfoContainer}>
          <label>{__("Additional info")}</label>
          <br />
        </div>
      </Card>

      <Card className={classes.blockPanel}>{blockPanel}</Card>
    </div>
  );
};

Sidebar.InspectorFill = InspectorFill;

export default Sidebar;
