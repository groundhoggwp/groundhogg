import _extends from "@babel/runtime/helpers/esm/extends";
import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

/**
 * External dependencies
 */
import { upperFirst } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useState } from '@wordpress/element';
import { InnerBlocks, InspectorControls, BlockControls, __experimentalUseBlockWrapperProps as useBlockWrapperProps } from '@wordpress/block-editor';
import { useDispatch, withSelect, withDispatch } from '@wordpress/data';
import { PanelBody, ToggleControl, ToolbarGroup } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import useBlockNavigator from './use-block-navigator';
import * as navIcons from './icons';
import NavigationPlaceholder from './placeholder';

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

  var _useState = useState(!hasExistingNavItems),
      _useState2 = _slicedToArray(_useState, 2),
      isPlaceholderShown = _useState2[0],
      setIsPlaceholderShown = _useState2[1];

  var _useDispatch = useDispatch('core/block-editor'),
      selectBlock = _useDispatch.selectBlock;

  var blockProps = useBlockWrapperProps();

  var _useBlockNavigator = useBlockNavigator(clientId),
      navigatorToolbarButton = _useBlockNavigator.navigatorToolbarButton,
      navigatorModal = _useBlockNavigator.navigatorModal;

  if (isPlaceholderShown) {
    return createElement("div", blockProps, createElement(NavigationPlaceholder, {
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

  var blockClassNames = classnames(className, (_classnames = {}, _defineProperty(_classnames, "items-justified-".concat(attributes.itemsJustification), attributes.itemsJustification), _defineProperty(_classnames, 'is-vertical', attributes.orientation === 'vertical'), _classnames));
  return createElement(Fragment, null, createElement(BlockControls, null, hasItemJustificationControls && createElement(ToolbarGroup, {
    icon: attributes.itemsJustification ? navIcons["justify".concat(upperFirst(attributes.itemsJustification), "Icon")] : navIcons.justifyLeftIcon,
    label: __('Change items justification'),
    isCollapsed: true,
    controls: [{
      icon: navIcons.justifyLeftIcon,
      title: __('Justify items left'),
      isActive: 'left' === attributes.itemsJustification,
      onClick: handleItemsAlignment('left')
    }, {
      icon: navIcons.justifyCenterIcon,
      title: __('Justify items center'),
      isActive: 'center' === attributes.itemsJustification,
      onClick: handleItemsAlignment('center')
    }, {
      icon: navIcons.justifyRightIcon,
      title: __('Justify items right'),
      isActive: 'right' === attributes.itemsJustification,
      onClick: handleItemsAlignment('right')
    }]
  }), hasListViewModal && createElement(ToolbarGroup, null, navigatorToolbarButton)), hasListViewModal && navigatorModal, createElement(InspectorControls, null, hasSubmenuIndicatorSetting && createElement(PanelBody, {
    title: __('Display settings')
  }, createElement(ToggleControl, {
    checked: attributes.showSubmenuIcon,
    onChange: function onChange(value) {
      setAttributes({
        showSubmenuIcon: value
      });
    },
    label: __('Show submenu indicator icons')
  }))), createElement("nav", _extends({}, blockProps, {
    className: classnames(blockProps.className, blockClassNames)
  }), createElement(InnerBlocks, {
    allowedBlocks: ['core/navigation-link', 'core/search', 'core/social-links'],
    renderAppender: isImmediateParentOfSelectedBlock && !selectedBlockHasDescendants || isSelected ? InnerBlocks.DefaultAppender : false,
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

export default compose([withSelect(function (select, _ref2) {
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
}), withDispatch(function (dispatch, _ref3) {
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
//# sourceMappingURL=edit.js.map