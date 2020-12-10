import { __ } from '@wordpress/i18n';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { Fragment } from '@wordpress/element';
import { BlockFormatControls, MediaUploadCheck } from '@wordpress/block-editor';
import { MediaUpload } from '@wordpress/media-utils';
import { ToolbarGroup, ToolbarButton, Icon } from '@wordpress/components';
import { select, dispatch, useSelect } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import './style.scss';
const wprmCreateIcon = () => (
	<Icon width={30} height={30} icon={} />
);
const insertBlock = ( name, atts ) => {
	let insertionPoint = select( 'core/block-editor' ).getBlockInsertionPoint();
	let insertedBlock = createBlock( name, atts );
	dispatch( 'core/block-editor' ).insertBlock( insertedBlock, insertionPoint.index, insertionPoint.rootClientId );
	dispatch( 'core/block-editor' ).selectBlock( insertedBlock.clientId );
}
const withBlockControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const {
			editMode,
			name,
			attributes,
			setAttributes
		} = props;
		const recipeBlockName = 'wp-recipe-maker/recipe';
		const handleCreateClick = (evt) => insertBlock( recipeBlockName )
		const createProps = {
			onClick: handleCreateClick,
			className: "rte-insert-recipe-button",
			label: 'Insert Recipe',
			icon: wprmCreateIcon
		}
		return (
			<Fragment>
				<BlockEdit { ...props } />
				<BlockFormatControls>
					<ToolbarGroup>
						<ToolbarButton {...createProps} />
					</ToolbarGroup>
				</BlockFormatControls>
			</Fragment>
		);
	};
}, 'withInspectorControl' );
addFilter(
	'editor.BlockEdit',
	'fe-zao/with-block-controls',
	withBlockControls,
	9999
);
