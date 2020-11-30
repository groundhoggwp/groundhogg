"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = DefaultStylePicker;

var _element = require("@wordpress/element");

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _lodash = require("lodash");

var _i18n = require("@wordpress/i18n");

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
function DefaultStylePicker(_ref) {
  var blockName = _ref.blockName;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var settings = select('core/block-editor').getSettings();
    var preferredStyleVariations = settings.__experimentalPreferredStyleVariations;
    return {
      preferredStyle: (0, _lodash.get)(preferredStyleVariations, ['value', blockName]),
      onUpdatePreferredStyleVariations: (0, _lodash.get)(preferredStyleVariations, ['onChange'], null),
      styles: select('core/blocks').getBlockStyles(blockName)
    };
  }, [blockName]),
      preferredStyle = _useSelect.preferredStyle,
      onUpdatePreferredStyleVariations = _useSelect.onUpdatePreferredStyleVariations,
      styles = _useSelect.styles;

  var selectOptions = (0, _element.useMemo)(function () {
    return [{
      label: (0, _i18n.__)('Not set'),
      value: ''
    }].concat((0, _toConsumableArray2.default)(styles.map(function (_ref2) {
      var label = _ref2.label,
          name = _ref2.name;
      return {
        label: label,
        value: name
      };
    })));
  }, [styles]);
  var selectOnChange = (0, _element.useCallback)(function (blockStyle) {
    onUpdatePreferredStyleVariations(blockName, blockStyle);
  }, [blockName, onUpdatePreferredStyleVariations]);
  return onUpdatePreferredStyleVariations && (0, _element.createElement)(_components.SelectControl, {
    options: selectOptions,
    value: preferredStyle || '',
    label: (0, _i18n.__)('Default Style'),
    onChange: selectOnChange
  });
}
//# sourceMappingURL=index.js.map