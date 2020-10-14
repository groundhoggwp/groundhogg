/**
 * WordPress dependencies
 */
import { createSlotFill, Panel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const {
	Slot: InspectorSlot,
	Fill: InspectorFill
} = createSlotFill(
	'GroundhoggEmailBuilderSidebarInspector'
);

//TODO: Match more closely to core edit-post
function Sidebar() {
	return (
		<div
			className="groundhogg-email-sidebar"
			role="region"
			aria-label={ __( 'Groundhogg Email Sidebar advanced settings.' ) }
			tabIndex="-1"
		>
			<Panel header={ __( 'Inspector' ) }>
				<InspectorSlot bubblesVirtually />
			</Panel>
		</div>
	);
}

Sidebar.InspectorFill = InspectorFill;

export default Sidebar;