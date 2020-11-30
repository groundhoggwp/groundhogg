"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = save;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _classnames = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

var _colorProps = _interopRequireDefault(require("./color-props"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function save(_ref) {
  var attributes = _ref.attributes;
  var borderRadius = attributes.borderRadius,
      linkTarget = attributes.linkTarget,
      rel = attributes.rel,
      text = attributes.text,
      title = attributes.title,
      url = attributes.url;
  var colorProps = (0, _colorProps.default)(attributes);
  var buttonClasses = (0, _classnames.default)('wp-block-button__link', colorProps.className, {
    'no-border-radius': borderRadius === 0
  });

  var buttonStyle = _objectSpread({
    borderRadius: borderRadius ? borderRadius + 'px' : undefined
  }, colorProps.style); // The use of a `title` attribute here is soft-deprecated, but still applied
  // if it had already been assigned, for the sake of backward-compatibility.
  // A title will no longer be assigned for new or updated button block links.


  return (0, _element.createElement)("div", null, (0, _element.createElement)(_blockEditor.RichText.Content, {
    tagName: "a",
    className: buttonClasses,
    href: url,
    title: title,
    style: buttonStyle,
    value: text,
    target: linkTarget,
    rel: rel
  }));
}
//# sourceMappingURL=save.js.map