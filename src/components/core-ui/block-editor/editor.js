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
import { useEffect, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { serialize, parse, pasteHandler, rawHandler } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Notices from './components/notices';
import Header from './components/header';
import Sidebar from './components/sidebar';
import BlockEditor from './components/block-editor';
import { DateTime } from 'luxon';
import {
	CORE_STORE_NAME,
	EMAILS_STORE_NAME
} from 'data';

const Editor = ( { settings, email, history } ) => {
	const dispatch = useDispatch( EMAILS_STORE_NAME );

	const [ titleToggle, setTitleToggle ] = useState( false );

	const toggleTitleEdit = () => {
		setTitleToggle( ! titleToggle )
	}

	const {subject: defaultSubjectValue, pre_header: defaultPreHeaderValue, content: defaultContentValue } = email.data

	const [ title, setTitle ] = useState( defaultSubjectValue );
	const [ subject, setSubject ] = useState( defaultSubjectValue );
	const [ preHeader, setPreHeader ] = useState( defaultPreHeaderValue );
	const [ content, setContent ] = useState( defaultContentValue );

	const {
		editorMode,
		isSaving,
		item
	} = useSelect(
		( select ) => ( {
				editorMode: select( CORE_STORE_NAME ).getEditorMode(),
				isSaving: select( CORE_STORE_NAME ).isItemsUpdating(),
				item: select( EMAILS_STORE_NAME ).getItem( email.ID ),
			} ),
		[]
	);

	// console.log(email.data)
	//email data shape
	// {
	// 	ID,
	// 	content,
	// 	subject,
	// 	pre_header,
	// 	from_user,
	// 	author,
	// 	last_updated,
	// 	date_created,
	// 	status,
	// 	is_template,
	// 	title
	// }


	const handleTitle = (e) => {
		setTitle(e.target.value);
	}
	if ( ! item.hasOwnProperty( 'ID' ) ) {
		return null;
	}

	const handleSubjectChange = (e)=>{
		setSubject(e.target.value);

	}
	const handlePreHeaderChange = (e)=>{
		setPreHeader(e.target.value);
	}
	const handleContentChange = (blocks)=>{
		setContent(serialize(blocks));
	}

	const saveDraft = (e)=>{
		dispatch.updateItem( email.ID, { data: { subject, title, pre_header: preHeader, status: 'draft', content, last_updated: `${DateTime.local()} ${DateTime.local().toISOTime()}` } } );
	}

	const publishEmail = (e)=>{
		dispatch.updateItem( email.ID, { data: { subject, title,  pre_header: preHeader, status: 'ready', content, last_updated: `${DateTime.local()} ${DateTime.local().toISOTime()}` } } );
	}

	const closeEditor = (e)=>{
		history.goBack();
	}



	return (
		<>
			<FullscreenMode isActive={false} />
			<SlotFillProvider>
				<DropZoneProvider>
					<FocusReturnProvider>
						<InterfaceSkeleton
							header={<Header email={email} history={history} saveDraft={saveDraft} publishEmail={publishEmail} closeEditor={closeEditor} isSaving={isSaving} titleToggle={titleToggle} toggleTitleEdit={toggleTitleEdit} handleTitle={handleTitle} content={content} item={item} />}
							sidebar={
								<>
									<Sidebar />
									<ComplementaryArea.Slot scope="gh/v4/core" />
								</>
							}
							content={
								<>
									<Notices />
									{ editorMode !== 'text' && <BlockEditor settings={settings} subject={subject} handleSubjectChange={handleSubjectChange} preHeader={preHeader} handlePreHeaderChange={handlePreHeaderChange} content={content} handleContentChange={handleContentChange}/> }
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
