"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = BlockParentSelector;

var _element = require("@wordpress/element");

var _blocks = require("@wordpress/blocks");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _i18n = require("@wordpress/i18n");

var _blockIcon = _interopRequireDefault(require("../block-icon"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Block parent selector component, displaying the hierarchy of the
 * current block selection as a single icon to "go up" a level.
 *
 * @return {WPComponent} Parent block selector.
 */
function BlockParentSelector() {
  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      selectBlock = _useDispatch.selectBlock;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlockName = _select.getBlockName,
        getBlockParents = _select.getBlockParents,
        getSelectedBlockClientId = _select.getSelectedBlockClientId;

    var selectedBlockClientId = getSelectedBlockClientId();
    var parents = getBlockParents(selectedBlockClientId);
    var _firstParentClientId = parents[parents.length - 1];
    var parentBlockName = getBlockName(_firstParentClientId);
    return {
      parentBlockType: (0, _blocks.getBlockType)(parentBlockName),
      firstParentClientId: _firstParentClientId
    };
  }, []),
      parentBlockType = _useSelect.parentBlockType,
      firstParentClientId = _useSelect.firstParentClientId;

  if (firstParentClientId !== undefined) {
    return (0, _element.createElement)("div", {
      className: "block-editor-block-parent-selector",
      key: firstParentClientId
    }, (0, _element.createElement)(_components.ToolbarButton, {
      className: "block-editor-block-parent-selector__button",
      onClick: function onClick() {
        return selectBlock(firstParentClientId);
      },
      label: (0, _i18n.sprintf)(
      /* translators: %s: Name of the block's parent. */
      (0, _i18n.__)('Select parent (%s)'), parentBlockType.title),
      showTooltip: true,
      icon: (0, _element.createElement)(_blockIcon.default, {
        icon: parentBlockType.icon
      })
    }));
  }

  return null;
}
//# sourceMappingURL=index.js.map