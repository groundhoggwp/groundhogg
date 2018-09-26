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
    AlignmentToolbar,
    BlockControls,
    BlockAlignmentToolbar,
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
    'groundhogg/button',
    {
        title: __( 'Submit Button', 'groundhogg' ),
        description: __( 'Collect the last name of a lead.', 'groundhogg'),
        category: 'groundhogg',
        icon: 'feedback',
        keywords: [
            __( 'Submit', 'groundhogg' ),
            __( 'Button', 'groundhogg' ),
            __( 'Form', 'groundhogg' ),
        ],
        attributes: {

            buttonText: {
                type: 'array',
                source: 'children',
                selector: '.button-text',
                default: __( 'Submit' , 'groundhogg' )
            },

            textAlignment: {
                type: 'string',
                default: 'center',
            },

            width: {
                type: 'string',
                default: '80'
            },

            fontSize: {
                type: 'string',
                default: '24'
            },

            borderColor: {
                type: 'string',
                default: 'rgb(207, 46, 46)'
            },

            backgroundColor: {
                type: 'string',
                default: 'rgb(207, 46, 46)'
            },

            textColor: {
                type: 'string',
                default: '#ffffff'
            }
        },
        edit: props => {
            const { attributes: { buttonText, textAlignment, width, fontSize, borderColor, backgroundColor, textColor },
                className, setAttributes } = props;

            const onChangeText =buttonText=> { setAttributes( {buttonText} ) };

            return [
                <BlockControls>
                    <AlignmentToolbar
                        value={ textAlignment }
                        onChange={ textAlignment => setAttributes( { textAlignment } ) }
                    />
                </BlockControls>,
                <InspectorControls>
                    <PanelBody
                        title={ __( 'Field Options', 'groundhogg' ) }
                    >
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
                    style={{
                        textAlign: textAlignment
                    }}
                    >
                        <button
                            style={ {
                                // textAlign: textAlignment,
                                display: 'inline-block',
                                width: width + '%',
                                fontSize: fontSize + 'px',
                                color: textColor,
                                borderColor: borderColor,
                                backgroundColor: backgroundColor,
                            } }
                        >
                            <RichText
                                className={classnames('gh-submit-button')}
                                placeholder={ __( 'Submit...' ) }
                                tagName="span"
                                stlye={{
                                    textAlign: 'center'
                                }}
                                onChange={(buttonText) => setAttributes( {buttonText} )}
                                value={buttonText}
                            />
                        </button>
                    </div>
                </div>
            ];
        },
        save: props => {

            const { attributes: { buttonText, textAlignment, width, fontSize, borderColor, backgroundColor, textColor },
                className, setAttributes } = props;

            return (
                <div
                    className={ classnames( className, ' gh-form-field' ) }
                >
                    <div style={ {
                        textAlign: textAlignment
                    } }>
                        <button
                            className={classnames(
                                'gh-submit-button',
                            )}
                            type="button"
                            value="submit"
                            style={ {
                                width: width + '%',
                                fontSize: fontSize + 'px',
                                color: textColor,
                                borderColor: borderColor,
                                backgroundColor: backgroundColor,
                            } }
                        >
                            <span
                                className={classnames( 'button-text' )}
                            >
                                { buttonText }
                            </span>
                        </button>
                    </div>
                </div>
            );
        },

    },
);
