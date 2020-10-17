import { __, _x } from '@wordpress/i18n';
import Button from '@material-ui/core/Button'
import CodeIcon from '@material-ui/icons/Code';
import LineStyleIcon from '@material-ui/icons/LineStyle';
import SurroundSoundIcon from '@material-ui/icons/SurroundSound';
import FindReplaceIcon from '@material-ui/icons/FindReplace';
import ChromeReaderModeIcon from '@material-ui/icons/ChromeReaderMode';
import DesktopMacIcon from '@material-ui/icons/DesktopMac';
import SmartphoneIcon from '@material-ui/icons/Smartphone';
import UpdateIcon from '@material-ui/icons/Update';
import { Fragment } from '@wordpress/element';
import ToolbarItem from './toolbar-item'; // Stop-gap while WP catches up.
import Dialog from '../dialog'; // Stop-gap while WP catches up.

function HeaderSecondary() {

	/* const { setIsInserterOpened } = useDispatch( 'core/edit-post' ); */ // Consider adding to core actions
	const isInserterOpened = false;
	const isTextModeEnabled = false;
	const showIconLabels = false;
	const isInserterEnabled = false; // May connect to GH core state.

	return (
		<Fragment>
			<ToolbarItem
				as={ Button }
				className="groundhogg-header-toolbar__mode-toggle"
				variant="contained"
				color="primary"
				size="small"
				onMouseDown={ ( event ) => {
					event.preventDefault();
				} }
				startIcon={ ( isTextModeEnabled ) ? <LineStyleIcon /> : <CodeIcon /> }
				/* translators: button label text should, if possible, be under 16
		characters. */
				label={ _x(
					'Toggle between HTML and Visual Mode',
					'Generic label for mode toggle button'
				) }
			>
				{ __( 'Editor Mode' ) }
			</ToolbarItem>
			<ToolbarItem
				as={ Button }
				className="groundhogg-header-toolbar__broadcast-link"
				variant="contained"
				color="primary"
				size="small"
				onMouseDown={ ( event ) => {
					event.preventDefault();
				} }
				startIcon={ <SurroundSoundIcon /> }
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
				as={ Dialog }
				className="groundhogg-header-toolbar__replacements-modal"
				buttonIcon={ <FindReplaceIcon /> }
				buttonTitle={ __( 'Replacements' ) }
				title={ __( 'Replacements' ) }
				content={ __( 'Replacements Table. TBD on how we want to parse this in here.' ) }
				dialogButtons={ [ { color: 'primary', label: __( 'Insert' ) } ] }
				/* translators: button label text should, if possible, be under 16
		characters. */
				label={ _x(
					'Open replacements list',
					'Generic label for replacements button'
				) }
			/>
			<ToolbarItem
				as={ Dialog }
				buttonIcon={ <ChromeReaderModeIcon /> }
				className="groundhogg-header-toolbar__alt-body-modal"
				/* translators: button label text should, if possible, be under 16
		characters. */
				buttonTitle={ __( 'Email Alt-Body' ) }
				title={ __( 'Email Alt-Body' ) }
				content={ __( 'Alt Body Content. Will need to build out custom component here.' ) }
				dialogButtons={ [ { color: 'primary', label: __( 'Done' ) } ] }
				label={ _x(
					'Open replacements list',
					'Generic label for replacements button'
				) }
			/>
			<ToolbarItem
				as={ Button }
				className="groundhogg-header-toolbar__update-and-test"
				variant="contained"
				color="primary"
				size="small"
				onMouseDown={ ( event ) => {
					event.preventDefault();
				} }
				startIcon={ <UpdateIcon /> }
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
				as={ Button }
				size="small"
				className="groundhogg-header-toolbar__mobile-device-toggle"
				variant="contained"
				color="secondary"
				onMouseDown={ ( event ) => {
					event.preventDefault();
				} }
				startIcon={ <SmartphoneIcon /> }
				/* translators: button label text should, if possible, be under 16
		characters. */
				label={ _x(
					'Mobile Device Toggle',
					'Generic label for mobile device toggle button'
				) }
			></ToolbarItem>
			<ToolbarItem
				as={ Button }
				className="groundhogg-header-toolbar__large-device-toggle"
				variant="contained"
				color="secondary"
				onMouseDown={ ( event ) => {
					event.preventDefault();
				} }
				startIcon={ <DesktopMacIcon /> }
				size="small"
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