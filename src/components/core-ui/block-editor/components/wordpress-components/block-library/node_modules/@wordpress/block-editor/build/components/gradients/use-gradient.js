"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.__experimentalGetGradientClass = __experimentalGetGradientClass;
exports.getGradientValueBySlug = getGradientValueBySlug;
exports.__experimentalGetGradientObjectByGradientValue = __experimentalGetGradientObjectByGradientValue;
exports.getGradientSlugByValue = getGradientSlugByValue;
exports.__experimentalUseGradient = __experimentalUseGradient;

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _lodash = require("lodash");

var _element = require("@wordpress/element");

var _data = require("@wordpress/data");

var _blockEdit = require("../block-edit");

var _useEditorFeature = _interopRequireDefault(require("../use-editor-feature"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
var EMPTY_ARRAY = [];

function __experimentalGetGradientClass(gradientSlug) {
  if (!gradientSlug) {
    return undefined;
  }

  return "has-".concat(gradientSlug, "-gradient-background");
}
/**
 * Retrieves the gradient value per slug.
 *
 * @param {Array} gradients Gradient Palette
 * @param {string} slug Gradient slug
 *
 * @return {string} Gradient value.
 */


function getGradientValueBySlug(gradients, slug) {
  var gradient = (0, _lodash.find)(gradients, ['slug', slug]);
  return gradient && gradient.gradient;
}

function __experimentalGetGradientObjectByGradientValue(gradients, value) {
  var gradient = (0, _lodash.find)(gradients, ['gradient', value]);
  return gradient;
}
/**
 * Retrieves the gradient slug per slug.
 *
 * @param {Array} gradients Gradient Palette
 * @param {string} value Gradient value
 * @return {string} Gradient slug.
 */


function getGradientSlugByValue(gradients, value) {
  var gradient = __experimentalGetGradientObjectByGradientValue(gradients, value);

  return gradient && gradient.slug;
}

function __experimentalUseGradient() {
  var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
      _ref$gradientAttribut = _ref.gradientAttribute,
      gradientAttribute = _ref$gradientAttribut === void 0 ? 'gradient' : _ref$gradientAttribut,
      _ref$customGradientAt = _ref.customGradientAttribute,
      customGradientAttribute = _ref$customGradientAt === void 0 ? 'customGradient' : _ref$customGradientAt;

  var _useBlockEditContext = (0, _blockEdit.useBlockEditContext)(),
      clientId = _useBlockEditContext.clientId;

  var gradients = (0, _useEditorFeature.default)('color.gradients') || EMPTY_ARRAY;

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getBlockAttributes = _select.getBlockAttributes;

    var attributes = getBlockAttributes(clientId);
    return {
      customGradient: attributes[customGradientAttribute],
      gradient: attributes[gradientAttribute]
    };
  }, [clientId, gradientAttribute, customGradientAttribute]),
      gradient = _useSelect.gradient,
      customGradient = _useSelect.customGradient;

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      updateBlockAttributes = _useDispatch.updateBlockAttributes;

  var setGradient = (0, _element.useCallback)(function (newGradientValue) {
    var _updateBlockAttribute2;

    var slug = getGradientSlugByValue(gradients, newGradientValue);

    if (slug) {
      var _updateBlockAttribute;

      updateBlockAttributes(clientId, (_updateBlockAttribute = {}, (0, _defineProperty2.default)(_updateBlockAttribute, gradientAttribute, slug), (0, _defineProperty2.default)(_updateBlockAttribute, customGradientAttribute, undefined), _updateBlockAttribute));
      return;
    }

    updateBlockAttributes(clientId, (_updateBlockAttribute2 = {}, (0, _defineProperty2.default)(_updateBlockAttribute2, gradientAttribute, undefined), (0, _defineProperty2.default)(_updateBlockAttribute2, customGradientAttribute, newGradientValue), _updateBlockAttribute2));
  }, [gradients, clientId, updateBlockAttributes]);

  var gradientClass = __experimentalGetGradientClass(gradient);

  var gradientValue;

  if (gradient) {
    gradientValue = getGradientValueBySlug(gradients, gradient);
  } else {
    gradientValue = customGradient;
  }

  return {
    gradientClass: gradientClass,
    gradientValue: gradientValue,
    setGradient: setGradient
  };
}
//# sourceMappingURL=use-gradient.js.map