import { createElement } from "@wordpress/element";

/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import deprecated from '@wordpress/deprecated';
export default function GuidePage(props) {
  useEffect(function () {
    deprecated('<GuidePage>', {
      alternative: 'the `pages` prop in <Guide>'
    });
  }, []);
  return createElement("div", props);
}
//# sourceMappingURL=page.js.map