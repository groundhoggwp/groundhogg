/**
 * A set of tokens.
 *
 * @see https://dom.spec.whatwg.org/#domtokenlist
 */
export default class TokenList {
    /**
     * Constructs a new instance of TokenList.
     *
     * @param {string} initialValue Initial value to assign.
     */
    constructor(initialValue?: string);
    /**
     * Replaces the associated set with a new string value.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-value
     *
     * @param {string} value New token set as string.
     */
    set value(arg: string);
    /**
     * Returns the associated set as string.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-value
     *
     * @return {string} Token set as string.
     */
    get value(): string;
    /** @type {string} */
    _currentValue: string;
    /** @type {string[]} */
    _valueAsArray: string[];
    /**
     * @param {Parameters<Array<string>['entries']>} args
     */
    entries(): IterableIterator<[number, string]>;
    /**
     * @param {Parameters<Array<string>['forEach']>} args
     */
    forEach(callbackfn: (value: string, index: number, array: string[]) => void, thisArg?: any): void;
    /**
     * @param {Parameters<Array<string>['keys']>} args
     */
    keys(): IterableIterator<number>;
    /**
     * @param {Parameters<Array<string>['values']>} args
     */
    values(): IterableIterator<string>;
    /**
     * Returns the number of tokens.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-length
     *
     * @return {number} Number of tokens.
     */
    get length(): number;
    /**
     * Returns the stringified form of the TokenList.
     *
     * @see https://dom.spec.whatwg.org/#DOMTokenList-stringification-behavior
     * @see https://www.ecma-international.org/ecma-262/9.0/index.html#sec-tostring
     *
     * @return {string} Token set as string.
     */
    toString(): string;
    /**
     * Returns an iterator for the TokenList, iterating items of the set.
     *
     * @see https://dom.spec.whatwg.org/#domtokenlist
     *
     * @return {IterableIterator<string>} TokenList iterator.
     */
    [Symbol.iterator](): IterableIterator<string>;
    /**
     * Returns the token with index `index`.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-item
     *
     * @param {number} index Index at which to return token.
     *
     * @return {string|undefined} Token at index.
     */
    item(index: number): string | undefined;
    /**
     * Returns true if `token` is present, and false otherwise.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-contains
     *
     * @param {string} item Token to test.
     *
     * @return {boolean} Whether token is present.
     */
    contains(item: string): boolean;
    /**
     * Adds all arguments passed, except those already present.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-add
     *
     * @param {...string} items Items to add.
     */
    add(...items: string[]): void;
    /**
     * Removes arguments passed, if they are present.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-remove
     *
     * @param {...string} items Items to remove.
     */
    remove(...items: string[]): void;
    /**
     * If `force` is not given, "toggles" `token`, removing it if it’s present
     * and adding it if it’s not present. If `force` is true, adds token (same
     * as add()). If force is false, removes token (same as remove()). Returns
     * true if `token` is now present, and false otherwise.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-toggle
     *
     * @param {string}  token   Token to toggle.
     * @param {boolean} [force] Presence to force.
     *
     * @return {boolean} Whether token is present after toggle.
     */
    toggle(token: string, force?: boolean | undefined): boolean;
    /**
     * Replaces `token` with `newToken`. Returns true if `token` was replaced
     * with `newToken`, and false otherwise.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-replace
     *
     * @param {string} token    Token to replace with `newToken`.
     * @param {string} newToken Token to use in place of `token`.
     *
     * @return {boolean} Whether replacement occurred.
     */
    replace(token: string, newToken: string): boolean;
    /**
     * Returns true if `token` is in the associated attribute’s supported
     * tokens. Returns false otherwise.
     *
     * Always returns `true` in this implementation.
     *
     * @see https://dom.spec.whatwg.org/#dom-domtokenlist-supports
     *
     * @return {boolean} Whether token is supported.
     */
    supports(): boolean;
}
//# sourceMappingURL=index.d.ts.map