import { __ } from "@wordpress/i18n";
import { registerBlockType } from "@wordpress/blocks";
import { RichText, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, SelectControl, RangeControl } from "@wordpress/components";

//https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/rich-text/README.md

registerBlockType("groundhogg/heading", {
  title: __("Groundhogg - Heading"), // Block title.
  icon: "shield", // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
  category: "common", // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
  keywords: [__("Groundhogg-Header"), __("text")],
  attributes: {
    content: {
      source: "html",
      selector: "h2",
    },
    font: {
      type: "string",
      default: "Arial, sans-serif",
    },
    fontSize: {
      type: "number",
      default: 24,
    },
  },
  edit: (props) => {
    const {
      attributes: { content, font, fontSize },
      setAttributes,
    } = props;

    const MIN_FONT_SIZE = 1;
    const MAX_FONT_SIZE = 50;

    const updateContent = (value) => {
      setAttributes({
        content: value,
      });
    };
    const updateFont = (value) => {
      setAttributes({
        font: value,
      });
    };

    const updateFontSize = (value) => {
      setAttributes({
        fontSize: value,
      });
    };

    let style = {
      fontFamily: font,
      fontSize: fontSize,
    };
    return (
      <div>
        <InspectorControls>
          <PanelBody title={__("Font")}>
            <SelectControl
              label="Font"
              value={font}
              options={[
                { value: "Arial, sans-serif", label: __("Arial") },
                {
                  value: "Arial Black, Arial, sans-serif",
                  label: __("Arial Black"),
                },
                {
                  value: "Century Gothic, Times, serif",
                  label: __("Century Gothic"),
                },
                { value: "Courier, monospace", label: __("Courier") },
                { value: "Courier New, monospace", label: __("Courier New") },
                {
                  value: "Geneva, Tahoma, Verdana, sans-serif",
                  label: __("Geneva"),
                },
                {
                  value: "Georgia, Times, Times New Roman, serif",
                  label: __("Georgia"),
                },
                {
                  value: "Helvetica, Arial, sans-serif",
                  label: __("Helvetica"),
                },
                {
                  value: "Lucida, Geneva, Verdana, sans-serif",
                  label: __("Lucida"),
                },
                { value: "Tahoma, Verdana, sans-serif", label: __("Tahoma") },
                {
                  value: "Times, Times New Roman, Baskerville, Georgia, serif",
                  label: __("Times"),
                },
                {
                  value: "Times New Roman, Times, Georgia, serif",
                  label: __("Times New Roman"),
                },
                { value: "Verdana, Geneva, sans-serif", label: __("Verdana") },
              ]}
              onChange={updateFont}
            />
          </PanelBody>
          <PanelBody title={__("Font")}>
            <RangeControl
              label={__("Font Size")}
              min={MIN_FONT_SIZE}
              max={Math.max(MAX_FONT_SIZE, fontSize)}
              value={fontSize}
              onChange={updateFontSize}
            />
          </PanelBody>
        </InspectorControls>

        <RichText
          style={style}
          // identifier="content"
          tagName="h2" // The tag here is the element output and editable in the admin
          value={content} // Any existing content, either from the database or an attribute default
          formattingControls={["bold", "italic"]} // Allow the content to be made bold or italic, but do not allow other formatting options
          onChange={updateContent} // Store updated content as a block attribute
          placeholder={__("Paragraph...")} // Display this text before any content has been added by the user
        />
      </div>
    );
  },
  save: (props) => {
    const {
      attributes: { content, font, fontSize },
    } = props;

    let style = {
      fontFamily: font,
      fontSize: fontSize,
    };
    return (
      <h2 style={style}>
        <RichText.Content value={content} />
      </h2>
    );
  },
});
