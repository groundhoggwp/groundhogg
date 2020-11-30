"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = GuidePage;

var _element = require("@wordpress/element");

var _deprecated = _interopRequireDefault(require("@wordpress/deprecated"));

/**
 * WordPress dependencies
 */
function GuidePage(props) {
  (0, _element.useEffect)(function () {
    (0, _deprecated.default)('<GuidePage>', {
      alternative: 'the `pages` prop in <Guide>'
    });
  }, []);
  return (0, _element.createElement)("div", props);
}
//# sourceMappingURL=page.js.map