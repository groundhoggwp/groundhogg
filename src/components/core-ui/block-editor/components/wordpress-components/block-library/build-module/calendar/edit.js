import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import { createElement } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import moment from 'moment';
import memoize from 'memize';
/**
 * WordPress dependencies
 */

import { Disabled } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';
var getYearMonth = memoize(function (date) {
  if (!date) {
    return {};
  }

  var momentDate = moment(date);
  return {
    year: momentDate.year(),
    month: momentDate.month() + 1
  };
});
export default function CalendarEdit(_ref) {
  var attributes = _ref.attributes;
  var date = useSelect(function (select) {
    var _select = select('core/editor'),
        getEditedPostAttribute = _select.getEditedPostAttribute;

    var postType = getEditedPostAttribute('type'); // Dates are used to overwrite year and month used on the calendar.
    // This overwrite should only happen for 'post' post types.
    // For other post types the calendar always displays the current month.

    return postType === 'post' ? getEditedPostAttribute('date') : undefined;
  }, []);
  return createElement(Disabled, null, createElement(ServerSideRender, {
    block: "core/calendar",
    attributes: _objectSpread(_objectSpread({}, attributes), getYearMonth(date))
  }));
}
//# sourceMappingURL=edit.js.map