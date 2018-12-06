/**
 * Block dependencies
 */
import icon from './icon';
import './style.scss';

/**
 * Internal block libraries
 */
const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { RichText } = wp.editor;

/**
 * Register block
 */
export default registerBlockType(
    'jsforwphowto/demo',
    {
        title: __( 'Demo Block', 'jsforwphowto' ),
        description: __( 'How to use the RichText component for building your own editable blocks.', 'jsforwphowto' ),
        category: 'common',
        icon: {
            background: '#0073AA',
            src: icon
        },
        keywords: [
            __( 'How to', 'jsforwphowto' ),
            __( 'Example', 'jsforwphowto' ),
            __( 'RichText', 'jsforwphowto' ),
        ],
        supports: {
            html: false
        },
        attributes: {
            message: {
                type: 'array',
                source: 'children',
                selector: '.message-body',
            }
        },
        edit: props => {
            const { attributes: { message }, className, setAttributes } = props;
            const onChangeMessage = message => { setAttributes( { message } ) };
            return (
                <div className={ className }>
                <h2>{ __( 'Call to Action', 'jsforwphowto' ) }</h2>
            <RichText
            tagName="div"
            multiline="p"
            placeholder={ __( 'Add your custom message', 'jsforwphowto' ) }
            onChange={ onChangeMessage }
            value={ message }
            />
            </div>
        );
        },
        save: props => {
            const { attributes: { message } } = props;
            return (
                <div>
                <h2>{ __( 'Call to Action', 'jsforwphowto' ) }</h2>
            <div class="message-body">
                { message }
                </div>
                </div>
        );
        },
    },
);







/**
 * Block dependencies
 */
import classnames from 'classnames';
// import icons from './icons';
// import './style.scss';

/**
 * Internal block libraries
 */


/*
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
/*
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
*/