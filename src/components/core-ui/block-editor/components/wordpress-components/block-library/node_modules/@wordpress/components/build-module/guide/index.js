import _slicedToArray from "@babel/runtime/helpers/esm/slicedToArray";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { useState, useEffect, Children } from '@wordpress/element';
import deprecated from '@wordpress/deprecated';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import Modal from '../modal';
import KeyboardShortcuts from '../keyboard-shortcuts';
import Button from '../button';
import PageControl from './page-control';
import FinishButton from './finish-button';
export default function Guide(_ref) {
  var children = _ref.children,
      className = _ref.className,
      contentLabel = _ref.contentLabel,
      finishButtonText = _ref.finishButtonText,
      onFinish = _ref.onFinish,
      _ref$pages = _ref.pages,
      pages = _ref$pages === void 0 ? [] : _ref$pages;

  var _useState = useState(0),
      _useState2 = _slicedToArray(_useState, 2),
      currentPage = _useState2[0],
      setCurrentPage = _useState2[1];

  useEffect(function () {
    if (Children.count(children)) {
      deprecated('Passing children to <Guide>', {
        alternative: 'the `pages` prop'
      });
    }
  }, [children]);

  if (Children.count(children)) {
    pages = Children.map(children, function (child) {
      return {
        content: child
      };
    });
  }

  var canGoBack = currentPage > 0;
  var canGoForward = currentPage < pages.length - 1;

  var goBack = function goBack() {
    if (canGoBack) {
      setCurrentPage(currentPage - 1);
    }
  };

  var goForward = function goForward() {
    if (canGoForward) {
      setCurrentPage(currentPage + 1);
    }
  };

  if (pages.length === 0) {
    return null;
  }

  return createElement(Modal, {
    className: classnames('components-guide', className),
    contentLabel: contentLabel,
    onRequestClose: onFinish
  }, createElement(KeyboardShortcuts, {
    key: currentPage,
    shortcuts: {
      left: goBack,
      right: goForward
    }
  }), createElement("div", {
    className: "components-guide__container"
  }, createElement("div", {
    className: "components-guide__page"
  }, pages[currentPage].image, createElement(PageControl, {
    currentPage: currentPage,
    numberOfPages: pages.length,
    setCurrentPage: setCurrentPage
  }), pages[currentPage].content, !canGoForward && createElement(FinishButton, {
    className: "components-guide__inline-finish-button",
    onClick: onFinish
  }, finishButtonText || __('Finish'))), createElement("div", {
    className: "components-guide__footer"
  }, canGoBack && createElement(Button, {
    className: "components-guide__back-button",
    onClick: goBack
  }, __('Previous')), canGoForward && createElement(Button, {
    className: "components-guide__forward-button",
    onClick: goForward
  }, __('Next')), !canGoForward && createElement(FinishButton, {
    className: "components-guide__finish-button",
    onClick: onFinish
  }, finishButtonText || __('Finish')))));
}
//# sourceMappingURL=index.js.map