"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.MediaUploadCheck = MediaUploadCheck;
exports.default = void 0;

var _data = require("@wordpress/data");

/**
 * WordPress dependencies
 */
function MediaUploadCheck(_ref) {
  var _ref$fallback = _ref.fallback,
      fallback = _ref$fallback === void 0 ? null : _ref$fallback,
      children = _ref.children;
  var hasUploadPermissions = (0, _data.useSelect)(function (select) {
    var _select = select('core/block-editor'),
        getSettings = _select.getSettings;

    return !!getSettings().mediaUpload;
  }, []);
  return hasUploadPermissions ? children : fallback;
}
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/media-upload/README.md
 */


var _default = MediaUploadCheck;
exports.default = _default;
//# sourceMappingURL=check.js.map