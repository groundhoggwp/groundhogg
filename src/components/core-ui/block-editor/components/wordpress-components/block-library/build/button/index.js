"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.settings = exports.name = exports.metadata = void 0;

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _deprecated = _interopRequireDefault(require("./deprecated"));

var _edit = _interopRequireDefault(require("./edit"));

var _save = _interopRequireDefault(require("./save"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var metadata = {
  name: "core/button",
  category: "design",
  parent: ["core/buttons"],
  attributes: {
    url: {
      type: "string",
      source: "attribute",
      selector: "a",
      attribute: "href"
    },
    title: {
      type: "string",
      source: "attribute",
      selector: "a",
      attribute: "title"
    },
    text: {
      type: "string",
      source: "html",
      selector: "a"
    },
    linkTarget: {
      type: "string",
      source: "attribute",
      selector: "a",
      attribute: "target"
    },
    rel: {
      type: "string",
      source: "attribute",
      selector: "a",
      attribute: "rel"
    },
    placeholder: {
      type: "string"
    },
    borderRadius: {
      type: "number"
    },
    style: {
      type: "object"
    },
    backgroundColor: {
      type: "string"
    },
    textColor: {
      type: "string"
    },
    gradient: {
      type: "string"
    }
  },
  supports: {
    anchor: true,
    align: true,
    alignWide: false,
    reusable: false,
    lightBlockWrapper: true
  }
};
exports.metadata = metadata;
var name = metadata.name;
exports.name = name;
var settings = {
  title: (0, _i18n.__)('Button'),
  description: (0, _i18n.__)('Prompt visitors to take action with a button-style link.'),
  icon: _icons.button,
  keywords: [(0, _i18n.__)('link')],
  example: {
    attributes: {
      className: 'is-style-fill',
      backgroundColor: 'vivid-green-cyan',
      text: (0, _i18n.__)('Call to Action')
    }
  },
  styles: [{
    name: 'fill',
    label: (0, _i18n.__)('Fill'),
    isDefault: true
  }, {
    name: 'outline',
    label: (0, _i18n.__)('Outline')
  }],
  edit: _edit.default,
  save: _save.default,
  deprecated: _deprecated.default,
  merge: function merge(a, _ref) {
    var _ref$text = _ref.text,
        text = _ref$text === void 0 ? '' : _ref$text;
    return _objectSpread(_objectSpread({}, a), {}, {
      text: (a.text || '') + text
    });
  }
};
exports.settings = settings;
//# sourceMappingURL=index.js.map