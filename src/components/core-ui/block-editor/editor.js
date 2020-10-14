/**
 * WordPress dependencies
 */
import {
	Popover,
	SlotFillProvider,
	DropZoneProvider,
	FocusReturnProvider,
} from '@wordpress/components';

import {
	InterfaceSkeleton,
	FullscreenMode
} from "@wordpress/interface";

import Grid from '@material-ui/core/Grid';

/**
 * Internal dependencies
 */
import Notices from './components/notices';
import Header from './components/header';
import Sidebar from './components/sidebar';
import BlockEditor from './components/block-editor'

function Editor( { settings, email } ) {

	return (
		<>
			<FullscreenMode isActive={false} />
			<SlotFillProvider>
				<DropZoneProvider>
					<FocusReturnProvider>
						<Grid container spacing="2">
							<Grid item xs="12"><Header email={email}/></Grid>
							<Grid item xs="9">
								<Notices />
								<BlockEditor settings={settings} />
							</Grid>
							<Grid item xs="3"><Sidebar /></Grid>
						</Grid>
						<Popover.Slot />
					</FocusReturnProvider>
				</DropZoneProvider>
			</SlotFillProvider>
		</>
	);
}

export default Editor;
