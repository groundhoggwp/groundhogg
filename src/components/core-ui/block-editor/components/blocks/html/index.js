import { __ } from "@wordpress/i18n";
import { registerBlockType } from "@wordpress/blocks";
import { useState, Fragment, RawHTML } from "@wordpress/element";
import {
  BlockControls,
  PlainText,
  transformStyles,
  useBlockProps,
} from "@wordpress/block-editor";
import {
  ToolbarButton,
  Disabled,
  SandBox,
  ToolbarGroup,
} from "@wordpress/components";
import { useSelect } from "@wordpress/data";

// For more functionality refer https://github.com/WordPress/gutenberg/tree/master/packages/block-library/src/html
import BlocksHTML from "components/svg/BlockEditor/BlocksHTML/";

registerBlockType("groundhogg/html", {
  title: __("Groundhogg - HTML"), // Block title.
  icon: BlocksHTML, // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
  // icon: "shield", // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
  category: "common", // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
  description: "Add custom HTML in your email",
  keywords: [__("Groundhogg - HTML")],
  attributes: {
    content: {
      type: "string",
      source: "html",
    },
  },
  supports: {
    customClassName: false,
    className: false,
    html: false,
  },
  edit: ({ attributes, setAttributes, isSelected }) => {
    const [isPreview, setIsPreview] = useState();

    const styles = useSelect((select) => {
      // Default styles used to unset some of the styles
      // that might be inherited from the editor style.
      const defaultStyles = `
			html,body,:root {
				margin: 0 !important;
				padding: 0 !important;
				overflow: visible !important;
				min-height: auto !important;
			}
		`;

      return [
        defaultStyles,
        ...transformStyles(select("core/block-editor").getSettings().styles),
      ];
    }, []);

    function switchToPreview() {
      setIsPreview(true);
    }

    function switchToHTML() {
      setIsPreview(false);
    }

    return (
      <div {...useBlockProps}>
        <BlockControls>
          <ToolbarGroup>
            <ToolbarButton
              className="components-tab-button"
              isPressed={!isPreview}
              onClick={switchToHTML}
            >
              <span>HTML</span>
            </ToolbarButton>
            <ToolbarButton
              className="components-tab-button"
              isPressed={isPreview}
              onClick={switchToPreview}
            >
              <span>{__("Preview")}</span>
            </ToolbarButton>
          </ToolbarGroup>
        </BlockControls>
        <Disabled.Consumer>
          {(isDisabled) =>
            isPreview || isDisabled ? (
              <Fragment>
                <SandBox html={attributes.content} styles={styles} />
                {/*
									An overlay is added when the block is not selected in order to register click events.
									Some browsers do not bubble up the clicks from the sandboxed iframe, which makes it
									difficult to reselect the block.
								*/}
                {!isSelected && (
                  <div className="block-library-html__preview-overlay"></div>
                )}
              </Fragment>
            ) : (
              <PlainText
                value={attributes.content}
                onChange={(content) => setAttributes({ content })}
                placeholder={__(
                  "This is some custom HTML which you can edit on the right. You may enter any valid HTML tags, but they may get filtered out as some email browsers to not support certain HTML."
                )}
                aria-label={__("HTML")}
              />
            )
          }
        </Disabled.Consumer>
      </div>
    );
  },
  save: ({ attributes }) => {
    // console.log(attributes.content);
    return <RawHTML>{attributes.content}</RawHTML>;
  },
});
