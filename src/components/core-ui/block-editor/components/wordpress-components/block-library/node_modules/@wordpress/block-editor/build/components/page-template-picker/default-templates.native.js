"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _blocks = require("@wordpress/blocks");

var _lodash = require("lodash");

var _memize = _interopRequireDefault(require("memize"));

var _templates = require("./templates");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var defaultTemplates = [_templates.About, _templates.Blog, _templates.Contact, _templates.Portfolio, _templates.Services, _templates.Team];

var createInnerBlocks = function createInnerBlocks(_ref) {
  var name = _ref.name,
      attributes = _ref.attributes,
      innerBlocks = _ref.innerBlocks;
  return (0, _blocks.createBlock)(name, attributes, (0, _lodash.map)(innerBlocks, createInnerBlocks));
};

var createBlocks = function createBlocks(template) {
  return template.map(function (_ref2) {
    var name = _ref2.name,
        attributes = _ref2.attributes,
        innerBlocks = _ref2.innerBlocks;
    return (0, _blocks.createBlock)(name, attributes, (0, _lodash.map)(innerBlocks, createInnerBlocks));
  });
};

var parsedTemplates = (0, _memize.default)(function () {
  return defaultTemplates.map(function (template) {
    return _objectSpread(_objectSpread({}, template), {}, {
      blocks: createBlocks(template.content)
    });
  });
});
var _default = parsedTemplates;
exports.default = _default;
//# sourceMappingURL=default-templates.native.js.map