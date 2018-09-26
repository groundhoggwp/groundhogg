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
} = wp.components;


/**
  * Register block
 */
export default registerBlockType(
    'groundhogg/text-field',
    {
        title: __( 'Text Field', 'groundhogg' ),
        description: __( 'Collect custom information', 'groundhogg'),
        category: 'groundhogg',
        icon: 'feedback',
        keywords: [
            __( 'Text', 'groundhogg' ),
            __( 'Custom', 'groundhogg' ),
            __( 'Form', 'groundhogg' ),
        ],
        attributes: {

            label: {
                type: 'array',
                source: 'children',
                selector: 'label',
            },
            required: {
                type: 'boolean',
                default: true,
            },
            showLabel: {
                type: 'boolean',
                default: true,
                selector: 'label',
            },
            placeholder: {
                type: 'string',
                selector: 'input',
                source: 'attribute',
                attribute: 'placeholder'
            },
            id: {
                type: 'string',
                //default: __( 'Pet\'s Name', 'groundhogg' ),
                selector: 'input',
                source: 'attribute',
                attribute: 'id'
            },
            name: {
                type: 'string',
                //default: __( 'Pet\'s Name', 'groundhogg' ),
                selector: 'input',
                source: 'attribute',
                attribute: 'name'
            },
            width: {
                type: 'string',
                default: '80'
            },
            fontSize: {
                type: 'string',
                default: '16'
            },
            borderColor: {
                type: 'string',
                default: '#444444'
            },
            backgroundColor: {
                type: 'string',
                default: '#f1f1f1'
            },
            textColor: {
                type: 'string',
                default: '#000000'
            }
        },
        edit: props => {
            const { attributes: { label, required, showLabel, placeholder, id, name, width, fontSize, borderColor, backgroundColor, textColor },
                className, setAttributes } = props;

            function keyify( key ) {
                if ( key )
                    return key.toLowerCase().replace( / /g, '_' ).replace(/[^a-z_]/gi, '')
            }

            return [
                <InspectorControls>
                    <PanelBody
                        title={ __( 'Field Options', 'groundhogg' ) }
                    >
                        <PanelBody>
                            <ToggleControl
                                label={ __( 'Show Label', 'groundhogg' ) }
                                checked={ showLabel }
                                onChange={ showLabel => setAttributes( { showLabel } ) }
                                help={ __( 'Toggles the outside field label.', 'groundhogg' ) }
                            />
                        </PanelBody>
                        <PanelBody>
                            <ToggleControl
                                label={ __( 'Required', 'groundhogg' ) }
                                checked={ required }
                                onChange={ required => setAttributes( { required } ) }
                                help={ __( 'Toggles whether this field should be required.', 'groundhogg' ) }
                            />
                        </PanelBody>
                        <PanelBody>
                            <TextControl
                                label={ __( 'Placeholder Text', 'groundhogg' ) }
                                help={ __( 'Input placeholder text.', 'groundhogg' ) }
                                value={ placeholder }
                                onChange={ placeholder => setAttributes( { placeholder } ) }
                            />
                        </PanelBody>
                        <PanelBody>
                            <TextControl
                                label={ __( 'CSS ID', 'groundhogg' ) }
                                help={ __( 'The CSS ID of the field. Ex: pet_name ', 'groundhogg' ) }
                                value={ keyify( id ) }
                                onChange={ id => setAttributes( { id } ) }
                            />
                        </PanelBody>
                        <PanelBody>
                            <TextControl
                                label={ __( 'Meta Key', 'groundhogg' ) }
                                help={ __( 'The key you want to use when merging this contact info. For example entering `pet_name` would allow you to insert {_pet_name} into an email.', 'groundhogg' ) }
                                value={ keyify( name ) }
                                onChange={ name => setAttributes( { name } ) }
                            />
                        </PanelBody>
                        <PanelBody>
                            <RangeControl
                                beforeIcon="arrow-left-alt2"
                                afterIcon="arrow-right-alt2"
                                label={ __( 'Field Width', 'groundhogg' ) }
                                value={ width }
                                onChange={ width => setAttributes( { width } ) }
                                min={ 20 }
                                max={ 100 }
                            />
                        </PanelBody>
                        <PanelBody>
                            <RangeControl
                                beforeIcon="arrow-left-alt2"
                                afterIcon="arrow-right-alt2"
                                label={ __( 'Font Size', 'groundhogg' ) }
                                value={ fontSize }
                                onChange={ fontSize => setAttributes( { fontSize } ) }
                                min={ 10 }
                                max={ 32 }
                            />
                        </PanelBody>
                        <PanelColor
                            title={ __( 'Font Color', 'groundhogg' ) }
                            colorValue={ textColor }
                        >
                            <ColorPalette
                                value={ textColor }
                                onChange={ textColor => setAttributes( { textColor } ) }
                            />
                        </PanelColor>
                        <PanelColor
                            title={ __( 'Border Color', 'groundhogg' ) }
                            colorValue={ borderColor }
                        >
                            <ColorPalette
                                value={ borderColor }
                                onChange={ borderColor => setAttributes( { borderColor } ) }
                            />
                        </PanelColor>
                        <PanelColor
                            title={ __( 'Background Color', 'groundhogg' ) }
                            colorValue={ backgroundColor }
                        >
                            <ColorPalette
                                value={ backgroundColor }
                                onChange={ backgroundColor => setAttributes( { backgroundColor } ) }
                            />
                        </PanelColor>
                    </PanelBody>
                </InspectorControls>,
                <div
                    className={ className }
                >
                    <div
                        className={ classnames(
                            { 'hidden' : ! showLabel }
                        ) }
                    >
                        <RichText

                            tagName="div"
                            placeholder={ __( 'Pet\'s Name...' ) }
                            value={ label }
                            onChange={ ( label ) => props.setAttributes( { label } ) }
                        />

                    </div>
                    <input
                        type="text"
                        className='gh-input'
                        placeholder={placeholder}
                        style={ {
                            width: width + '%',
                            fontSize: fontSize + 'px',
                            color: textColor,
                            borderColor: borderColor,
                            backgroundColor: backgroundColor,
                        } }
                    />
                </div>
            ];
        },
        save: props => {

            const { attributes: { required, label, showLabel, placeholder, id, name, width, fontSize, borderColor, backgroundColor, textColor },
                className, setAttributes } = props;

            return (
                <div className={ classnames(
                    className,
                    'gh-form-field',
                    ) } >
                    <p>
                        {
                            showLabel && <div className={classnames(
                                'gh-input-label-container',
                                {'hidden': !showLabel}
                            ) } style={!showLabel && {display: 'none'}}>
                                <label htmlFor="gh-text-field" className="gh-input-label gh-text-field-label">{label}</label>
                            </div>
                        }
                        <div className="gh-input-container">
                            <input
                                type="text"
                                id={ id }
                                name={ name }
                                className={ classnames( 'gh-input', 'gh-text-field', 'gh-meta', { 'required': required } ) }
                                placeholder={placeholder}
                                style={ {
                                    width: width + '%',
                                    fontSize: fontSize + 'px',
                                    color: textColor,
                                    borderColor: borderColor,
                                    backgroundColor: backgroundColor,
                                } }
                            />
                        </div>
                    </p>
                </div>
            );
        },

    },
);
