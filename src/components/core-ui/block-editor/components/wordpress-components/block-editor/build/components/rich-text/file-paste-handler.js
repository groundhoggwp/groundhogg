"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.filePasteHandler = filePasteHandler;

var _blob = require("@wordpress/blob");

/**
 * WordPress dependencies
 */
function filePasteHandler(files) {
  return files.filter(function (_ref) {
    var type = _ref.type;
    return /^image\/(?:jpe?g|png|gif)$/.test(type);
  }).map(function (file) {
    return "<img src=\"".concat((0, _blob.createBlobURL)(file), "\">");
  }).join('');
}
//# sourceMappingURL=file-paste-handler.js.map