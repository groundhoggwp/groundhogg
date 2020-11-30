/**
 * Component which merges passed value with current consumed block context.
 *
 * @see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/block-context/README.md
 *
 * @param {BlockContextProviderProps} props
 */
export function BlockContextProvider({ value, children }: BlockContextProviderProps): JSX.Element;
export default Context;
export type ReactNode = string | number | boolean | {} | import("react").ReactElement<any, string | ((props: any) => import("react").ReactElement<any, string | any | (new (props: any) => import("react").Component<any, any, any>)> | null) | (new (props: any) => import("react").Component<any, any, any>)> | import("react").ReactNodeArray | import("react").ReactPortal | null | undefined;
export type BlockContextProviderProps = {
    /**
     * Context value to merge with current
     * value.
     */
    value: Record<string, any>;
    /**
     * Component children.
     */
    children: import("react").ReactNode;
};
/** @typedef {import('react').ReactNode} ReactNode */
/**
 * @typedef BlockContextProviderProps
 *
 * @property {Record<string,*>} value    Context value to merge with current
 *                                       value.
 * @property {ReactNode}        children Component children.
 */
/** @type {import('react').Context<Record<string,*>>} */
declare const Context: import('react').Context<Record<string, any>>;
//# sourceMappingURL=index.d.ts.map