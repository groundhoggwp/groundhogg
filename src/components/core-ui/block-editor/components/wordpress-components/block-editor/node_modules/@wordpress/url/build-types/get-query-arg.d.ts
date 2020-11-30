/**
 * @typedef {{[key: string]: QueryArgParsed}} QueryArgObject
 */
/**
 * @typedef {string|string[]|QueryArgObject} QueryArgParsed
 */
/**
 * Returns a single query argument of the url
 *
 * @param {string} url URL.
 * @param {string} arg Query arg name.
 *
 * @example
 * ```js
 * const foo = getQueryArg( 'https://wordpress.org?foo=bar&bar=baz', 'foo' ); // bar
 * ```
 *
 * @return {QueryArgParsed|undefined} Query arg value.
 */
export function getQueryArg(url: string, arg: string): string | string[] | {
    [key: string]: string | string[] | any;
} | undefined;
export type QueryArgObject = {
    [key: string]: string | string[] | any;
};
export type QueryArgParsed = string | string[] | {
    [key: string]: string | string[] | any;
};
//# sourceMappingURL=get-query-arg.d.ts.map