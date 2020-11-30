"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _element = require("@wordpress/element");

var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));

var _compose = require("@wordpress/compose");

var _useSelect = _interopRequireDefault(require("../use-select"));

/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Higher-order component used to inject state-derived props using registered
 * selectors.
 *
 * @param {Function} mapSelectToProps Function called on every state change,
 *                                   expected to return object of props to
 *                                   merge with the component's own props.
 *
 * @example
 * ```js
 * function PriceDisplay( { price, currency } ) {
 * 	return new Intl.NumberFormat( 'en-US', {
 * 		style: 'currency',
 * 		currency,
 * 	} ).format( price );
 * }
 *
 * const { withSelect } = wp.data;
 *
 * const HammerPriceDisplay = withSelect( ( select, ownProps ) => {
 * 	const { getPrice } = select( 'my-shop' );
 * 	const { currency } = ownProps;
 *
 * 	return {
 * 		price: getPrice( 'hammer', currency ),
 * 	};
 * } )( PriceDisplay );
 *
 * // Rendered in the application:
 * //
 * //  <HammerPriceDisplay currency="USD" />
 * ```
 * In the above example, when `HammerPriceDisplay` is rendered into an
 * application, it will pass the price into the underlying `PriceDisplay`
 * component and update automatically if the price of a hammer ever changes in
 * the store.
 *
 * @return {WPComponent} Enhanced component with merged state data props.
 */
var withSelect = function withSelect(mapSelectToProps) {
  return (0, _compose.createHigherOrderComponent)(function (WrappedComponent) {
    return (0, _compose.pure)(function (ownProps) {
      var mapSelect = function mapSelect(select, registry) {
        return mapSelectToProps(select, ownProps, registry);
      };

      var mergeProps = (0, _useSelect.default)(mapSelect);
      return (0, _element.createElement)(WrappedComponent, (0, _extends2.default)({}, ownProps, mergeProps));
    });
  }, 'withSelect');
};

var _default = withSelect;
exports.default = _default;
//# sourceMappingURL=index.js.map