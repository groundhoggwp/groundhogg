"use strict";

var _interopRequireWildcard = require("@babel/runtime/helpers/interopRequireWildcard");

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _lodash = require("lodash");

var _classnames2 = _interopRequireDefault(require("classnames"));

var _blockEditor = require("@wordpress/block-editor");

var _data = require("@wordpress/data");

var _components = require("@wordpress/components");

var _compose = require("@wordpress/compose");

var _i18n = require("@wordpress/i18n");

var _useBlockNavigator2 = _interopRequireDefault(require("./use-block-navigator"));

var navIcons = _interopRequireWildcard(require("./icons"));

var _placeholder = _interopRequireDefault(require("./placeholder"));

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
function Navigation(_ref) {
  var _classnames;

  var selectedBlockHasDescendants = _ref.selectedBlockHasDescendants,
      attributes = _ref.attributes,
      setAttributes = _ref.setAttributes,
      clientId = _ref.clientId,
      hasExistingNavItems = _ref.hasExistingNavItems,
      isImmediateParentOfSelectedBlock = _ref.isImmediateParentOfSelectedBlock,
      isSelected = _ref.isSelected,
      updateInnerBlocks = _ref.updateInnerBlocks,
      className = _ref.className,
      _ref$hasSubmenuIndica = _ref.hasSubmenuIndicatorSetting,
      hasSubmenuIndicatorSetting = _ref$hasSubmenuIndica === void 0 ? true : _ref$hasSubmenuIndica,
      _ref$hasItemJustifica = _ref.hasItemJustificationControls,
      hasItemJustificationControls = _ref$hasItemJustifica === void 0 ? true : _ref$hasItemJustifica,
      _ref$hasListViewModal = _ref.hasListViewModal,
      hasListViewModal = _ref$hasListViewModal === void 0 ? true : _ref$hasListViewModal;

  var _useState = (0, _element.useState)(!hasExistingNavItems),
      _useState2 = (0, _slicedToArray2.default)(_useState, 2),
      isPlaceholderShown = _useState2[0],
      setIsPlaceholderShown = _useState2[1];

  var _useDispatch = (0, _data.useDispatch)('core/block-editor'),
      selectBlock = _useDispatch.selectBlock;

  var blockProps = (0, _blockEditor.__experimentalUseBlockWrapperProps)();

  var _useBlockNavigator = (0, _useBlockNavigator2.default)(clientId),
      navigatorToolbarButton = _useBlockNavigator.navigatorToolbarButton,
      navigatorModal = _useBlockNavigator.navigatorModal;

  if (isPlaceholderShown) {
    return (0, _element.createElement)("div", blockProps, (0, _element.createElement)(_placeholder.default, {
      onCreate: function onCreate(blocks, selectNavigationBlock) {
        setIsPlaceholderShown(false);
        updateInnerBlocks(blocks);

        if (selectNavigationBlock) {
          selectBlock(clientId);
        }
      }
    }));
  }

  function handleItemsAlignment(align) {
    return function () {
      var itemsJustification = attributes.itemsJustification === align ? undefined : align;
      setAttributes({
        itemsJustification: itemsJustification
      });
    };
  }

  var blockClassNames = (0, _classnames2.default)(className, (_classnames = {}, (0, _defineProperty2.default)(_classnames, "items-justified-".concat(attributes.itemsJustification), attributes.itemsJustification), (0, _defineProperty2.default)(_classnames, 'is-vertical', attributes.orientation === 'vertical'), _classnames));
  return (0, _element.createElement)(_element.Fragment, null, (0, _element.createElement)(_blockEditor.BlockControls, null, hasItemJustificationControls && (0, _element.createElement)(_components.ToolbarGroup, {
    icon: attributes.itemsJustification ? navIcons["justify".concat((0, _lodash.upperFirst)(attributes.itemsJustification), "Icon")] : navIcons.justifyLeftIcon,
    label: (0, _i18n.__)('Change items justification'),
    isCollapsed: true,
    controls: [{
      icon: navIcons.justifyLeftIcon,
      title: (0, _i18n.__)('Justify items left'),
      isActive: 'left' === attributes.itemsJustification,
      onClick: handleItemsAlignment('left')
    }, {
      icon: navIcons.justifyCenterIcon,
      title: (0, _i18n.__)('Justify items center'),
      isActive: 'center' === attributes.itemsJustification,
      onClick: handleItemsAlignment('center')
    }, {
      icon: navIcons.justifyRightIcon,
      title: (0, _i18n.__)('Justify items right'),
      isActive: 'right' === attributes.itemsJustification,
      onClick: handleItemsAlignment('right')
    }]
  }), hasListViewModal && (0, _element.createElement)(_components.ToolbarGroup, null, navigatorToolbarButton)), hasListViewModal && navigatorModal, (0, _element.createElement)(_blockEditor.InspectorControls, null, hasSubmenuIndicatorSetting && (0, _element.createElement)(_components.PanelBody, {
    title: (0, _i18n.__)('Display settings')
  }, (0, _element.createElement)(_components.ToggleControl, {
    checked: attributes.showSubmenuIcon,
    onChange: function onChange(value) {
      setAttributes({
        showSubmenuIcon: value
      });
    },
    label: (0, _i18n.__)('Show submenu indicator icons')
  }))), (0, _element.createElement)("nav", (0, _extends2.default)({}, blockProps, {
    className: (0, _classnames2.default)(blockProps.className, blockClassNames)
  }), (0, _element.createElement)(_blockEditor.InnerBlocks, {
    allowedBlocks: ['core/navigation-link', 'core/search', 'core/social-links'],
    renderAppender: isImmediateParentOfSelectedBlock && !selectedBlockHasDescendants || isSelected ? _blockEditor.InnerBlocks.DefaultAppender : false,
    templateInsertUpdatesSelection: false,
    orientation: attributes.orientation || 'horizontal',
    __experimentalTagName: "ul",
    __experimentalAppenderTagName: "li",
    __experimentalPassedProps: {
      className: 'wp-block-navigation__container'
    },
    __experimentalCaptureToolbars: true // Template lock set to false here so that the Nav
    // Block on the experimental menus screen does not
    // inherit templateLock={ 'all' }.
    ,
    templateLock: false
  })));
}

var _default = (0, _compose.compose)([(0, _data.withSelect)(function (select, _ref2) {
  var _getClientIdsOfDescen;

  var clientId = _ref2.clientId;
  var innerBlocks = select('core/block-editor').getBlocks(clientId);

  var _select = select('core/block-editor'),
      getClientIdsOfDescendants = _select.getClientIdsOfDescendants,
      hasSelectedInnerBlock = _select.hasSelectedInnerBlock,
      getSelectedBlockClientId = _select.getSelectedBlockClientId;

  var isImmediateParentOfSelectedBlock = hasSelectedInnerBlock(clientId, false);
  var selectedBlockId = getSelectedBlockClientId();
  var selectedBlockHasDescendants = !!((_getClientIdsOfDescen = getClientIdsOfDescendants([selectedBlockId])) === null || _getClientIdsOfDescen === void 0 ? void 0 : _getClientIdsOfDescen.length);
  return {
    isImmediateParentOfSelectedBlock: isImmediateParentOfSelectedBlock,
    selectedBlockHasDescendants: selectedBlockHasDescendants,
    hasExistingNavItems: !!innerBlocks.length
  };
}), (0, _data.withDispatch)(function (dispatch, _ref3) {
  var clientId = _ref3.clientId;
  return {
    updateInnerBlocks: function updateInnerBlocks(blocks) {
      if ((blocks === null || blocks === void 0 ? void 0 : blocks.length) === 0) {
        return false;
      }

      dispatch('core/block-editor').replaceInnerBlocks(clientId, blocks);
    }
  };
})])(Navigation);

exports.default = _default;
//# sourceMappingURL=edit.js.map