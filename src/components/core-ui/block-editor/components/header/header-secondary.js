import { __, _x } from '@wordpress/i18n';
import {
	Button
} from '@wordpress/components';
import {
	plus,
	people,
	page,
	megaphone,
	mobile,
	update,
	desktop
} from '@wordpress/icons';
import { useRef, Fragment } from '@wordpress/element';
import ToolbarItem from './toolbar-item'; // Stop-gap while WP catches up.

function HeaderSecondary() {
	const inserterButton = useRef();

	/* const { setIsInserterOpened } = useDispatch( 'core/edit-post' ); */ // Consider adding to core actions
	const isInserterOpened = false;
	const isTextModeEnabled = false;
	const showIconLabels = false;
	const isInserterEnabled = false; // May connect to GH core state.

	return (
		<Fragment>
			<ToolbarItem
				ref={ inserterButton }
				as={ Button }
				className="groundhogg-header-toolbar__mode-toggle"
				isPrimary
				isPressed={ isInserterOpened }
				onMouseDown={ ( event ) => {
					event.preventDefault();
				} }
				icon={ plus }
				/* translators: button label text should, if possible, be under 16
		characters. */
				label={ _x(
					'Toggle between HTML and Visual Mode',
					'Generic label for mode toggle button'
				) }
			>
				{ __( 'Toggle Editor Mode' ) }
			</ToolbarItem>
			<ToolbarItem
				ref={ inserterButton }
				as={ Button }
				className="groundhogg-header-toolbar__broadcast-link"
				isPrimary
				isPressed={ isInserterOpened }
				onMouseDown={ ( event ) => {
					event.preventDefault();
				} }
				icon={ megaphone }
				/* translators: button label text should, if possible, be under 16
		characters. */
				label={ _x(
					'Link to Broadcast',
					'Generic label for link to broadcasts'
				) }
			>
				{ __( 'Broadcast' ) }
			</ToolbarItem>
			<ToolbarItem
				ref={ inserterButton }
				as={ Button }
				className="groundhogg-header-toolbar__replacements-modal"
				isPrimary
				isPressed={ isInserterOpened }
				onMouseDown={ ( event ) => {
					event.preventDefault();
				} }
				icon={ people }
				/* translators: button label text should, if possible, be under 16
		characters. */
				label={ _x(
					'Open replacements list',
					'Generic label for replacements button'
				) }
			>
				{ __( 'Replacements' ) }
			</ToolbarItem>
			<ToolbarItem
				ref={ inserterButton }
				as={ Button }
				className="groundhogg-header-toolbar__alt-body-modal"
				isPrimary
				isPressed={ isInserterOpened }
				onMouseDown={ ( event ) => {
					event.preventDefault();
				} }
				icon={ page }
				/* translators: button label text should, if possible, be under 16
		characters. */
				label={ _x(
					'Open replacements list',
					'Generic label for replacements button'
				) }
			>
				{ __( 'Alt-Body' ) }
			</ToolbarItem>
			<ToolbarItem
				ref={ inserterButton }
				as={ Button }
				className="groundhogg-header-toolbar__update-and-test"
				isPrimary
				isPressed={ isInserterOpened }
				onMouseDown={ ( event ) => {
					event.preventDefault();
				} }
				icon={ update }
				/* translators: button label text should, if possible, be under 16
		characters. */
				label={ _x(
					'Update and Test Link',
					'Generic label for replacements button'
				) }
			>
				{ __( 'Update and Test' ) }
			</ToolbarItem>
			<ToolbarItem
				ref={ inserterButton }
				as={ Button }
				className="groundhogg-header-toolbar__mobile-device-toggle"
				isPrimary
				isPressed={ isInserterOpened }
				onMouseDown={ ( event ) => {
					event.preventDefault();
				} }
				icon={ mobile }
				/* translators: button label text should, if possible, be under 16
		characters. */
				label={ _x(
					'Mobile Device Toggle',
					'Generic label for mobile device toggle button'
				) }
			></ToolbarItem>
			<ToolbarItem
				ref={ inserterButton }
				as={ Button }
				className="groundhogg-header-toolbar__large-device-toggle"
				isPrimary
				isPressed={ isInserterOpened }
				onMouseDown={ ( event ) => {
					event.preventDefault();
				} }
				icon={ desktop }
				/* translators: button label text should, if possible, be under 16
		characters. */
				label={ _x(
					'Desktop Preview Toggle',
					'Generic label for desktop preview button'
				) }
			></ToolbarItem>
		</Fragment>
	);
}

export default HeaderSecondary;