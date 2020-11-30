"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = CalendarEdit;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _moment = _interopRequireDefault(require("moment"));

var _memize = _interopRequireDefault(require("memize"));

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _serverSideRender = _interopRequireDefault(require("@wordpress/server-side-render"));

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var getYearMonth = (0, _memize.default)(function (date) {
  if (!date) {
    return {};
  }

  var momentDate = (0, _moment.default)(date);
  return {
    year: momentDate.year(),
    month: momentDate.month() + 1
  };
});

function CalendarEdit(_ref) {
  var attributes = _ref.attributes;
  var date = (0, _data.useSelect)(function (select) {
    var _select = select('core/editor'),
        getEditedPostAttribute = _select.getEditedPostAttribute;

    var postType = getEditedPostAttribute('type'); // Dates are used to overwrite year and month used on the calendar.
    // This overwrite should only happen for 'post' post types.
    // For other post types the calendar always displays the current month.

    return postType === 'post' ? getEditedPostAttribute('date') : undefined;
  }, []);
  return (0, _element.createElement)(_components.Disabled, null, (0, _element.createElement)(_serverSideRender.default, {
    block: "core/calendar",
    attributes: _objectSpread(_objectSpread({}, attributes), getYearMonth(date))
  }));
}
//# sourceMappingURL=edit.js.map