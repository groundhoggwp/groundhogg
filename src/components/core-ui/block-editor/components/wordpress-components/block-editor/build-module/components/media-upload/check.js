/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
export function MediaUploadCheck(_ref) {
  var _ref$fallback = _ref.fallback,
      fallback = _ref$fallback === void 0 ? null : _ref$fallback,
      children = _ref.children;
  var hasUploadPermissions = useSelect(function (select) {
    var _select = select('core/block-editor'),
        getSettings = _select.getSettings;

    return !!getSettings().mediaUpload;
  }, []);
  return hasUploadPermissions ? children : fallback;
}
/**
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/media-upload/README.md
 */

export default MediaUploadCheck;
//# sourceMappingURL=check.js.map