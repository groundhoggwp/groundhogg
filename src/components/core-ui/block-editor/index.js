import {
	BlockEditorKeyboardShortcuts,
	BlockEditorProvider,
	BlockList,
	BlockInspector,
	WritingFlow,
	ObserveTyping,
} from '@wordpress/block-editor';

import {
	Popover,
	SlotFillProvider,
	DropZoneProvider,
} from '@wordpress/components';

import { useEffect, useState } from '@wordpress/element';

import { registerCoreBlocks } from "@wordpress/block-library";
import Paper from "@material-ui/core/Paper";
import Grid from "@material-ui/core/Grid";
import '@wordpress/format-library';

export default function MyEditorComponent() {
	const [ blocks, updateBlocks ] = useState( [] );

	useEffect( () => {
		registerCoreBlocks();
	}, [] );

	return (
			<SlotFillProvider>
				<DropZoneProvider>
					<BlockEditorProvider
						value={ blocks }
						onInput={ updateBlocks }
						onChange={ updateBlocks }
					>
          <Grid container spacing={3}>
            <Grid item xs={9}>
              <Paper>
              <div className="editor-styles-wrapper">
                <Popover.Slot name="block-toolbar" />
                <BlockEditorKeyboardShortcuts />
                <WritingFlow>
                  <ObserveTyping>
                    <BlockList />
                  </ObserveTyping>
                </WritingFlow>
              </div>
              </Paper>
              </Grid>
              <Grid item xs={3}>
                <Paper>
                  <BlockInspector />
                </Paper>
              <Popover.Slot />
              </Grid>
            </Grid>
					</BlockEditorProvider>
				</DropZoneProvider>
			</SlotFillProvider>
  );
}