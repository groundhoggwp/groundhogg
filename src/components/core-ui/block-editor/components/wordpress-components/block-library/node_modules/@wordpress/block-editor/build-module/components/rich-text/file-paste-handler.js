/**
 * WordPress dependencies
 */
import { createBlobURL } from '@wordpress/blob';
export function filePasteHandler(files) {
  return files.filter(function (_ref) {
    var type = _ref.type;
    return /^image\/(?:jpe?g|png|gif)$/.test(type);
  }).map(function (file) {
    return "<img src=\"".concat(createBlobURL(file), "\">");
  }).join('');
}
//# sourceMappingURL=file-paste-handler.js.map