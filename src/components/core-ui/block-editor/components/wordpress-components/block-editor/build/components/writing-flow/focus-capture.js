"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _lodash = require("lodash");

var _dom = require("@wordpress/dom");

var _data = require("@wordpress/data");

var _dom2 = require("../../utils/dom");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Renders focus capturing areas to redirect focus to the selected block if not
 * in Navigation mode.
 *
 * @param {string}  selectedClientId Client ID of the selected block.
 * @param {boolean} isReverse        Set to true if the component is rendered
 *                                   after the block list, false if rendered
 *                                   before.
 * @param {Object}  containerRef     Reference containing the element reference
 *                                   of the block list container.
 * @param {boolean} noCapture        Reference containing the flag for enabling
 *                                   or disabling capturing.
 *
 * @return {WPElement} The focus capture element.
 */
var FocusCapture = (0, _element.forwardRef)(function (_ref, ref) {
  var selectedClientId = _ref.selectedClientId,
      isReverse = _ref.isReverse,
      containerRef = _ref.containerRef,
      noCapture = _ref.noCapture,
      hasMultiSelection = _ref.hasMultiSelection,
      multiSelectionContainer = _ref.multiSelectionContainer;
  var isNavigationMode = (0, _data.useSelect)(function (select) {
    return select('core/block-editor').isNavigationMode();
  });

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      setNavigationMode = _useDispatch.setNavigationMode;

  function onFocus() {
    // Do not capture incoming focus if set by us in WritingFlow.
    if (noCapture.current) {
      noCapture.current = null;
      return;
    } // When focus coming in from out of the block list, and no block is
    // selected, enable Navigation mode and select the first or last block
    // depending on the direction.


    if (!selectedClientId) {
      if (hasMultiSelection) {
        multiSelectionContainer.current.focus();
        return;
      }

      setNavigationMode(true);

      var tabbables = _dom.focus.tabbable.find(containerRef.current);

      if (tabbables.length) {
        if (isReverse) {
          (0, _lodash.last)(tabbables).focus();
        } else {
          (0, _lodash.first)(tabbables).focus();
        }
      }

      return;
    } // If there is a selected block, move focus to the first or last
    // tabbable element depending on the direction.


    var wrapper = (0, _dom2.getBlockDOMNode)(selectedClientId);

    if (isReverse) {
      var _tabbables = _dom.focus.tabbable.find(wrapper);

      var lastTabbable = (0, _lodash.last)(_tabbables) || wrapper;
      lastTabbable.focus();
    } else {
      wrapper.focus();
    }
  }

  return (0, _element.createElement)("div", {
    ref: ref // Don't allow tabbing to this element in Navigation mode.
    ,
    tabIndex: !isNavigationMode ? '0' : undefined,
    onFocus: onFocus // Needs to be positioned within the viewport, so focus to this
    // element does not scroll the page.
    ,
    style: {
      position: 'fixed'
    }
  });
});
var _default = FocusCapture;
exports.default = _default;
//# sourceMappingURL=focus-capture.js.map