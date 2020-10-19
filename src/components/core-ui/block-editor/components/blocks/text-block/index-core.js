/**
 * WordPress dependencies
 */
import {__, _x} from '@wordpress/i18n';

import {registerBlockType , rawHandler, serialize } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import save from './save';

const {name} = metadata;

export {metadata, name};

export const settings = {
	title: _x('groundhoggg CLASSSIC BLOCK'),
	description: __('Use the classic WordPress editor.'),
	icon: 'shield',
	edit,
	save,
};


registerBlockType('groundhogg/text-block', settings);
