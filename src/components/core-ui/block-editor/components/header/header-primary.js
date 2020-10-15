import { useViewportMatch } from '@wordpress/compose';
import { __, _x } from '@wordpress/i18n';
import {
	BlockToolbar,
	NavigableToolbar,
	BlockNavigationDropdown,
} from '@wordpress/block-editor';
import {
	Button,
} from '@wordpress/components';
import { plus } from '@wordpress/icons';
import { useRef } from '@wordpress/element';
import ToolbarItem from './toolbar-item'; // Stop-gap while WP catches up.

function HeaderToolbar() {
	const inserterButton = useRef();

	/* const { setIsInserterOpened } = useDispatch( 'core/edit-post' ); */ // Consider adding to core actions
	const isInserterOpened = false;
	const isTextModeEnabled = false;
	const showIconLabels = false;
	const displayBlockToolbar = true; // May connect to GH core state.
	const isInserterEnabled = false; // May connect to GH core state.

	const isWideViewport = useViewportMatch( 'wide' );


	const toolbarAriaLabel = displayBlockToolbar
		? /* translators: accessibility text for the editor toolbar when Top Toolbar is on */
		  __( 'Document and block tools' )
		: /* translators: accessibility text for the editor toolbar when Top Toolbar is off */
		  __( 'Document tools' );

	const overflowItems = (
		<>
			<ToolbarItem
				as={ BlockNavigationDropdown }
				isDisabled={ isTextModeEnabled }
				showTooltip={ ! showIconLabels }
				isTertiary={ showIconLabels }
			/>
		</>
	);

	return (
		<NavigableToolbar
			className="groundhogg-header-toolbar edit-post-header-toolbar"
			aria-label={ toolbarAriaLabel }
		>
			<div className="groundhogg-header-toolbar__left edit-post-header-toolbar__left">
				<ToolbarItem
					ref={ inserterButton }
					as={ Button }
					className="groundhogg-header-toolbar__inserter-toggle edit-post-header-toolbar__inserter-toggle"
					isPrimary
					isPressed={ isInserterOpened }
					onMouseDown={ ( event ) => {
						event.preventDefault();
					} }
					disabled={ ! isInserterEnabled }
					icon={ plus }
					/* translators: button label text should, if possible, be under 16
			characters. */
					label={ _x(
						'Add block',
						'Generic label for block inserter button'
					) }
					showTooltip={ ! showIconLabels }
				>
					{ showIconLabels && __( 'Add' ) }
				</ToolbarItem>

			</div>

			{ displayBlockToolbar && (
				<div className="groundhogg-header-toolbar__block-toolbar edit-post-header-toolbar__block-toolbar">
					<BlockToolbar hideDragHandle />
				</div>
			) }
		</NavigableToolbar>
	);
}

export default HeaderToolbar;