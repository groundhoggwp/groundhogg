"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getNearestBlockIndex = getNearestBlockIndex;
exports.default = useBlockDropZone;

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _components = require("@wordpress/components");

var _data = require("@wordpress/data");

var _element = require("@wordpress/element");

var _useOnBlockDrop = _interopRequireDefault(require("../use-on-block-drop"));

var _math = require("../../utils/math");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/** @typedef {import('../../utils/math').WPPoint} WPPoint */

/**
 * The orientation of a block list.
 *
 * @typedef {'horizontal'|'vertical'|undefined} WPBlockListOrientation
 */

/**
 * Given a list of block DOM elements finds the index that a block should be dropped
 * at.
 *
 * @param {Element[]}              elements    Array of DOM elements that represent each block in a block list.
 * @param {WPPoint}                position    The position of the item being dragged.
 * @param {WPBlockListOrientation} orientation The orientation of a block list.
 *
 * @return {number|undefined} The block index that's closest to the drag position.
 */
function getNearestBlockIndex(elements, position, orientation) {
  var allowedEdges = orientation === 'horizontal' ? ['left', 'right'] : ['top', 'bottom'];
  var candidateIndex;
  var candidateDistance;
  elements.forEach(function (element, index) {
    // Ensure the element is a block. It should have the `wp-block` class.
    if (!element.classList.contains('wp-block')) {
      return;
    }

    var rect = element.getBoundingClientRect();

    var _getDistanceToNearest = (0, _math.getDistanceToNearestEdge)(position, rect, allowedEdges),
        _getDistanceToNearest2 = (0, _slicedToArray2.default)(_getDistanceToNearest, 2),
        distance = _getDistanceToNearest2[0],
        edge = _getDistanceToNearest2[1];

    if (candidateDistance === undefined || distance < candidateDistance) {
      // If the user is dropping to the trailing edge of the block
      // add 1 to the index to represent dragging after.
      var isTrailingEdge = edge === 'bottom' || edge === 'right';
      var offset = isTrailingEdge ? 1 : 0; // If the target is the dragged block itself and another 1 to
      // index as the dragged block is set to `display: none` and
      // should be skipped in the calculation.

      var isTargetDraggedBlock = isTrailingEdge && elements[index + 1] && elements[index + 1].classList.contains('is-dragging');
      offset += isTargetDraggedBlock ? 1 : 0; // Update the currently known best candidate.

      candidateDistance = distance;
      candidateIndex = index + offset;
    }
  });
  return candidateIndex;
}
/**
 * @typedef  {Object} WPBlockDropZoneConfig
 * @property {Object} element      A React ref object pointing to the block list's DOM element.
 * @property {string} rootClientId The root client id for the block list.
 */

/**
 * A React hook that can be used to make a block list handle drag and drop.
 *
 * @param {WPBlockDropZoneConfig} dropZoneConfig configuration data for the drop zone.
 *
 * @return {number|undefined} The block index that's closest to the drag position.
 */


function useBlockDropZone(_ref) {
  var element = _ref.element,
      _ref$rootClientId = _ref.rootClientId,
      targetRootClientId = _ref$rootClientId === void 0 ? '' : _ref$rootClientId;

  var _useState = (0, _element.useState)(null),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      targetBlockIndex = _useState2[0],
      setTargetBlockIndex = _useState2[1];

  var _useSelect = (0, _data.useSelect)(function (select) {
    var _getBlockListSettings;

    var _select = select('core/block-editor'),
        getBlockListSettings = _select.getBlockListSettings,
        getTemplateLock = _select.getTemplateLock;

    return {
      isLockedAll: getTemplateLock(targetRootClientId) === 'all',
      orientation: (_getBlockListSettings = getBlockListSettings(targetRootClientId)) === null || _getBlockListSettings === void 0 ? void 0 : _getBlockListSettings.orientation
    };
  }, [targetRootClientId]),
      isLockedAll = _useSelect.isLockedAll,
      orientation = _useSelect.orientation;

  var dropEventHandlers = (0, _useOnBlockDrop.default)(targetRootClientId, targetBlockIndex);

  var _useDropZone = (0, _components.__unstableUseDropZone)(_objectSpread({
    element: element,
    isDisabled: isLockedAll,
    withPosition: true
  }, dropEventHandlers)),
      position = _useDropZone.position;

  (0, _element.useEffect)(function () {
    if (position) {
      var blockElements = Array.from(element.current.children);
      var targetIndex = getNearestBlockIndex(blockElements, position, orientation);
      setTargetBlockIndex(targetIndex === undefined ? 0 : targetIndex);
    }
  }, [position]);

  if (position) {
    return targetBlockIndex;
  }
}
//# sourceMappingURL=index.js.map