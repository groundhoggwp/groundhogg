import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";

/**
 * WordPress dependencies
 */
import { createBlobURL } from '@wordpress/blob';
/**
 * Browser dependencies
 */

var _window = window,
    atob = _window.atob,
    File = _window.File;
export default function imageCorrector(node) {
  if (node.nodeName !== 'IMG') {
    return;
  }

  if (node.src.indexOf('file:') === 0) {
    node.src = '';
  } // This piece cannot be tested outside a browser env.


  if (node.src.indexOf('data:') === 0) {
    var _node$src$split = node.src.split(','),
        _node$src$split2 = _slicedToArray(_node$src$split, 2),
        properties = _node$src$split2[0],
        data = _node$src$split2[1];

    var _properties$slice$spl = properties.slice(5).split(';'),
        _properties$slice$spl2 = _slicedToArray(_properties$slice$spl, 1),
        type = _properties$slice$spl2[0];

    if (!data || !type) {
      node.src = '';
      return;
    }

    var decoded; // Can throw DOMException!

    try {
      decoded = atob(data);
    } catch (e) {
      node.src = '';
      return;
    }

    var uint8Array = new Uint8Array(decoded.length);

    for (var i = 0; i < uint8Array.length; i++) {
      uint8Array[i] = decoded.charCodeAt(i);
    }

    var name = type.replace('/', '.');
    var file = new File([uint8Array], name, {
      type: type
    });
    node.src = createBlobURL(file);
  } // Remove trackers and hardly visible images.


  if (node.height === 1 || node.width === 1) {
    node.parentNode.removeChild(node);
  }
}
//# sourceMappingURL=image-corrector.js.map