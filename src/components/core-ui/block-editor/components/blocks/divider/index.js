import { __ } from "@wordpress/i18n";
import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, PanelColorSettings } from "@wordpress/block-editor";
import { PanelBody, RangeControl } from "@wordpress/components";


import BlocksDivider from "components/svg/BlockEditor/BlocksDivider/";


registerBlockType("groundhogg/divider", {
  title: __("Groundhogg - Divider"), // Block title.
  icon: BlocksDivider, // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
  // icon: "editor-paragraph", // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
  category: "common", // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
  description: "Add Space in your email",
  keywords: [__("Groundhogg - Divider")],
  attributes: {
    height: {
      type: "number",
      default: 2,
    },
    width: {
      type: "number",
      default: 80,
    },
    color: {
      type: "string",
    },
  },
  edit: (props) => {
    const {
      attributes: { height, width, color },
      setAttributes,
    } = props;

    const MIN_DIVIDER_HEIGHT = 1;
    const MAX_DIVIDER_HEIGHT = 20;

    const MIN_DIVIDER_WIDTH = 1;
    const MAX_DIVIDER_WIDTH = 100;

    const updateHeight = (value) => {
      setAttributes({
        height: value,
      });
    };

    const updateWidth = (value) => {
      setAttributes({
        width: value,
      });
    };

    const updateColor = (value) => {
      setAttributes({
        color: value,
      });
    };

    let style = {
      height: height + "px",
      width: width + "%",
      backgroundColor: color,
    };

    return (
      <div className={props.className}>
        <InspectorControls>
          <PanelBody title={__("Divider settings")}>
            <RangeControl
              label={__("Height in pixels")}
              min={MIN_DIVIDER_HEIGHT}
              max={Math.max(MAX_DIVIDER_HEIGHT, height)}
              value={height}
              onChange={updateHeight}
            />
            <RangeControl
              label={__("width in percentage")}
              min={MIN_DIVIDER_WIDTH}
              max={Math.max(MAX_DIVIDER_WIDTH, height)}
              value={width}
              onChange={updateWidth}
            />
          </PanelBody>

          <PanelColorSettings
            title={__("Color settings")}
            colorSettings={[
              {
                value: color,
                onChange: updateColor,
                label: __("Color"),
              },
            ]}
          />
        </InspectorControls>
        <table width="100%" cellPadding="0" cellSpacing="0">
          <tbody>
            <tr>
              <td className="divider">
                <div style={{ margin: "5px 0px 5px 0px" }}>
                  <hr style={style} />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    );
  },
  save: (props) => {
    const {
      attributes: { height, width, color },
    } = props;

    const style = {
      height: height + "px",
      width: width + "%",
      backgroundColor: color,
    };

    return (
      <table width="100%" cellPadding="0" cellSpacing="0">
        <tbody>
          <tr>
            <td className="divider">
              <div style={{ margin: "5px 0px 5px 0px" }}>
                <hr style={style} />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    );
  },
});
