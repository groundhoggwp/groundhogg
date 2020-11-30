import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";

/**
 * External dependencies
 */
import { I18nManager } from 'react-native';
/**
 * WordPress dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { arrowUp, arrowDown, arrowLeft, arrowRight } from '@wordpress/icons';
var horizontalMover = {
  backwardButtonIcon: arrowLeft,
  forwardButtonIcon: arrowRight,
  backwardButtonHint: __('Double tap to move the block to the left'),
  forwardButtonHint: __('Double tap to move the block to the right'),
  firstBlockTitle: __('Move block left'),
  lastBlockTitle: __('Move block right'),

  /* translators: accessibility text. %1: current block position (number). %2: next block position (number) */
  backwardButtonTitle: __('Move block left from position %1$s to position %2$s'),

  /* translators: accessibility text. %1: current block position (number). %2: next block position (number) */
  forwardButtonTitle: __('Move block right from position %1$s to position %2$s')
};
var verticalMover = {
  backwardButtonIcon: arrowUp,
  forwardButtonIcon: arrowDown,
  backwardButtonHint: __('Double tap to move the block up'),
  forwardButtonHint: __('Double tap to move the block down'),
  firstBlockTitle: __('Move block up'),
  lastBlockTitle: __('Move block down'),

  /* translators: accessibility text. %1: current block position (number). %2: next block position (number) */
  backwardButtonTitle: __('Move block up from row %1$s to row %2$s'),

  /* translators: accessibility text. %1: current block position (number). %2: next block position (number) */
  forwardButtonTitle: __('Move block down from row %1$s to row %2$s')
};
var KEYS = ['description', 'icon', 'title', 'actionTitle'];
var SETUP_GETTER = {
  description: getMoverDescription,
  icon: getArrowIcon,
  title: getMoverButtonTitle,
  actionTitle: getMoverActionTitle
};
export function getMoversSetup(isStackedHorizontally, _ref) {
  var firstIndex = _ref.firstIndex,
      _ref$keys = _ref.keys,
      keys = _ref$keys === void 0 ? KEYS : _ref$keys;
  return keys.reduce(function (setup, key) {
    if (KEYS.includes(key)) {
      Object.assign(setup, _defineProperty({}, key, getSetup(key, isStackedHorizontally, {
        firstIndex: firstIndex
      })));
    }

    return setup;
  }, {});
}

function switchButtonPropIfRTL(isBackwardButton, forwardButtonProp, backwardButtonProp, isStackedHorizontally) {
  if (I18nManager.isRTL && isStackedHorizontally) {
    // for RTL and horizontal direction switch prop between forward and backward button
    if (isBackwardButton) {
      return forwardButtonProp; // set forwardButtonProp for backward button
    }

    return backwardButtonProp; // set backwardButtonProp for forward button
  }

  return isBackwardButton ? backwardButtonProp : forwardButtonProp;
}

function getSetup() {
  var _arguments = Array.prototype.slice.call(arguments),
      key = _arguments[0],
      args = _arguments.slice(1);

  return SETUP_GETTER[key].apply(null, _toConsumableArray(args));
}

function applyRTLSetup(isBackwardButton, args) {
  return switchButtonPropIfRTL.apply(null, [isBackwardButton].concat(_toConsumableArray(args)));
}

function getMoverDescription(isStackedHorizontally) {
  return isStackedHorizontally ? horizontalMover : verticalMover;
}

function getArrowIcon(isStackedHorizontally) {
  var _getMoverDescription = getMoverDescription(isStackedHorizontally),
      forwardButtonIcon = _getMoverDescription.forwardButtonIcon,
      backwardButtonIcon = _getMoverDescription.backwardButtonIcon;

  var args = [forwardButtonIcon, backwardButtonIcon, isStackedHorizontally];
  return {
    backward: applyRTLSetup(true, args),
    forward: applyRTLSetup(false, args)
  };
}

function getMoverActionTitle(isStackedHorizontally) {
  var _getMoverDescription2 = getMoverDescription(isStackedHorizontally),
      firstBlockTitle = _getMoverDescription2.firstBlockTitle,
      lastBlockTitle = _getMoverDescription2.lastBlockTitle;

  var args = [lastBlockTitle, firstBlockTitle, isStackedHorizontally];
  var actionTitlePrev = applyRTLSetup(true, args);
  var actionTitleNext = applyRTLSetup(false, args);
  return {
    backward: sprintf(actionTitlePrev, firstBlockTitle),
    forward: sprintf(actionTitleNext, lastBlockTitle)
  };
}

function getMoverButtonTitle(isStackedHorizontally, _ref2) {
  var firstIndex = _ref2.firstIndex;

  var getIndexes = function getIndexes(isBackwardButton) {
    var fromIndex = firstIndex + 1; // current position based on index
    // for backwardButton decrease index (move left/up) for forwardButton increase index (move right/down)

    var direction = isBackwardButton ? -1 : 1;
    var toIndex = fromIndex + direction; // position after move

    return [fromIndex, toIndex];
  };

  var _getMoverDescription3 = getMoverDescription(isStackedHorizontally),
      backwardButtonTitle = _getMoverDescription3.backwardButtonTitle,
      forwardButtonTitle = _getMoverDescription3.forwardButtonTitle;

  var args = [forwardButtonTitle, backwardButtonTitle, isStackedHorizontally];
  var buttonTitlePrev = applyRTLSetup(true, args);
  var buttonTitleNext = applyRTLSetup(false, args);
  return {
    backward: sprintf.apply(void 0, [buttonTitlePrev].concat(_toConsumableArray(getIndexes(true)))),
    forward: sprintf.apply(void 0, [buttonTitleNext].concat(_toConsumableArray(getIndexes(false))))
  };
}
//# sourceMappingURL=mover-description.native.js.map