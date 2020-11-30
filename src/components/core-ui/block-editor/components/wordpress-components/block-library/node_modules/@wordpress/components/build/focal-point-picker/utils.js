"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getExtension = getExtension;
exports.isVideoType = isVideoType;
exports.fractionToPercentage = fractionToPercentage;
exports.INITIAL_BOUNDS = void 0;
var INITIAL_BOUNDS = {
  top: 0,
  left: 0,
  bottom: 0,
  right: 0,
  width: 0,
  height: 0
};
exports.INITIAL_BOUNDS = INITIAL_BOUNDS;
var VIDEO_EXTENSIONS = ['avi', 'mpg', 'mpeg', 'mov', 'mp4', 'm4v', 'ogg', 'ogv', 'webm', 'wmv'];
/**
 * Gets the extension of a file name.
 *
 * @param {string} filename The file name.
 * @return {string} The extension of the file name.
 */

function getExtension() {
  var filename = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
  var parts = filename.split('.');
  return parts[parts.length - 1];
}
/**
 * Checks if a file is a video.
 *
 * @param {string} filename The file name.
 * @return {boolean} Whether the file is a video.
 */


function isVideoType() {
  var filename = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
  if (!filename) return false;
  return VIDEO_EXTENSIONS.includes(getExtension(filename));
}
/**
 * Transforms a fraction value to a percentage value.
 *
 * @param {number} fraction The fraction value.
 * @return {number} A percentage value.
 */


function fractionToPercentage(fraction) {
  return Math.round(fraction * 100);
}
//# sourceMappingURL=utils.js.map