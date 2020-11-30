export default Icon;
export type IconProps = {
    icon: JSX.Element;
    size?: number | undefined;
};
/** @typedef {{icon: JSX.Element, size?: number} & import('react').ComponentPropsWithoutRef<'SVG'>} IconProps */
/**
 * Return an SVG icon.
 *
 * @param {IconProps} props icon is the SVG component to render
 *                          size is a number specifiying the icon size in pixels
 *                          Other props will be passed to wrapped SVG component
 *
 * @return {JSX.Element}  Icon component
 */
declare function Icon({ icon, size, ...props }: {
    icon: JSX.Element;
    size?: number | undefined;
}): JSX.Element;
//# sourceMappingURL=index.d.ts.map