import _defineProperty from "@babel/runtime/helpers/esm/defineProperty";
import _toConsumableArray from "@babel/runtime/helpers/esm/toConsumableArray";
import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement, Fragment } from "@wordpress/element";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

/**
 * External dependencies
 */
import { some } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { createBlock, parse } from '@wordpress/blocks';
import { Button, CustomSelectControl, Spinner, Placeholder } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { forwardRef, useCallback, useMemo, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { navigation as icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import createDataTree from './create-data-tree';
var CREATE_EMPTY_OPTION_VALUE = '__CREATE_EMPTY__';
var CREATE_FROM_PAGES_OPTION_VALUE = '__CREATE_FROM_PAGES__';
/**
 * Get instruction text for the Placeholder component.
 *
 * @param {boolean} hasMenus Flag that indicates if there are menus.
 * @param {boolean} hasPages Flag that indicates if there are pages.
 *
 * @return {string} Text to display as the placeholder instructions.
 */

function getPlaceholderInstructions(hasMenus, hasPages) {
  if (hasMenus && hasPages) {
    return __('Use an existing menu here, include all top-level pages, or add an empty Navigation block.');
  } else if (hasMenus && !hasPages) {
    return __('Use an existing menu here, or add an empty Navigation block.');
  } else if (!hasMenus && hasPages) {
    return __('Include all existing pages here, or add an empty Navigation block.');
  }

  return __('Create an empty navigation.');
}
/**
 * Return the menu id if the user has one selected.
 *
 * @param {Object} selectedCreateOption An object containing details of
 *                                      the selected create option.
 *
 * @return {number|undefined} The menu id.
 */


function getSelectedMenu(selectedCreateOption) {
  var optionId = selectedCreateOption === null || selectedCreateOption === void 0 ? void 0 : selectedCreateOption.id;
  return optionId !== undefined && Number.isInteger(optionId) ? optionId : undefined;
}
/**
 * A recursive function that maps menu item nodes to blocks.
 *
 * @param {Object[]} menuItems An array of menu items.
 * @return {WPBlock[]} An array of blocks.
 */


function mapMenuItemsToBlocks(menuItems) {
  return menuItems.map(function (menuItem) {
    var _menuItem$xfn, _menuItem$classes, _menuItem$children;

    if (menuItem.type === 'block') {
      var _parse = parse(menuItem.content.raw),
          _parse2 = _slicedToArray(_parse, 1),
          block = _parse2[0];

      if (!block) {
        return createBlock('core/freeform', {
          content: menuItem.content
        });
      }

      return block;
    }

    var attributes = {
      label: !menuItem.title.rendered ? __('(no title)') : menuItem.title.rendered,
      opensInNewTab: menuItem.target === '_blank'
    };

    if (menuItem.url) {
      attributes.url = menuItem.url;
    }

    if (menuItem.description) {
      attributes.description = menuItem.description;
    }

    if (((_menuItem$xfn = menuItem.xfn) === null || _menuItem$xfn === void 0 ? void 0 : _menuItem$xfn.length) && some(menuItem.xfn)) {
      attributes.rel = menuItem.xfn.join(' ');
    }

    if (((_menuItem$classes = menuItem.classes) === null || _menuItem$classes === void 0 ? void 0 : _menuItem$classes.length) && some(menuItem.classes)) {
      attributes.className = menuItem.classes.join(' ');
    }

    var innerBlocks = ((_menuItem$children = menuItem.children) === null || _menuItem$children === void 0 ? void 0 : _menuItem$children.length) ? mapMenuItemsToBlocks(menuItem.children) : [];
    return createBlock('core/navigation-link', attributes, innerBlocks);
  });
}
/**
 * Convert a flat menu item structure to a nested blocks structure.
 *
 * @param {Object[]} menuItems An array of menu items.
 *
 * @return {WPBlock[]} An array of blocks.
 */


function convertMenuItemsToBlocks(menuItems) {
  if (!menuItems) {
    return null;
  }

  var menuTree = createDataTree(menuItems);
  return mapMenuItemsToBlocks(menuTree);
}
/**
 * Convert pages to blocks.
 *
 * @param {Object[]} pages An array of pages.
 *
 * @return {WPBlock[]} An array of blocks.
 */


function convertPagesToBlocks(pages) {
  if (!pages) {
    return null;
  }

  return pages.map(function (_ref) {
    var title = _ref.title,
        type = _ref.type,
        url = _ref.link,
        id = _ref.id;
    return createBlock('core/navigation-link', {
      type: type,
      id: id,
      url: url,
      label: !title.rendered ? __('(no title)') : title.rendered,
      opensInNewTab: false
    });
  });
}

function NavigationPlaceholder(_ref2, ref) {
  var onCreate = _ref2.onCreate;

  var _useState = useState(),
      _useState2 = _slicedToArray(_useState, 2),
      selectedCreateOption = _useState2[0],
      setSelectedCreateOption = _useState2[1];

  var _useState3 = useState(false),
      _useState4 = _slicedToArray(_useState3, 2),
      isCreatingFromMenu = _useState4[0],
      setIsCreatingFromMenu = _useState4[1];

  var _useSelect = useSelect(function (select) {
    var _select = select('core'),
        getEntityRecords = _select.getEntityRecords,
        getMenus = _select.getMenus,
        getMenuItems = _select.getMenuItems,
        isResolving = _select.isResolving,
        hasFinishedResolution = _select.hasFinishedResolution;

    var pagesParameters = ['postType', 'page', {
      parent: 0,
      order: 'asc',
      orderby: 'id'
    }];
    var menusParameters = [{
      per_page: -1
    }];
    var selectedMenu = getSelectedMenu(selectedCreateOption);
    var hasSelectedMenu = selectedMenu !== undefined;
    var menuItemsParameters = hasSelectedMenu ? [{
      menus: selectedMenu,
      per_page: -1
    }] : undefined;
    return {
      pages: getEntityRecords.apply(void 0, pagesParameters),
      isResolvingPages: isResolving('getEntityRecords', pagesParameters),
      hasResolvedPages: hasFinishedResolution('getEntityRecords', pagesParameters),
      menus: getMenus.apply(void 0, menusParameters),
      isResolvingMenus: isResolving('getMenus', menusParameters),
      hasResolvedMenus: hasFinishedResolution('getMenus', menusParameters),
      menuItems: hasSelectedMenu ? getMenuItems.apply(void 0, _toConsumableArray(menuItemsParameters)) : undefined,
      hasResolvedMenuItems: hasSelectedMenu ? hasFinishedResolution('getMenuItems', menuItemsParameters) : false
    };
  }, [selectedCreateOption]),
      pages = _useSelect.pages,
      isResolvingPages = _useSelect.isResolvingPages,
      hasResolvedPages = _useSelect.hasResolvedPages,
      menus = _useSelect.menus,
      isResolvingMenus = _useSelect.isResolvingMenus,
      hasResolvedMenus = _useSelect.hasResolvedMenus,
      menuItems = _useSelect.menuItems,
      hasResolvedMenuItems = _useSelect.hasResolvedMenuItems;

  var hasPages = !!(hasResolvedPages && (pages === null || pages === void 0 ? void 0 : pages.length));
  var hasMenus = !!(hasResolvedMenus && (menus === null || menus === void 0 ? void 0 : menus.length));
  var isLoading = isResolvingPages || isResolvingMenus;
  var createOptions = useMemo(function () {
    return [].concat(_toConsumableArray(hasMenus ? menus : []), [{
      id: CREATE_EMPTY_OPTION_VALUE,
      name: __('Create empty Navigation'),
      className: 'is-create-empty-option'
    }], _toConsumableArray(hasPages ? [{
      id: CREATE_FROM_PAGES_OPTION_VALUE,
      name: __('Create from all top-level pages')
    }] : []));
  }, [menus, hasMenus, hasPages]);
  var createFromMenu = useCallback(function () {
    // If an empty menu was selected, create an empty block.
    if (!menuItems.length) {
      onCreate([]);
      return;
    }

    var blocks = convertMenuItemsToBlocks(menuItems);
    var selectNavigationBlock = true;
    onCreate(blocks, selectNavigationBlock);
  });
  var onCreateButtonClick = useCallback(function () {
    if (!selectedCreateOption) {
      return;
    }

    var key = selectedCreateOption.key;

    switch (key) {
      case CREATE_EMPTY_OPTION_VALUE:
        {
          onCreate([]);
          return;
        }

      case CREATE_FROM_PAGES_OPTION_VALUE:
        {
          var blocks = convertPagesToBlocks(pages);
          var selectNavigationBlock = true;
          onCreate(blocks, selectNavigationBlock);
          return;
        }
      // The default case indicates that a menu was selected.

      default:
        // If we have menu items, create the block right away.
        if (hasResolvedMenuItems) {
          createFromMenu();
          return;
        } // Otherwise, create the block when resolution finishes.


        setIsCreatingFromMenu(true);
    }
  });
  useEffect(function () {
    // If the user selected a menu but we had to wait for menu items to
    // finish resolving, then create the block once resolution finishes.
    if (isCreatingFromMenu && hasResolvedMenuItems) {
      createFromMenu();
      setIsCreatingFromMenu(false);
    }
  }, [isCreatingFromMenu, hasResolvedMenuItems]);

  if (hasMenus && !selectedCreateOption) {
    setSelectedCreateOption(createOptions[0]);
  }

  return createElement(Placeholder, {
    className: "wp-block-navigation-placeholder",
    icon: icon,
    label: __('Navigation')
  }, isLoading && createElement("div", {
    ref: ref
  }, createElement(Spinner, null), " ", __('Loading…')), !isLoading && createElement("div", {
    ref: ref,
    className: "wp-block-navigation-placeholder__actions"
  }, createElement(Fragment, null, createElement(CustomSelectControl, {
    className: classnames('wp-block-navigation-placeholder__select-control', {
      'has-menus': hasMenus
    }),
    label: !isLoading ? getPlaceholderInstructions(hasMenus, hasPages) : undefined,
    value: selectedCreateOption || createOptions[0],
    onChange: function onChange(_ref3) {
      var selectedItem = _ref3.selectedItem;

      if ((selectedItem === null || selectedItem === void 0 ? void 0 : selectedItem.key) === selectedCreateOption) {
        return;
      }

      setSelectedCreateOption(selectedItem);
      setIsCreatingFromMenu(false);
    },
    options: createOptions.map(function (option) {
      return _objectSpread(_objectSpread({}, option), {}, {
        key: option.id
      });
    })
  }), createElement(Button, {
    isSecondary: true,
    className: "wp-block-navigation-placeholder__button",
    disabled: !selectedCreateOption,
    isBusy: isCreatingFromMenu,
    onClick: onCreateButtonClick
  }, __('Create')))));
}

export default forwardRef(NavigationPlaceholder);
//# sourceMappingURL=placeholder.js.map