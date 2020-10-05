/**
 * Add custom column to table.
 *
 * @todo Likely add additional functional logic once ListTable component is finalized.
 * @todo Improve inline data docs
 *
 * @param {string} table Name of table (contacts, tags, etc.)
 * @param {object} data Object containing header and column data. Column is object containing value and display properties.
 */
export const addTableColumn = ( table, data ) => {
	return wp.hooks.addFilter( 'groundhogg_custom_columns', lodash.uniqueId( `${table}_${data.header.label}_` ), data );
}

/**
 * Register a navigation menu item in the top navigation bar.
 *
 * @todo Likely add additional functional logic once TopBar/Dashboard component is finalized.
 * @todo Improve inline data docs
 *
 * @param {*} navItem
 */
export const registerNavItem = ( navItem ) => {
	return wp.hooks.addFilter( 'groundhogg_custom_columns', lodash.uniqueId( `${navItem.name}_` ), navItem, navItem.priority );
}

/**
 * Register a settings object in a settings panel.
 *
 * @todo Likely add additional functional logic once Dashboard and Settings page components are finalized.
 * @todo Improve inline data docs
 *
 * @param {*} settingObject
 */
export const registerSetting = ( settingObject ) => {
	return wp.hooks.addFilter( 'groundhogg_settings', lodash.uniqueId( `${settingObject.name}_` ), settingObject, settingObject.priority );
}
/**
 * Register a settings panel in a settings panel.
 *
 * @todo Likely add additional functional logic once Dashboard and Settings page components are finalized.
 * @todo Improve inline data docs
 *
 * @param {*} settingsPanel
 */
export const registerSettingsPanel = ( settingsPanel ) => {
	return wp.hooks.addFilter( 'groundhogg_settings_panels', lodash.uniqueId( `${settingsPanel.name}_` ), settingsPanel, settingsPanel.priority );
}