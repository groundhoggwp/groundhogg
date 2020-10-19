
import {__} from '@wordpress/i18n';
import {registerBlockType} from '@wordpress/blocks';
import {InspectorControls,  MediaUpload } from '@wordpress/block-editor';
import {PanelBody,RangeControl, SelectControl, TextControl} from '@wordpress/components';

registerBlockType('groundhogg/image', {
	title: __('Groundhogg - Image'), // Block title.
	icon: 'shield', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'common', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__('Groundhogg - Image'),
		__('Image'),
		__('groundhogg')
	],
	attributes: {
		width: {
			type: "number",
			default: 300
		},
		alignment: {
			type: 'string',
			default: 'center'
		},
		image: {
			type: 'string',
			default: 'https://via.placeholder.com/350x150'
		},
		alt: {
			type: 'string',

		},
		title: {
			type: 'string'
		},
		link: {
			type: 'string',

		}
	},
	edit: (props) => {
		// Creates a <p class='wp-block-cgb-block-react'></p>.
		const {
			attributes: {
				width,
				alignment,
				image,
				alt,
				title,
				link
			},
			setAttributes,
		} = props;


		const MIN_SPACER_WIDTH = 1;
		const MAX_SPACER_WIDTH = 600;
		const updateWidth = (value) => {
			setAttributes({
				width: value,
			});
		};

		const updateAlignment = (value) => {
			setAttributes({
				alignment: value,
			});
		}

		const updateImage = (value) => {
			setAttributes({
				image: value.url,
			});
		}

		const updateAlt= (value) => {
			setAttributes({
				alt: value,
			});
		}

		const updateTitle = (value) => {
			setAttributes({
				title: value,
			});
		}
		const updateLink = (value) => {
			setAttributes({
				link: value,
			});
		}


		const defaultStyles = {
			display: 'inline-block',
			maxWidth: '100%',
			width: width,
			verticalAlign: 'bottom'
		};


		return (
			<div className={props.className}>
				<InspectorControls>
					<PanelBody title={__('Image')}>
						<MediaUpload
							onSelect={updateImage}
							type="image"
							value={image}
							render={({open}) => {
								return (
									<button onClick={open}>
										{__('Select Image')}
									</button>
								);
							}}
						/>

						<TextControl
							onChange={updateAlt}
							value={alt}
							placeholder="Alt Tag"
							label="Alt Tag"
							className=""
						/>
						<TextControl
							onChange={updateTitle}
							value={title}
							placeholder="title"
							label="Image Title"
							className=""
						/>
					</PanelBody>
					<PanelBody title={__('Width settings')}>
						<RangeControl
							label={__('Width in pixels')}
							min={MIN_SPACER_WIDTH}
							max={Math.max(MAX_SPACER_WIDTH, width)}
							value={width}
							onChange={updateWidth}
						/>
					</PanelBody>
					<PanelBody title={__('Alignment')}>
						<SelectControl
							label="Alignment"
							value={alignment}
							options={[
								{label: __('Center'), value: 'center'},
								{label: __('Left'), value: 'left'},
								{label: __('Right'), value: 'right'},
							]}
							onChange={updateAlignment}
						/>
					</PanelBody>
					<PanelBody title={__('Link setting')}>
						<TextControl
							onChange={updateLink}
							value={link}
							placeholder="http://www.google.com"
							label="Redirect Link"
							className=""
						/>
					</PanelBody>
				</InspectorControls>
				<div className="image-wrapper" align={alignment}>
					<a href={link}>
						<img
							width={width}
							src={image}
							style={defaultStyles}
							title={title}
							alt={alt}/>
					</a>
				</div>

			</div>
		);
	},
	save: (props) => {
		const {
			attributes: {
				width,
				alignment,
				image,
				alt,
				title,
				link
			},
		} = props;

		const defaultStyles = {
			display: 'inline-block',
			maxWidth: '100%',
			width: width,
			verticalAlign: 'bottom'
		};

		return (
			<div className="image-wrapper" align={alignment}>
				<a href={link}>
					<img
						width={width}
						src={image}
						style={defaultStyles}
						title={title}
						alt={alt}/>
				</a>
			</div>
		);
	},
});
