import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";

/**
 * External dependencies
 */
import { find } from 'lodash';
/**
 * WordPress dependencies
 */

import { useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
/**
 * Internal dependencies
 */

import { useBlockEditContext } from '../block-edit';
import useEditorFeature from '../use-editor-feature';
var EMPTY_ARRAY = [];
export function __experimentalGetGradientClass(gradientSlug) {
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

export function getGradientValueBySlug(gradients, slug) {
  var gradient = find(gradients, ['slug', slug]);
  return gradient && gradient.gradient;
}
export function __experimentalGetGradientObjectByGradientValue(gradients, value) {
  var gradient = find(gradients, ['gradient', value]);
  return gradient;
}
/**
 * Retrieves the gradient slug per slug.
 *
 * @param {Array} gradients Gradient Palette
 * @param {string} value Gradient value
 * @return {string} Gradient slug.
 */

export function getGradientSlugByValue(gradients, value) {
  var gradient = __experimentalGetGradientObjectByGradientValue(gradients, value);

  return gradient && gradient.slug;
}
export function __experimentalUseGradient() {
  var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
      _ref$gradientAttribut = _ref.gradientAttribute,
      gradientAttribute = _ref$gradientAttribut === void 0 ? 'gradient' : _ref$gradientAttribut,
      _ref$customGradientAt = _ref.customGradientAttribute,
      customGradientAttribute = _ref$customGradientAt === void 0 ? 'customGradient' : _ref$customGradientAt;

  var _useBlockEditContext = useBlockEditContext(),
      clientId = _useBlockEditContext.clientId;

  var gradients = useEditorFeature('color.gradients') || EMPTY_ARRAY;

  var _useSelect = useSelect(function (select) {
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

  var _useDispatch = useDispatch('core/block-editor'),
      updateBlockAttributes = _useDispatch.updateBlockAttributes;

  var setGradient = useCallback(function (newGradientValue) {
    var _updateBlockAttribute2;

    var slug = getGradientSlugByValue(gradients, newGradientValue);

    if (slug) {
      var _updateBlockAttribute;

      updateBlockAttributes(clientId, (_updateBlockAttribute = {}, _defineProperty(_updateBlockAttribute, gradientAttribute, slug), _defineProperty(_updateBlockAttribute, customGradientAttribute, undefined), _updateBlockAttribute));
      return;
    }

    updateBlockAttributes(clientId, (_updateBlockAttribute2 = {}, _defineProperty(_updateBlockAttribute2, gradientAttribute, undefined), _defineProperty(_updateBlockAttribute2, customGradientAttribute, newGradientValue), _updateBlockAttribute2));
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