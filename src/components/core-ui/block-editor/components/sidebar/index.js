/**
 * WordPress dependencies
 */
import { createSlotFill, Panel } from "@wordpress/components";
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
  Inserter
} from "@wordpress/block-editor";
/**
 * Internal dependencies
 */


const { Slot: InspectorSlot, Fill: InspectorFill } = createSlotFill(
  "GroundhoggEmailBuilderSidebarInspector"
);

//TODO: Match more closely to core edit-post
function Sidebar() {
  return (
    <div
      className="groundhogg-email-sidebar"
      role="region"
      aria-label={__("Groundhogg Email Sidebar advanced settings.")}
      tabIndex="-1"
    >
      <Panel header={__("Blocks")}>
        <Inserter/>
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
