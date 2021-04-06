import { __ } from "@wordpress/i18n";
import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, ResizableBox, RangeControl } from "@wordpress/components";

import BlocksSpacer from "components/svg/BlockEditor/BlocksSpacer/";

registerBlockType("groundhogg/spacer", {
  title: __("Groundhogg - Spacer"), // Block title.
  icon: BlocksSpacer, // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
  // icon: "shield", // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
  category: "common", // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
  description: "Add Space in your email",
  keywords: [__("Groundhogg - Spacer"), __("spacer")],
  attributes: {
    height: {
      type: "number",
      default: 100,
    },
  },
  edit: (props) => {
    // Creates a <p class='wp-block-cgb-block-react'></p>.
    const {
      attributes: { height = 10 },
      setAttributes,
    } = props;

    const MIN_SPACER_HEIGHT = 1;
    const MAX_SPACER_HEIGHT = 500;
    const updateHeight = (value) => {
      setAttributes({
        height: value,
      });
    };

    return (
      <div className={props.className}>
        <InspectorControls>
          <PanelBody title={__("Spacer settings")}>
            <RangeControl
              label={__("Height in pixels")}
              min={MIN_SPACER_HEIGHT}
              max={Math.max(MAX_SPACER_HEIGHT, height)}
              value={height}
              onChange={updateHeight}
            />
          </PanelBody>
        </InspectorControls>
        <table width="100%">
          <tbody>
            <tr>
              <td className="spacer" height={height}>
                &nbsp;
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    );
  },
  save: (props) => {
    const {
      attributes: { height },
    } = props;

    return (
      <table width="100%">
        <tbody>
          <tr>
            <td className="spacer" height={height}>
              &nbsp;
            </td>
          </tr>
        </tbody>
      </table>
    );
  },
});
