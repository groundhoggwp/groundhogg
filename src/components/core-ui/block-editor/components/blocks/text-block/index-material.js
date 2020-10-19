import {__} from '@wordpress/i18n';
import {registerBlockType, rawHandler, serialize} from '@wordpress/blocks';
/**
 * External dependencies
 */
import {debounce} from 'lodash';

/**
 * WordPress dependencies
 */
import {RichText, BlockEditorProvider,} from '@wordpress/block-editor';
import {ToolbarGroup, ToolbarButton, TextareaControl} from '@wordpress/components';
import {useEffect, useRef, RawHTML, Fragment, useState} from '@wordpress/element';

import Editor from 'material-ui-editor'


const ConvertToBlocksButton = ({clientId}) => {
	const {replaceBlocks} = useDispatch('groundhogg/text-block');
	const block = useSelect(
		(select) => {
			return select('groundhogg/text-block').getBlock(clientId);
		},
		[clientId]
	);

	return (
		<ToolbarButton
			onClick={() =>
				replaceBlocks(
					block.clientId,
					rawHandler({HTML: serialize(block)})
				)
			}
		>
			{__('Convert to blocks')}
		</ToolbarButton>
	);
};


registerBlockType('groundhogg/text-block', {
	title: __('Groundhogg - Text Block'), // Block title.
	icon: 'shield', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'common', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__('Groundhogg- TEXT block'),
		__('text'),
	],
	"attributes": {
		"content": {
			"type": "string",
			"source": "html",
			selector: 'div'
		},
		sendContent: {
			type: 'bool',
			default: false
		}
	},
	edit: (props) => {



		const {
			attributes: {
				content,
				sendContent
			},
			setAttributes,
		} = props;


		const updateContent = (value) => {
			// console.log(value);
			// console.log(value);
			setAttributes({
				content: value,
				sendContent :true
			});

		};


		return (
			<div>
				{sendContent ? <Editor
						onChange={updateContent}
					/> :
					<Editor

						onChange={updateContent}
					> {content} </Editor>
				}
			</div>
		);

	},
	save: (props) => {
		const {
			attributes: {
				content,
			}
		} = props;

		// console.log(value);
		return <RawHTML>{content}</RawHTML>;
	},
});
