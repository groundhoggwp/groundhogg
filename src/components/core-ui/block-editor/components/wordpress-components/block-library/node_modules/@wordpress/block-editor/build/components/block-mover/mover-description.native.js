"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getMoversSetup = getMoversSetup;

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _reactNative = require("react-native");

var _i18n = require("@wordpress/i18n");

var _icons = require("@wordpress/icons");

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */
var horizontalMover = {
  backwardButtonIcon: _icons.arrowLeft,
  forwardButtonIcon: _icons.arrowRight,
  backwardButtonHint: (0, _i18n.__)('Double tap to move the block to the left'),
  forwardButtonHint: (0, _i18n.__)('Double tap to move the block to the right'),
  firstBlockTitle: (0, _i18n.__)('Move block left'),
  lastBlockTitle: (0, _i18n.__)('Move block right'),

  /* translators: accessibility text. %1: current block position (number). %2: next block position (number) */
  backwardButtonTitle: (0, _i18n.__)('Move block left from position %1$s to position %2$s'),

  /* translators: accessibility text. %1: current block position (number). %2: next block position (number) */
  forwardButtonTitle: (0, _i18n.__)('Move block right from position %1$s to position %2$s')
};
var verticalMover = {
  backwardButtonIcon: _icons.arrowUp,
  forwardButtonIcon: _icons.arrowDown,
  backwardButtonHint: (0, _i18n.__)('Double tap to move the block up'),
  forwardButtonHint: (0, _i18n.__)('Double tap to move the block down'),
  firstBlockTitle: (0, _i18n.__)('Move block up'),
  lastBlockTitle: (0, _i18n.__)('Move block down'),

  /* translators: accessibility text. %1: current block position (number). %2: next block position (number) */
  backwardButtonTitle: (0, _i18n.__)('Move block up from row %1$s to row %2$s'),

  /* translators: accessibility text. %1: current block position (number). %2: next block position (number) */
  forwardButtonTitle: (0, _i18n.__)('Move block down from row %1$s to row %2$s')
};
var KEYS = ['description', 'icon', 'title', 'actionTitle'];
var SETUP_GETTER = {
  description: getMoverDescription,
  icon: getArrowIcon,
  title: getMoverButtonTitle,
  actionTitle: getMoverActionTitle
};

function getMoversSetup(isStackedHorizontally, _ref) {
  var firstIndex = _ref.firstIndex,
      _ref$keys = _ref.keys,
      keys = _ref$keys === void 0 ? KEYS : _ref$keys;
  return keys.reduce(function (setup, key) {
    if (KEYS.includes(key)) {
      Object.assign(setup, (0, _defineProperty2.default)({}, key, getSetup(key, isStackedHorizontally, {
        firstIndex: firstIndex
      })));
    }

    return setup;
  }, {});
}

function switchButtonPropIfRTL(isBackwardButton, forwardButtonProp, backwardButtonProp, isStackedHorizontally) {
  if (_reactNative.I18nManager.isRTL && isStackedHorizontally) {
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

  return SETUP_GETTER[key].apply(null, (0, _toConsumableArray2.default)(args));
}

function applyRTLSetup(isBackwardButton, args) {
  return switchButtonPropIfRTL.apply(null, [isBackwardButton].concat((0, _toConsumableArray2.default)(args)));
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
    backward: (0, _i18n.sprintf)(actionTitlePrev, firstBlockTitle),
    forward: (0, _i18n.sprintf)(actionTitleNext, lastBlockTitle)
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
    backward: _i18n.sprintf.apply(void 0, [buttonTitlePrev].concat((0, _toConsumableArray2.default)(getIndexes(true)))),
    forward: _i18n.sprintf.apply(void 0, [buttonTitleNext].concat((0, _toConsumableArray2.default)(getIndexes(false))))
  };
}
//# sourceMappingURL=mover-description.native.js.map