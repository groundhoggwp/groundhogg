"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = Token;

var _element = require("@wordpress/element");

var _classnames = _interopRequireDefault(require("classnames"));

var _lodash = require("lodash");

var _compose = require("@wordpress/compose");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _button = _interopRequireDefault(require("../button"));

var _visuallyHidden = _interopRequireDefault(require("../visually-hidden"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function Token(_ref) {
  var value = _ref.value,
      status = _ref.status,
      title = _ref.title,
      displayTransform = _ref.displayTransform,
      _ref$isBorderless = _ref.isBorderless,
      isBorderless = _ref$isBorderless === void 0 ? false : _ref$isBorderless,
      _ref$disabled = _ref.disabled,
      disabled = _ref$disabled === void 0 ? false : _ref$disabled,
      _ref$onClickRemove = _ref.onClickRemove,
      onClickRemove = _ref$onClickRemove === void 0 ? _lodash.noop : _ref$onClickRemove,
      onMouseEnter = _ref.onMouseEnter,
      onMouseLeave = _ref.onMouseLeave,
      messages = _ref.messages,
      termPosition = _ref.termPosition,
      termsCount = _ref.termsCount;
  var instanceId = (0, _compose.useInstanceId)(Token);
  var tokenClasses = (0, _classnames.default)('components-form-token-field__token', {
    'is-error': 'error' === status,
    'is-success': 'success' === status,
    'is-validating': 'validating' === status,
    'is-borderless': isBorderless,
    'is-disabled': disabled
  });

  var onClick = function onClick() {
    return onClickRemove({
      value: value
    });
  };

  var transformedValue = displayTransform(value);
  var termPositionAndCount = (0, _i18n.sprintf)(
  /* translators: 1: term name, 2: term position in a set of terms, 3: total term set count. */
  (0, _i18n.__)('%1$s (%2$s of %3$s)'), transformedValue, termPosition, termsCount);
  return (0, _element.createElement)("span", {
    className: tokenClasses,
    onMouseEnter: onMouseEnter,
    onMouseLeave: onMouseLeave,
    title: title
  }, (0, _element.createElement)("span", {
    className: "components-form-token-field__token-text",
    id: "components-form-token-field__token-text-".concat(instanceId)
  }, (0, _element.createElement)(_visuallyHidden.default, {
    as: "span"
  }, termPositionAndCount), (0, _element.createElement)("span", {
    "aria-hidden": "true"
  }, transformedValue)), (0, _element.createElement)(_button.default, {
    className: "components-form-token-field__remove-token",
    icon: _icons.closeCircleFilled,
    onClick: !disabled && onClick,
    label: messages.remove,
    "aria-describedby": "components-form-token-field__token-text-".concat(instanceId)
  }));
}
//# sourceMappingURL=token.js.map