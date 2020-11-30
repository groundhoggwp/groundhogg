"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * @constant string IS_ROOT_TAG Regex to check if the selector is a root tag selector.
 */
var IS_ROOT_TAG = /^(body|html|:root).*$/;

var wrap = function wrap(namespace) {
  var ignore = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
  return function (node) {
    var updateSelector = function updateSelector(selector) {
      if (ignore.includes(selector.trim())) {
        return selector;
      } // Anything other than a root tag is always prefixed.


      {
        if (!selector.match(IS_ROOT_TAG)) {
          return namespace + ' ' + selector;
        }
      } // HTML and Body elements cannot be contained within our container so lets extract their styles.

      return selector.replace(/^(body|html|:root)/, namespace);
    };

    if (node.type === 'rule') {
      return _objectSpread(_objectSpread({}, node), {}, {
        selectors: node.selectors.map(updateSelector)
      });
    }

    return node;
  };
};

var _default = wrap;
exports.default = _default;
//# sourceMappingURL=wrap.js.map