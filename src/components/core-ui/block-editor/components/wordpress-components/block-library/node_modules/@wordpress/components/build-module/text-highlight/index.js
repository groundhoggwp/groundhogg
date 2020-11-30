import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { escapeRegExp } from 'lodash';
/**
 * WordPress dependencies
 */

import { createInterpolateElement } from '@wordpress/element';

var TextHighlight = function TextHighlight(_ref) {
  var _ref$text = _ref.text,
      text = _ref$text === void 0 ? '' : _ref$text,
      _ref$highlight = _ref.highlight,
      highlight = _ref$highlight === void 0 ? '' : _ref$highlight;
  var trimmedHighlightText = highlight.trim();

  if (!trimmedHighlightText) {
    return text;
  }

  var regex = new RegExp("(".concat(escapeRegExp(trimmedHighlightText), ")"), 'gi');
  return createInterpolateElement(text.replace(regex, '<mark>$&</mark>'), {
    mark: createElement("mark", null)
  });
};

export default TextHighlight;
//# sourceMappingURL=index.js.map