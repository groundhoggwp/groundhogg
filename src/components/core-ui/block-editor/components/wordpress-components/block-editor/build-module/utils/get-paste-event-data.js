/**
 * External dependencies
 */
import { find, isNil } from 'lodash';
/**
 * WordPress dependencies
 */

import { createBlobURL } from '@wordpress/blob';
export function getPasteEventData(_ref) {
  var clipboardData = _ref.clipboardData;
  var items = clipboardData.items,
      files = clipboardData.files; // In Edge these properties can be null instead of undefined, so a more
  // rigorous test is required over using default values.

  items = isNil(items) ? [] : items;
  files = isNil(files) ? [] : files;
  var plainText = '';
  var html = ''; // IE11 only supports `Text` as an argument for `getData` and will
  // otherwise throw an invalid argument error, so we try the standard
  // arguments first, then fallback to `Text` if they fail.

  try {
    plainText = clipboardData.getData('text/plain');
    html = clipboardData.getData('text/html');
  } catch (error1) {
    try {
      html = clipboardData.getData('Text');
    } catch (error2) {
      // Some browsers like UC Browser paste plain text by default and
      // don't support clipboardData at all, so allow default
      // behaviour.
      return;
    }
  }

  files = Array.from(files);
  Array.from(items).forEach(function (item) {
    if (!item.getAsFile) {
      return;
    }

    var file = item.getAsFile();

    if (!file) {
      return;
    }

    var name = file.name,
        type = file.type,
        size = file.size;

    if (!find(files, {
      name: name,
      type: type,
      size: size
    })) {
      files.push(file);
    }
  });
  files = files.filter(function (_ref2) {
    var type = _ref2.type;
    return /^image\/(?:jpe?g|png|gif)$/.test(type);
  }); // Only process files if no HTML is present.
  // A pasted file may have the URL as plain text.

  if (files.length && !html) {
    html = files.map(function (file) {
      return "<img src=\"".concat(createBlobURL(file), "\">");
    }).join('');
    plainText = '';
  }

  return {
    html: html,
    plainText: plainText
  };
}
//# sourceMappingURL=get-paste-event-data.js.map