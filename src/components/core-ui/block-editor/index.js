import { registerCoreBlocks } from '@wordpress/block-library';
import Editor from './editor';
import './index.scss';

/**
 * Block Editor component
 *
 * @link https://developer.wordpress.org/block-editor/packages/packages-block-editor/#SETTINGS_DEFAULTS
 * @todo Determine how tightly to couple Block Editor to "email"
 */
export default function EditorComponent( { email } ) {

  // @todo: we should be able to do away with this once we've registered our own blocks
  registerCoreBlocks();
  return ( <Editor email={email} settings={ window.Groundhogg.preloadSettings } /> );
}