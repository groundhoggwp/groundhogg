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

/**
 * External dependencies
 */
import { makeStyles } from "@material-ui/core/styles";
import { useEffect, useState } from "@wordpress/element";
import TextField from "@material-ui/core/TextField/TextField";
import Grid from "@material-ui/core/Grid/Grid";

/**
 * Internal dependencies
 */

const useStyles = makeStyles((theme) => ({
  root: {},
  searchField: {
    width: "calc(100% - 20px)",
    margin: "10px",
  },
}));

const { Slot: InspectorSlot, Fill: InspectorFill } = createSlotFill(
  "GroundhoggEmailBuilderSidebarInspector"
);

const Sidebar = () => {
  const classes = useStyles();

  const [blocks, setBlocks] = useState(getBlockTypes());
  const [search, setSearch] = useState("");
  let blockTypes = getBlockTypes();

  const handleOnChange = (e) => {
    setSearch(e.target.value);
    updateBlocks(e.target.value.trim());
  };

  const updateBlocks = (searchTerm) => {
    if (searchTerm === "") {
      setBlocks(getBlockTypes());
    } else {
      const newBlocks = getBlockTypes().filter(
        (block) =>
          block.title.split(" - ")[1].toLowerCase().indexOf(search) !== -1
      );
      setBlocks(newBlocks);
    }
  };

  return (
    <div
      className="groundhogg-email-sidebar"
      role="region"
      aria-label={__("Groundhogg Email Sidebar advanced settings.")}
      tabIndex="-1"
    >
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
              <div className="block-editor-block side-bar-drag-drop-block">
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
      </Panel>
      <Panel header={__("Inspector")}>
        <InspectorSlot bubblesVirtually />
      </Panel>
    </div>
  );
};

Sidebar.InspectorFill = InspectorFill;

export default Sidebar;
