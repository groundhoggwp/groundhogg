import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { noop } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
import { RawHTML, useEffect, renderToString } from '@wordpress/element';
import { speak } from '@wordpress/a11y';
import { close } from '@wordpress/icons';
/**
 * Internal dependencies
 */

import { Button } from '../';
/** @typedef {import('@wordpress/element').WPElement} WPElement */

/**
 * Custom hook which announces the message with the given politeness, if a
 * valid message is provided.
 *
 * @param {string|WPElement}     [message]  Message to announce.
 * @param {'polite'|'assertive'} politeness Politeness to announce.
 */

function useSpokenMessage(message, politeness) {
  var spokenMessage = typeof message === 'string' ? message : renderToString(message);
  useEffect(function () {
    if (spokenMessage) {
      speak(spokenMessage, politeness);
    }
  }, [spokenMessage, politeness]);
}
/**
 * Given a notice status, returns an assumed default politeness for the status.
 * Defaults to 'assertive'.
 *
 * @param {string} [status] Notice status.
 *
 * @return {'polite'|'assertive'} Notice politeness.
 */


function getDefaultPoliteness(status) {
  switch (status) {
    case 'success':
    case 'warning':
    case 'info':
      return 'polite';

    case 'error':
    default:
      return 'assertive';
  }
}

function Notice(_ref) {
  var className = _ref.className,
      _ref$status = _ref.status,
      status = _ref$status === void 0 ? 'info' : _ref$status,
      children = _ref.children,
      _ref$spokenMessage = _ref.spokenMessage,
      spokenMessage = _ref$spokenMessage === void 0 ? children : _ref$spokenMessage,
      _ref$onRemove = _ref.onRemove,
      onRemove = _ref$onRemove === void 0 ? noop : _ref$onRemove,
      _ref$isDismissible = _ref.isDismissible,
      isDismissible = _ref$isDismissible === void 0 ? true : _ref$isDismissible,
      _ref$actions = _ref.actions,
      actions = _ref$actions === void 0 ? [] : _ref$actions,
      _ref$politeness = _ref.politeness,
      politeness = _ref$politeness === void 0 ? getDefaultPoliteness(status) : _ref$politeness,
      __unstableHTML = _ref.__unstableHTML;
  useSpokenMessage(spokenMessage, politeness);
  var classes = classnames(className, 'components-notice', 'is-' + status, {
    'is-dismissible': isDismissible
  });

  if (__unstableHTML) {
    children = createElement(RawHTML, null, children);
  }

  return createElement("div", {
    className: classes
  }, createElement("div", {
    className: "components-notice__content"
  }, children, actions.map(function (_ref2, index) {
    var buttonCustomClasses = _ref2.className,
        label = _ref2.label,
        isPrimary = _ref2.isPrimary,
        _ref2$noDefaultClasse = _ref2.noDefaultClasses,
        noDefaultClasses = _ref2$noDefaultClasse === void 0 ? false : _ref2$noDefaultClasse,
        onClick = _ref2.onClick,
        url = _ref2.url;
    return createElement(Button, {
      key: index,
      href: url,
      isPrimary: isPrimary,
      isSecondary: !noDefaultClasses && !url,
      isLink: !noDefaultClasses && !!url,
      onClick: url ? undefined : onClick,
      className: classnames('components-notice__action', buttonCustomClasses)
    }, label);
  })), isDismissible && createElement(Button, {
    className: "components-notice__dismiss",
    icon: close,
    label: __('Dismiss this notice'),
    onClick: onRemove,
    showTooltip: false
  }));
}

export default Notice;
//# sourceMappingURL=index.js.map