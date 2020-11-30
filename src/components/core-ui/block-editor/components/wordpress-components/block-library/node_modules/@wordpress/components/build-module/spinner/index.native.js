import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View } from 'react-native';
/**
 * Internal dependencies
 */

import style from './style.scss';
export default function Spinner(props) {
  var progress = props.progress;
  var width = progress + '%';
  return createElement(View, {
    style: [style.spinner, {
      width: width
    }]
  });
}
//# sourceMappingURL=index.native.js.map