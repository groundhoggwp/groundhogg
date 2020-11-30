/**
 * Given a block client ID, returns the corresponding DOM node for the block,
 * if exists. As much as possible, this helper should be avoided, and used only
 * in cases where isolated behaviors need remote access to a block node.
 *
 * @param {string} clientId Block client ID.
 *
 * @return {Element?} Block DOM node.
 */
export function getBlockDOMNode(clientId: string): Element | null;
/**
 * Returns the preview container DOM node for a given block client ID, or
 * undefined if the container cannot be determined.
 *
 * @param {string} clientId Block client ID.
 *
 * @return {Node|undefined} Preview container DOM node.
 */
export function getBlockPreviewContainerDOMNode(clientId: string): Node | undefined;
/**
 * Returns true if the given element is a block focus stop. Blocks without their
 * own text fields rely on the focus stop to be keyboard navigable.
 *
 * @param {Element} element Element to test.
 *
 * @return {boolean} Whether element is a block focus stop.
 */
export function isBlockFocusStop(element: Element): boolean;
/**
 * Returns true if two elements are contained within the same block.
 *
 * @param {Element} a First element.
 * @param {Element} b Second element.
 *
 * @return {boolean} Whether elements are in the same block.
 */
export function isInSameBlock(a: Element, b: Element): boolean;
/**
 * Returns true if an element is considered part of the block and not its
 * children.
 *
 * @param {Element} blockElement Block container element.
 * @param {Element} element      Element.
 *
 * @return {boolean} Whether element is in the block Element but not its
 *                   children.
 */
export function isInsideRootBlock(blockElement: Element, element: Element): boolean;
/**
 * Returns true if the given element contains inner blocks (an InnerBlocks
 * element).
 *
 * @param {Element} element Element to test.
 *
 * @return {boolean} Whether element contains inner blocks.
 */
export function hasInnerBlocksContext(element: Element): boolean;
/**
 * Finds the block client ID given any DOM node inside the block.
 *
 * @param {Node?} node DOM node.
 *
 * @return {string|undefined} Client ID or undefined if the node is not part of
 *                            a block.
 */
export function getBlockClientId(node: Node | null): string | undefined;
//# sourceMappingURL=dom.d.ts.map