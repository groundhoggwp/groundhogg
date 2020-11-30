import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { times } from 'lodash';
import classnames from 'classnames';
var lineClassName = 'block-editor-block-navigator-descender-line';
export default function DescenderLines(_ref) {
  var level = _ref.level,
      isLastRow = _ref.isLastRow,
      terminatedLevels = _ref.terminatedLevels;
  return times(level - 1, function (index) {
    // The first 'level' that has a descender line is level 2.
    // Add 2 to the zero-based index below to reflect that.
    var currentLevel = index + 2;
    var hasItem = currentLevel === level;
    return createElement("div", {
      key: index,
      "aria-hidden": "true",
      className: classnames(lineClassName, {
        'has-item': hasItem,
        'is-last-row': isLastRow,
        'is-terminated': terminatedLevels.includes(currentLevel)
      })
    });
  });
}
//# sourceMappingURL=descender-lines.js.map