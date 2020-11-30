"use strict";

var _interopRequireWildcard = require("@babel/runtime/helpers/interopRequireWildcard");

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.DimensionControl = DimensionControl;
exports.default = void 0;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _ = require("../");

var _i18n = require("@wordpress/i18n");

var _sizes = _interopRequireWildcard(require("./sizes"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Internal dependencies
 */
function DimensionControl(props) {
  var label = props.label,
      value = props.value,
      _props$sizes = props.sizes,
      sizes = _props$sizes === void 0 ? _sizes.default : _props$sizes,
      icon = props.icon,
      onChange = props.onChange,
      _props$className = props.className,
      className = _props$className === void 0 ? '' : _props$className;

  var onChangeSpacingSize = function onChangeSpacingSize(val) {
    var theSize = (0, _sizes.findSizeBySlug)(sizes, val);

    if (!theSize || value === theSize.slug) {
      onChange(undefined);
    } else if ((0, _lodash.isFunction)(onChange)) {
      onChange(theSize.slug);
    }
  };

  var formatSizesAsOptions = function formatSizesAsOptions(theSizes) {
    var options = theSizes.map(function (_ref) {
      var name = _ref.name,
          slug = _ref.slug;
      return {
        label: name,
        value: slug
      };
    });
    return [{
      label: (0, _i18n.__)('Default'),
      value: ''
    }].concat(options);
  };

  var selectLabel = (0, _element.createElement)(_element.Fragment, null, icon && (0, _element.createElement)(_.Icon, {
    icon: icon
  }), label);
  return (0, _element.createElement)(_.SelectControl, {
    className: (0, _classnames.default)(className, 'block-editor-dimension-control'),
    label: selectLabel,
    hideLabelFromVision: false,
    value: value,
    onChange: onChangeSpacingSize,
    options: formatSizesAsOptions(sizes)
  });
}

var _default = DimensionControl;
exports.default = _default;
//# sourceMappingURL=index.js.map