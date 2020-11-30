import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { FlatList } from 'react-native';
/**
 * Internal dependencies
 */

import KeyboardAvoidingView from '../keyboard-avoiding-view';
export var KeyboardAwareFlatList = function KeyboardAwareFlatList(props) {
  return createElement(KeyboardAvoidingView, {
    style: {
      flex: 1
    }
  }, createElement(FlatList, props));
};

KeyboardAwareFlatList.handleCaretVerticalPositionChange = function () {//no need to handle on Android, it is system managed
};

export default KeyboardAwareFlatList;
//# sourceMappingURL=index.android.js.map