/**
 * @typedef {( timeOrDeadline: IdleDeadline | number ) => void} Callback
 */
/**
 * @return {(callback: Callback) => void} RequestIdleCallback
 */
export function createRequestIdleCallback(): (callback: (timeOrDeadline: number | IdleDeadline) => void) => void;
declare function _default(callback: (timeOrDeadline: number | IdleDeadline) => void): void;
export default _default;
export type Callback = (timeOrDeadline: number | IdleDeadline) => void;
//# sourceMappingURL=request-idle-callback.d.ts.map