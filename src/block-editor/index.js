// Make sure to load the block editor stylesheets too
// import '@wordpress/components/build-style/style.css';
// import '@wordpress/block-editor/build-style/style.css';

import {
  BlockEditorProvider,
  BlockList,
  BlockTools,
  WritingFlow,
  ObserveTyping,
} from '@wordpress/block-editor'
import { SlotFillProvider, Popover } from '@wordpress/components'

export default ({ blocks, onChange }) => {

  console.log(blocks)

  return (
    <BlockEditorProvider
      value={blocks}
      onInput={(blocks) => onChange(blocks)}
      onChange={(blocks) => onChange(blocks)}
    >
      <SlotFillProvider>
        <BlockTools>
          <WritingFlow>
            <ObserveTyping>
              <BlockList/>
            </ObserveTyping>
          </WritingFlow>
        </BlockTools>
        <Popover.Slot/>
      </SlotFillProvider>
    </BlockEditorProvider>
  )
}
