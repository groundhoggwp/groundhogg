import Editor from './editor';
import './index.scss';
import './components/blocks';
import {
	setDefaultBlockName,
} from '@wordpress/blocks';
/**
 * Block Editor component
 *
 * @link https://developer.wordpress.org/block-editor/packages/packages-block-editor/#SETTINGS_DEFAULTS
 * @todo Determine how tightly to couple Block Editor to "email"
 */
export default function EditorComponent( { email } ) {
  setDefaultBlockName( 'groundhogg/paragraph' );
  return ( <Editor email={email} settings={ window.Groundhogg.preloadSettings } /> );
}