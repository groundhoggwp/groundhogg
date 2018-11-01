/**
 * Block dependencies
 */
import classnames from 'classnames';
// import icons from './icons';
// import './style.scss';

/**
 * Internal block libraries
 */
const { __ } = wp.i18n;
const {
    registerBlockType,
} = wp.blocks;
const {
    RichText,
    // AlignmentToolbar,
    // BlockControls,
    // BlockAlignmentToolbar,
    InspectorControls,
    ColorPalette,
} = wp.editor;
const {
    PanelBody,
    PanelRow,
    PanelColor,
    FormToggle,
    ToggleControl,
    RangeControl,
    TextControl,
    ServerSideRender,
} = wp.components;

console.log( 'Im here! ');
/**
  * Register block
 */
export default registerBlockType(
    'groundhogg/form',
    {
        title: __( 'Groundhogg Form', 'groundhogg' ),
        description: __( 'Display a Groundhogg Form', 'groundhogg'),
        category: 'groundhogg',
        icon: 'feedback',
        keywords: [
            __( 'Form', 'groundhogg' ),
            __( 'Groundhogg', 'groundhogg' ),
        ],
        attributes: {

            id: {
                type: 'string',
                selector: 'form',
                source: 'attribute',
                attribute: 'id'
            },

        },
        edit: props => {
            const { attributes: { id },
                className, setAttributes } = props;

            return [
                <ServerSideRender
                    block="groundhogg/form"
                    attributes={ props.attributes }
                />
            ];
        },
        save: props => {

            return <ServerSideRender
                block="groundhogg/form-saved"
                attributes={ props.attributes }
            />;

        }

    },
);
