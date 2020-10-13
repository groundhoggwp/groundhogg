import {
  BlockInspector,
  BlockEditorProvider,
  BlockList,
  WritingFlow,
  ObserveTyping,
} from "@wordpress/block-editor";
import { SlotFillProvider, Popover } from "@wordpress/components";
import { useState } from "@wordpress/element";

// import '@wordpress/block-library/build-style/style.css';
// import '@wordpress/block-library/build-style/editor.css';
// import '@wordpress/block-library/build-style/theme.css';
import { registerCoreBlocks } from "@wordpress/block-library";
import Container from "@material-ui/core/Container";
import Paper from "@material-ui/core/Paper";
import Grid from "@material-ui/core/Grid";

registerCoreBlocks();
export default function MyEditorComponent() {
  const [blocks, updateBlocks] = useState([]);

  return (
    <BlockEditorProvider
      value={blocks}
      onInput={(blocks) => updateBlocks(blocks)}
      onChange={(blocks) => updateBlocks(blocks)}
    >
      <Grid container spacing={3}>
        <Grid item xs={9}>
          <Paper>
            <SlotFillProvider>
              <Popover.Slot name="block-toolbar" />
              <WritingFlow>
                <ObserveTyping>
                  <BlockList />
                </ObserveTyping>
              </WritingFlow>
              <Popover.Slot />
            </SlotFillProvider>
          </Paper>
        </Grid>
        <Grid item xs={3}>
            <Paper>
            <BlockInspector />
            </Paper>
        </Grid>
      </Grid>
    </BlockEditorProvider>
  );
}
