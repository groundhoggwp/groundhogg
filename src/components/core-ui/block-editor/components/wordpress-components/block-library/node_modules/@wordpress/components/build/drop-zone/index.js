"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.useDropZone = useDropZone;
exports.default = void 0;

var _element = require("@wordpress/element");

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _classnames2 = _interopRequireDefault(require("classnames"));

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

var _provider = require("./provider");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function useDropZone(_ref) {
  var element = _ref.element,
      onFilesDrop = _ref.onFilesDrop,
      onHTMLDrop = _ref.onHTMLDrop,
      onDrop = _ref.onDrop,
      isDisabled = _ref.isDisabled,
      withPosition = _ref.withPosition,
      _ref$__unstableIsRela = _ref.__unstableIsRelative,
      __unstableIsRelative = _ref$__unstableIsRela === void 0 ? false : _ref$__unstableIsRela;

  var _useContext = (0, _element.useContext)(_provider.Context),
      addDropZone = _useContext.addDropZone,
      removeDropZone = _useContext.removeDropZone;

  var _useState = (0, _element.useState)({
    isDraggingOverDocument: false,
    isDraggingOverElement: false,
    type: null
  }),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      state = _useState2[0],
      setState = _useState2[1];

  (0, _element.useEffect)(function () {
    if (!isDisabled) {
      var dropZone = {
        element: element,
        onDrop: onDrop,
        onFilesDrop: onFilesDrop,
        onHTMLDrop: onHTMLDrop,
        setState: setState,
        withPosition: withPosition,
        isRelative: __unstableIsRelative
      };
      addDropZone(dropZone);
      return function () {
        removeDropZone(dropZone);
      };
    }
  }, [isDisabled, onDrop, onFilesDrop, onHTMLDrop, withPosition]);
  return state;
}

var DropZone = function DropZone(props) {
  return (0, _element.createElement)(_provider.DropZoneConsumer, null, function (_ref2) {
    var addDropZone = _ref2.addDropZone,
        removeDropZone = _ref2.removeDropZone;
    return (0, _element.createElement)(DropZoneComponent, (0, _extends2.default)({
      addDropZone: addDropZone,
      removeDropZone: removeDropZone
    }, props));
  });
};

function DropZoneComponent(_ref3) {
  var className = _ref3.className,
      label = _ref3.label,
      onFilesDrop = _ref3.onFilesDrop,
      onHTMLDrop = _ref3.onHTMLDrop,
      onDrop = _ref3.onDrop;
  var element = (0, _element.useRef)();

  var _useDropZone = useDropZone({
    element: element,
    onFilesDrop: onFilesDrop,
    onHTMLDrop: onHTMLDrop,
    onDrop: onDrop,
    __unstableIsRelative: true
  }),
      isDraggingOverDocument = _useDropZone.isDraggingOverDocument,
      isDraggingOverElement = _useDropZone.isDraggingOverElement,
      type = _useDropZone.type;

  var children;

  if (isDraggingOverElement) {
    children = (0, _element.createElement)("div", {
      className: "components-drop-zone__content"
    }, (0, _element.createElement)(_icons.Icon, {
      icon: _icons.upload,
      className: "components-drop-zone__content-icon"
    }), (0, _element.createElement)("span", {
      className: "components-drop-zone__content-text"
    }, label ? label : (0, _i18n.__)('Drop files to upload')));
  }

  var classes = (0, _classnames2.default)('components-drop-zone', className, (0, _defineProperty2.default)({
    'is-active': (isDraggingOverDocument || isDraggingOverElement) && (type === 'file' && onFilesDrop || type === 'html' && onHTMLDrop || type === 'default' && onDrop),
    'is-dragging-over-document': isDraggingOverDocument,
    'is-dragging-over-element': isDraggingOverElement
  }, "is-dragging-".concat(type), !!type));
  return (0, _element.createElement)("div", {
    ref: element,
    className: classes
  }, children);
}

var _default = DropZone;
exports.default = _default;
//# sourceMappingURL=index.js.map