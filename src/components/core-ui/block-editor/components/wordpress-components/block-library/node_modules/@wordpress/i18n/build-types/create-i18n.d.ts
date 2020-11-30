export function createI18n(initialData?: Record<string, any> | undefined, initialDomain?: string | undefined): I18n;
export type LocaleData = {
    [x: string]: any;
};
/**
 * An i18n instance
 */
export type I18n = {
    /**
     * Merges locale data into the Tannin instance by domain. Accepts data in a
     * Jed-formatted JSON object shape.
     */
    setLocaleData: Function;
    /**
     * Retrieve the translation of text.
     */
    __: Function;
    /**
     * Retrieve translated string with gettext context.
     */
    _x: Function;
    /**
     * Translates and retrieves the singular or plural form based on the supplied
     * number.
     */
    _n: Function;
    /**
     * Translates and retrieves the singular or plural form based on the supplied
     * number, with gettext context.
     */
    _nx: Function;
    /**
     * Check if current locale is RTL.
     */
    isRTL: Function;
};
//# sourceMappingURL=create-i18n.d.ts.map