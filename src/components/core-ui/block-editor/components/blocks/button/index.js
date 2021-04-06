import { __ } from "@wordpress/i18n";
import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, PanelColorSettings } from "@wordpress/block-editor";
import { PanelBody, SelectControl, TextControl } from "@wordpress/components";





import BlocksButton from "components/svg/BlockEditor/BlocksButton/";


registerBlockType("groundhogg/button", {
  title: __("Groundhogg - Button"), // Block title.
  icon: BlocksButton, // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
  // icon: "shield", // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
  category: "common", // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
  description: "Add Space in your email",
  keywords: [__("Groundhogg - Divider")],
  attributes: {
    text: {
      type: "string",
      default: __("I am a button →"),
    },
    link: {
      type: "string",
      default: __(""),
    },
    color: {
      type: "string",
      default: "#EB7035",
    },
    fontColor: {
      type: "string",
      default: "#ffffff",
    },
    fontSize: {
      type: "number",
    },
    font: {
      type: "string",
      default: "Arial",
    },
    alignment: {
      type: "string",
      default: "center",
    },
  },
  edit: (props) => {
    const {
      attributes: { text, link, color, fontColor, fontSize, font, alignment },
      setAttributes,
    } = props;

    const updateText = (value) => {
      setAttributes({
        text: value,
      });
    };

    const updateLink = (value) => {
      setAttributes({
        link: value,
      });
    };

    const updateColor = (value) => {
      setAttributes({
        color: value,
      });
    };

    const updateFontColor = (value) => {
      setAttributes({
        fontColor: value,
      });
    };

    const updateAlignment = (value) => {
      setAttributes({
        alignment: value,
      });
    };

    let style = {
      backgroundColor: color,
      border: "none",
      color: fontColor,
      padding: "15px 32px",
      textAlign: "center",
      textDecoration: "none",
      display: "inline-block",
      fontSize: 16,
      borderRadius: 10,
    };

    return (
      <div>
        <InspectorControls>
          <PanelBody title={__("Button text")}>
            <TextControl
              onChange={updateText}
              value={text}
              placeholder="Alt Tag"
              label="Alt Tag"
              className=""
            />
          </PanelBody>
          <PanelBody title={__("Button Link")}>
            <TextControl
              onChange={updateLink}
              value={link}
              placeholder="http://www.google.com"
              label="Button Link"
              className=""
            />
          </PanelBody>
          <PanelColorSettings
            label={__("Background Color")}
            title={__("Background Color")}
            colorSettings={[
              {
                value: color,
                onChange: updateColor,
                label: __("Background Color"),
              },
            ]}
          />

          <PanelColorSettings
            label={__("Font Color")}
            title={__("Font Color")}
            colorSettings={[
              {
                value: fontColor,
                onChange: updateFontColor,
                label: __("Font Color"),
              },
            ]}
          />
          <PanelBody title={__("Alignment")}>
            <SelectControl
              label="Alignment"
              value={alignment}
              options={[
                { label: __("Center"), value: "center" },
                { label: __("Left"), value: "left" },
                { label: __("Right"), value: "right" },
              ]}
              onChange={updateAlignment}
            />
          </PanelBody>
        </InspectorControls>
        <div align={alignment}>
          <a href={link}>
            <span style={style}>
              <b>{text}</b>
            </span>
          </a>
        </div>
      </div>
    );
  },
  save: (props) => {
    const {
      attributes: { text, link, color, fontColor, fontSize, font, alignment },
    } = props;

    let style = {
      backgroundColor: color,
      border: "none",
      color: fontColor,
      padding: "15px 32px",
      textAlign: "center",
      textDecoration: "none",
      display: "inline-block",
      fontSize: 16,
      borderRadius: 10,
    };

    return (
      <div align={alignment}>
        <a href={link}>
          <span style={style}>
            <b>{text}</b>
          </span>
        </a>
      </div>
    );
  },
});
