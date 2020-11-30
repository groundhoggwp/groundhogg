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
  Inserter,
  // InserterListItem
} from "@wordpress/block-editor";
import {
  getBlockTypes,
  // InserterListItem
} from "@wordpress/blocks";
/**
 * External dependencies
 */
import TextField from '@material-ui/core/TextField/TextField'
/**
 * Internal dependencies
 */


const { Slot: InspectorSlot, Fill: InspectorFill } = createSlotFill(
  "GroundhoggEmailBuilderSidebarInspector"
);

console.log(Inserter)
// <Box className="">
//   <Grid container spacing={ 2 }>
//
//   </Grid>
// </Box>
//TODO: Match more closely to core edit-post


function Sidebar() {
  let blockTypes = getBlockTypes()
  console.log(asdfsadf, blockTypes)
  let search = ""
  return (
    <div
      className="groundhogg-email-sidebar"
      role="region"
      aria-label={__("Groundhogg Email Sidebar advanced settings.")}
      tabIndex="-1"
    >
      <Panel header={__("Blocks")}>
          <TextField
            value={ search }
            label={ 'Search' }
            type={ 'search' }
            variant={ 'outlined' }
            size={ 'small' }
            fullWidth
          />
        <div id="yes-drop" className="drag-drop">
          {" "}
          #yes-drop{" "}

        </div>
      </Panel>
      <Panel header={__("Inspector")}>
        <InspectorSlot bubblesVirtually />
      </Panel>
    </div>
  );
}

Sidebar.InspectorFill = InspectorFill;

export default Sidebar;
