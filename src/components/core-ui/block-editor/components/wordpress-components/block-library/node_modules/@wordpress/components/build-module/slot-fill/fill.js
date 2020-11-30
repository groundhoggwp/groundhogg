import _extends from "@babel/runtime/helpers/esm/extends";
import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { isFunction } from 'lodash';
/**
 * WordPress dependencies
 */

import { createPortal, useLayoutEffect, useRef } from '@wordpress/element';
/**
 * Internal dependencies
 */

import { Consumer, useSlot } from './context';
var occurrences = 0;

function FillComponent(_ref) {
  var name = _ref.name,
      children = _ref.children,
      registerFill = _ref.registerFill,
      unregisterFill = _ref.unregisterFill;
  var slot = useSlot(name);
  var ref = useRef({
    name: name,
    children: children
  });

  if (!ref.current.occurrence) {
    ref.current.occurrence = ++occurrences;
  }

  useLayoutEffect(function () {
    registerFill(name, ref.current);
    return function () {
      return unregisterFill(name, ref.current);
    };
  }, []);
  useLayoutEffect(function () {
    ref.current.children = children;

    if (slot) {
      slot.forceUpdate();
    }
  }, [children]);
  useLayoutEffect(function () {
    if (name === ref.current.name) {
      // ignore initial effect
      return;
    }

    unregisterFill(ref.current.name, ref.current);
    ref.current.name = name;
    registerFill(name, ref.current);
  }, [name]);

  if (!slot || !slot.node) {
    return null;
  } // If a function is passed as a child, provide it with the fillProps.


  if (isFunction(children)) {
    children = children(slot.props.fillProps);
  }

  return createPortal(children, slot.node);
}

var Fill = function Fill(props) {
  return createElement(Consumer, null, function (_ref2) {
    var registerFill = _ref2.registerFill,
        unregisterFill = _ref2.unregisterFill;
    return createElement(FillComponent, _extends({}, props, {
      registerFill: registerFill,
      unregisterFill: unregisterFill
    }));
  });
};

export default Fill;
//# sourceMappingURL=fill.js.map