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
import {Button, TextField, InputLabel} from "@material-ui/core";
import FormHelperText from '@material-ui/core/FormHelperText';
import FormControl from '@material-ui/core/FormControl';
import MenuItem from '@material-ui/core/MenuItem';
import Select from '@material-ui/core/Select';
import DesktopMacIcon from '@material-ui/icons/DesktopMac';
import SmartphoneIcon from '@material-ui/icons/Smartphone';
import FormatAlignJustifyIcon from '@material-ui/icons/FormatAlignJustify';
import FormatAlignLeftIcon from '@material-ui/icons/FormatAlignLeft';
import FormatAlignRightIcon from '@material-ui/icons/FormatAlignRight';

/**
 * Internal dependencies
 */

const useStyles = makeStyles((theme) => ({
  root: {},
  searchField: {
    width: "calc(100% - 20px)",
    margin: "10px"
  },
}));

const { Slot: InspectorSlot, Fill: InspectorFill } = createSlotFill(
  "GroundhoggEmailBuilderSidebarInspector"
);

const Sidebar = ({isInspecting, handleViewTypeChange}) => {
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

  const blockPanel = isInspecting ?

      <Panel header={__("Inspector")}>
        <InspectorSlot bubblesVirtually />
      </Panel>
      :
      <Panel header={__("Blocks")}>
          <TextField
            className={classes.searchField}
            value={search}
            label={"Search"}
            type={"search"}
            variant={"outlined"}
            size={"small"}
            onChange={handleOnChange}
            fullWidth
          />
          <div className="side-bar-blocks-container">
            {blocks.map((block) => {
              return (
                <div
                  data-block={JSON.stringify(block)}
                  className="block-editor-block side-bar-drag-drop-block"
                >
                  <svg
                    aria-hidden="true"
                    role="img"
                    focusable="false"
                    xmlns="http://www.w3.org/2000/svg"
                    width="20"
                    height="20"
                    viewBox="0 0 20 20"
                    class="dashicon dashicons-shield"
                  >
                    <path d="M10 2s3 2 7 2c0 11-7 14-7 14S3 15 3 4c4 0 7-2 7-2zm0 8h5s1-1 1-5c0 0-5-1-6-2v7H5c1 4 5 7 5 7v-7z"></path>
                  </svg>
                  {block.title}
                </div>
              );
            })}
          </div>

          <Button>Additional options:</Button>
        </Panel>


  return (
    <PerfectScrollbar>
    <div
      className="groundhogg-email-sidebar"
      role="region"
      aria-label={__("Groundhogg Email Sidebar advanced settings.")}
      tabIndex="-1"
    >
      <Panel>
        <Button>{__("Send test email")}</Button>
        <Button onClick={() => { handleViewTypeChange('mobile') }}>
          <SmartphoneIcon/>
        </Button>
        <Button onClick={() => { handleViewTypeChange('desktop') }}>
          <DesktopMacIcon />
        </Button>

        <TextField
          className={classes.searchField}
          value={'from'}
          label={"From"}
          type={"search"}
          onChange={handleOnChange}
          fullWidth
        />
        <TextField
          className={classes.searchField}
          value={'from'}
          label={"Reply to"}
          type={"search"}
          onChange={handleOnChange}
          fullWidth
        />

        <label>{__("Alignment")}</label>
        <Button><FormatAlignJustifyIcon/></Button>
        <Button><FormatAlignLeftIcon/></Button>
        <Button><FormatAlignRightIcon/></Button>

        <FormControl className={classes.formControl}>
          <InputLabel shrink id="demo-simple-select-placeholder-label-label">
            Message type:
          </InputLabel>
          <Select
            labelId="demo-simple-select-placeholder-label-label"
            id="demo-simple-select-placeholder-label"
            value={5}
            onChange={()=>{}}
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



      </Panel>

      {blockPanel}

    </div>
    </PerfectScrollbar>
  );
};

Sidebar.InspectorFill = InspectorFill;

export default Sidebar;
