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
	FullscreenMode,
	ComplementaryArea
} from "@wordpress/interface";

import {
	PostTextEditor
} from '@wordpress/editor';

import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Notices from './components/notices';
import Header from './components/header';
import Sidebar from './components/sidebar';
import BlockEditor from './components/block-editor'
import { CORE_STORE_NAME } from 'data/core';

function Editor( { settings, email } ) {

	const {
		editorMode,
	} = useSelect(
		( select ) => ( {
				editorMode: select( CORE_STORE_NAME ).getEditorMode(),
			} ),
		[]
	);

	return (
		<>
			<FullscreenMode isActive={false} />
			<SlotFillProvider>
				<DropZoneProvider>
					<FocusReturnProvider>
						<InterfaceSkeleton
							header={<Header email={email} />}
							sidebar={
								<>
									<Sidebar />
									<ComplementaryArea.Slot scope="gh/v4/core" />
								</>
							}
							content={
								<>
									<Notices />
									{ editorMode !== 'text' && <BlockEditor settings={settings} /> }
									{ editorMode === 'text' && <PostTextEditor /> }
								</>
							}
						/>
						<Popover.Slot />
					</FocusReturnProvider>
				</DropZoneProvider>
			</SlotFillProvider>
		</>
	);
}

export default Editor;