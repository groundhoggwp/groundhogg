"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.Input = void 0;

var _styledBase = _interopRequireDefault(require("@emotion/styled-base"));

var _core = require("@emotion/core");

var _inputControl = _interopRequireDefault(require("../../input-control"));

function _EMOTION_STRINGIFIED_CSS_ERROR__() { return "You have tried to stringify object returned from `css` function. It isn't supposed to be used directly (e.g. as value of the `className` prop), but rather handed to emotion so it can handle it (e.g. as value of `css` prop)."; }

var _ref = process.env.NODE_ENV === "production" ? {
  name: "1b9wwt5",
  styles: "&::-webkit-outer-spin-button,&::-webkit-inner-spin-button{-webkit-appearance:none !important;margin:0 !important;}"
} : {
  name: "1b9wwt5",
  styles: "&::-webkit-outer-spin-button,&::-webkit-inner-spin-button{-webkit-appearance:none !important;margin:0 !important;}",
  map: "/*# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIkB3b3JkcHJlc3MvY29tcG9uZW50cy9zcmMvbnVtYmVyLWNvbnRyb2wvc3R5bGVzL251bWJlci1jb250cm9sLXN0eWxlcy5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFhVyIsImZpbGUiOiJAd29yZHByZXNzL2NvbXBvbmVudHMvc3JjL251bWJlci1jb250cm9sL3N0eWxlcy9udW1iZXItY29udHJvbC1zdHlsZXMuanMiLCJzb3VyY2VzQ29udGVudCI6WyIvKipcbiAqIEV4dGVybmFsIGRlcGVuZGVuY2llc1xuICovXG5pbXBvcnQgeyBjc3MgfSBmcm9tICdAZW1vdGlvbi9jb3JlJztcbmltcG9ydCBzdHlsZWQgZnJvbSAnQGVtb3Rpb24vc3R5bGVkJztcbi8qKlxuICogSW50ZXJuYWwgZGVwZW5kZW5jaWVzXG4gKi9cbmltcG9ydCBJbnB1dENvbnRyb2wgZnJvbSAnLi4vLi4vaW5wdXQtY29udHJvbCc7XG5cbmNvbnN0IGh0bWxBcnJvd1N0eWxlcyA9ICggeyBoaWRlSFRNTEFycm93cyB9ICkgPT4ge1xuXHRpZiAoICEgaGlkZUhUTUxBcnJvd3MgKSByZXR1cm4gYGA7XG5cblx0cmV0dXJuIGNzc2Bcblx0XHQmOjotd2Via2l0LW91dGVyLXNwaW4tYnV0dG9uLFxuXHRcdCY6Oi13ZWJraXQtaW5uZXItc3Bpbi1idXR0b24ge1xuXHRcdFx0LXdlYmtpdC1hcHBlYXJhbmNlOiBub25lICFpbXBvcnRhbnQ7XG5cdFx0XHRtYXJnaW46IDAgIWltcG9ydGFudDtcblx0XHR9XG5cdGA7XG59O1xuXG5leHBvcnQgY29uc3QgSW5wdXQgPSBzdHlsZWQoIElucHV0Q29udHJvbCApYFxuXHQkeyBodG1sQXJyb3dTdHlsZXMgfTtcbmA7XG4iXX0= */",
  toString: _EMOTION_STRINGIFIED_CSS_ERROR__
};

var htmlArrowStyles = function htmlArrowStyles(_ref2) {
  var hideHTMLArrows = _ref2.hideHTMLArrows;
  if (!hideHTMLArrows) return "";
  return _ref;
};

var Input = ( /*#__PURE__*/0, _styledBase.default)(_inputControl.default, {
  target: "ep48uk90",
  label: "Input"
})(htmlArrowStyles, ";" + (process.env.NODE_ENV === "production" ? "" : "/*# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIkB3b3JkcHJlc3MvY29tcG9uZW50cy9zcmMvbnVtYmVyLWNvbnRyb2wvc3R5bGVzL251bWJlci1jb250cm9sLXN0eWxlcy5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFzQjJDIiwiZmlsZSI6IkB3b3JkcHJlc3MvY29tcG9uZW50cy9zcmMvbnVtYmVyLWNvbnRyb2wvc3R5bGVzL251bWJlci1jb250cm9sLXN0eWxlcy5qcyIsInNvdXJjZXNDb250ZW50IjpbIi8qKlxuICogRXh0ZXJuYWwgZGVwZW5kZW5jaWVzXG4gKi9cbmltcG9ydCB7IGNzcyB9IGZyb20gJ0BlbW90aW9uL2NvcmUnO1xuaW1wb3J0IHN0eWxlZCBmcm9tICdAZW1vdGlvbi9zdHlsZWQnO1xuLyoqXG4gKiBJbnRlcm5hbCBkZXBlbmRlbmNpZXNcbiAqL1xuaW1wb3J0IElucHV0Q29udHJvbCBmcm9tICcuLi8uLi9pbnB1dC1jb250cm9sJztcblxuY29uc3QgaHRtbEFycm93U3R5bGVzID0gKCB7IGhpZGVIVE1MQXJyb3dzIH0gKSA9PiB7XG5cdGlmICggISBoaWRlSFRNTEFycm93cyApIHJldHVybiBgYDtcblxuXHRyZXR1cm4gY3NzYFxuXHRcdCY6Oi13ZWJraXQtb3V0ZXItc3Bpbi1idXR0b24sXG5cdFx0Jjo6LXdlYmtpdC1pbm5lci1zcGluLWJ1dHRvbiB7XG5cdFx0XHQtd2Via2l0LWFwcGVhcmFuY2U6IG5vbmUgIWltcG9ydGFudDtcblx0XHRcdG1hcmdpbjogMCAhaW1wb3J0YW50O1xuXHRcdH1cblx0YDtcbn07XG5cbmV4cG9ydCBjb25zdCBJbnB1dCA9IHN0eWxlZCggSW5wdXRDb250cm9sIClgXG5cdCR7IGh0bWxBcnJvd1N0eWxlcyB9O1xuYDtcbiJdfQ== */"));
exports.Input = Input;
//# sourceMappingURL=number-control-styles.js.map