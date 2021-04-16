/**
 * WordPress dependencies
 */
import {
	Popover,
	SlotFillProvider,
	DropZoneProvider,
	FocusReturnProvider,
} from '@wordpress/components';

import { InterfaceSkeleton, FullscreenMode } from "@wordpress/interface";


/**
 * Internal dependencies
 */
import Notices from './components/notices';
import Header from './components/header';
import Sidebar from './components/sidebar';
import BlockEditor from './components/block-editor';

// Makes formatting in our custom version basically impossible
// <InterfaceSkeleton
// 	header={}
// 	sidebar={}
// 	content={
//
// 	}
// />


function Editor( { settings } ) {
	return (
		<>
			<FullscreenMode isActive={false} />
			<SlotFillProvider>
				<DropZoneProvider>
					<FocusReturnProvider>

						<Header />

						<Sidebar />

						<>
							<Notices />
							<BlockEditor settings={settings} />
						</>

						<Popover.Slot />
					</FocusReturnProvider>
				</DropZoneProvider>
			</SlotFillProvider>
		</>
	);
}

export default Editor;
