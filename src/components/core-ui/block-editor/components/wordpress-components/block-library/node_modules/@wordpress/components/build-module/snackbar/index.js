import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { noop } from 'lodash';
import classnames from 'classnames';
/**
 * WordPress dependencies
 */

import { speak } from '@wordpress/a11y';
import { useEffect, forwardRef, renderToString } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import warning from '@wordpress/warning';
/**
 * Internal dependencies
 */

import { Button } from '../';
var NOTICE_TIMEOUT = 10000;
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

function Snackbar(_ref, ref) {
  var className = _ref.className,
      children = _ref.children,
      _ref$spokenMessage = _ref.spokenMessage,
      spokenMessage = _ref$spokenMessage === void 0 ? children : _ref$spokenMessage,
      _ref$politeness = _ref.politeness,
      politeness = _ref$politeness === void 0 ? 'polite' : _ref$politeness,
      _ref$actions = _ref.actions,
      actions = _ref$actions === void 0 ? [] : _ref$actions,
      _ref$onRemove = _ref.onRemove,
      onRemove = _ref$onRemove === void 0 ? noop : _ref$onRemove;
  useSpokenMessage(spokenMessage, politeness);
  useEffect(function () {
    var timeoutHandle = setTimeout(function () {
      onRemove();
    }, NOTICE_TIMEOUT);
    return function () {
      return clearTimeout(timeoutHandle);
    };
  }, []);
  var classes = classnames(className, 'components-snackbar');

  if (actions && actions.length > 1) {
    // we need to inform developers that snackbar only accepts 1 action
    typeof process !== "undefined" && process.env && process.env.NODE_ENV !== "production" ? warning('Snackbar can only have 1 action, use Notice if your message require many messages') : void 0; // return first element only while keeping it inside an array

    actions = [actions[0]];
  }

  return createElement("div", {
    ref: ref,
    className: classes,
    onClick: onRemove,
    tabIndex: "0",
    role: "button",
    onKeyPress: onRemove,
    "aria-label": __('Dismiss this notice')
  }, createElement("div", {
    className: "components-snackbar__content"
  }, children, actions.map(function (_ref2, index) {
    var label = _ref2.label,
        _onClick = _ref2.onClick,
        url = _ref2.url;
    return createElement(Button, {
      key: index,
      href: url,
      isTertiary: true,
      onClick: function onClick(event) {
        event.stopPropagation();

        if (_onClick) {
          _onClick(event);
        }
      },
      className: "components-snackbar__action"
    }, label);
  })));
}

export default forwardRef(Snackbar);
//# sourceMappingURL=index.js.map