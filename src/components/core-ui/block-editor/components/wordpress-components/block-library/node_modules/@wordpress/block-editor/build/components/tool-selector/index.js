"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _components = require("@wordpress/components");

var _i18n = require("@wordpress/i18n");

var _data = require("@wordpress/data");

var _icons = require("@wordpress/icons");

/**
 * WordPress dependencies
 */
var selectIcon = (0, _element.createElement)(_components.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  width: "24",
  height: "24",
  viewBox: "0 0 24 24"
}, (0, _element.createElement)(_components.Path, {
  d: "M9.4 20.5L5.2 3.8l14.6 9-2 .3c-.2 0-.4.1-.7.1-.9.2-1.6.3-2.2.5-.8.3-1.4.5-1.8.8-.4.3-.8.8-1.3 1.5-.4.5-.8 1.2-1.2 2l-.3.6-.9 1.9zM7.6 7.1l2.4 9.3c.2-.4.5-.8.7-1.1.6-.8 1.1-1.4 1.6-1.8.5-.4 1.3-.8 2.2-1.1l1.2-.3-8.1-5z"
}));

function ToolSelector(props, ref) {
  var isNavigationTool = (0, _data.useSelect)(function (select) {
    return select('core/block-editor').isNavigationMode();
  }, []);

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      setNavigationMode = _useDispatch.setNavigationMode;

  var onSwitchMode = function onSwitchMode(mode) {
    setNavigationMode(mode === 'edit' ? false : true);
  };

  return (0, _element.createElement)(_components.Dropdown, {
    renderToggle: function renderToggle(_ref) {
      var isOpen = _ref.isOpen,
          onToggle = _ref.onToggle;
      return (0, _element.createElement)(_components.Button, (0, _extends2.default)({}, props, {
        ref: ref,
        icon: isNavigationTool ? selectIcon : _icons.edit,
        "aria-expanded": isOpen,
        "aria-haspopup": "true",
        onClick: onToggle
        /* translators: button label text should, if possible, be under 16 characters. */
        ,
        label: (0, _i18n.__)('Modes')
      }));
    },
    position: "bottom right",
    renderContent: function renderContent() {
      return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_components.NavigableMenu, {
        role: "menu",
        "aria-label": (0, _i18n.__)('Modes')
      }, (0, _element.createElement)(_components.MenuItemsChoice, {
        value: isNavigationTool ? 'select' : 'edit',
        onSelect: onSwitchMode,
        choices: [{
          value: 'edit',
          label: (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_icons.Icon, {
            icon: _icons.edit
          }), (0, _i18n.__)('Edit'))
        }, {
          value: 'select',
          label: (0, _element.createElement)(_element.Fragment, null, selectIcon, (0, _i18n.__)('Select'))
        }]
      })), (0, _element.createElement)("div", {
        className: "block-editor-tool-selector__help"
      }, (0, _i18n.__)('Tools offer different interactions for block selection & editing. To select, press Escape, to go back to editing, press Enter.')));
    }
  });
}

var _default = (0, _element.forwardRef)(ToolSelector);

exports.default = _default;
//# sourceMappingURL=index.js.map