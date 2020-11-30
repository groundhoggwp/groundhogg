"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = addContainer;

/**
 * Build the live regions markup.
 *
 * @param {string} [ariaLive] Value for the 'aria-live' attribute; default: 'polite'.
 *
 * @return {HTMLDivElement} The ARIA live region HTML element.
 */
function addContainer() {
  var ariaLive = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'polite';
  var container = document.createElement('div');
  container.id = "a11y-speak-".concat(ariaLive);
  container.className = 'a11y-speak-region';
  container.setAttribute('style', 'position: absolute;' + 'margin: -1px;' + 'padding: 0;' + 'height: 1px;' + 'width: 1px;' + 'overflow: hidden;' + 'clip: rect(1px, 1px, 1px, 1px);' + '-webkit-clip-path: inset(50%);' + 'clip-path: inset(50%);' + 'border: 0;' + 'word-wrap: normal !important;');
  container.setAttribute('aria-live', ariaLive);
  container.setAttribute('aria-relevant', 'additions text');
  container.setAttribute('aria-atomic', 'true');
  var _document = document,
      body = _document.body;

  if (body) {
    body.appendChild(container);
  }

  return container;
}
//# sourceMappingURL=add-container.js.map