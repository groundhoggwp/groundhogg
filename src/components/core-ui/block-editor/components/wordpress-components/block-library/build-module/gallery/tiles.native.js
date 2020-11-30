import { createElement } from "@wordpress/element";

/**
 * External dependencies
 */
import { View, StyleSheet } from 'react-native';
/**
 * WordPress dependencies
 */

import { Children } from '@wordpress/element';
/**
 * Internal dependencies
 */

import styles from './tiles-styles.scss';

function Tiles(props) {
  var columns = props.columns,
      children = props.children,
      _props$spacing = props.spacing,
      spacing = _props$spacing === void 0 ? 10 : _props$spacing,
      style = props.style;
  var compose = StyleSheet.compose;
  var tileCount = Children.count(children);
  var lastTile = tileCount - 1;
  var lastRow = Math.floor(lastTile / columns);
  var wrappedChildren = Children.map(children, function (child, index) {
    /** Since we don't have `calc()`, we must calculate our spacings here in
     * order to preserve even spacing between tiles and equal width for tiles
     * in a given row.
     *
     * In order to ensure equal sizing of tile contents, we distribute the
     * spacing such that each tile has an equal "share" of the fixed spacing. To
     * keep the tiles properly aligned within their rows, we calculate the left
     * and right paddings based on the tile's relative position within the row.
     *
     * Note: we use padding instead of margins so that the fixed spacing is
     * included within the relative spacing (i.e. width percentage), and
     * wrapping behavior is preserved.
     *
     *  - The left most tile in a row must have left padding of zero.
     *  - The right most tile in a row must have a right padding of zero.
     *
     * The values of these left and right paddings are interpolated for tiles in
     * between. The right padding is complementary with the left padding of the
     * next tile (i.e. the right padding of [tile n] + the left padding of
     * [tile n + 1] will be equal for all tiles except the last one in a given
     * row).
     */
    var row = Math.floor(index / columns);
    var rowLength = row === lastRow ? lastTile % columns + 1 : columns;
    var indexInRow = index % columns;
    return createElement(View, {
      style: [styles.tileStyle, {
        width: "".concat(100 / rowLength, "%"),
        paddingLeft: spacing * (indexInRow / rowLength),
        paddingRight: spacing * (1 - (indexInRow + 1) / rowLength),
        paddingTop: row === 0 ? 0 : spacing / 2,
        paddingBottom: row === lastRow ? 0 : spacing / 2
      }]
    }, child);
  });
  var containerStyle = compose(styles.containerStyle, style);
  return createElement(View, {
    style: containerStyle
  }, wrappedChildren);
}

export default Tiles;
//# sourceMappingURL=tiles.native.js.map