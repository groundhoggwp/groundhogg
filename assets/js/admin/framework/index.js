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
	return wp.hooks.addFilter(
		'groundhogg.listTable.customColumns',
		lodash.uniqueId( `${table}_${data.header.label}_` ),
		( tableData ) => ( { ...tableData, data } )
	);
}

/**
 * Register a navigation menu item in the top navigation bar.
 *
 * @todo Improve inline data docs
 *
 * @param {*} navItem
 */
export const registerNavItem = ( navItem ) => {
	return wp.hooks.addFilter(
		'groundhogg.navigation',
		lodash.uniqueId( `${navItem.name}_` ),
		( navItems ) => ( { ...navItems, navItem } ),
		navItem.priority || 10
	);
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
	return wp.hooks.addFilter(
		'groundhogg.settings.settings',
		lodash.uniqueId( `${settingObject.id}_` ),
		( settings ) => ( { ...settings, settingObject } ),
		settingObject.priority || 10
	);
}

/**
 * Register a settings panel in a settings panel.
 *
 * @todo Likely add additional functional logic once Dashboard and Settings page components are finalized.
 * @todo Improve inline data docs
 *
 * @param {*} settingsPanel
 */
export const registerSettingsSection = ( settingsSection ) => {
	return wp.hooks.addFilter(
		'groundhogg.settings.section',
		lodash.uniqueId( `${settingsSection.id}_` ),
		( sections ) => ( { ...sections, settingsSection } ),
		settingsSection.priority || 10
	);
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
	return wp.hooks.addFilter(
		'groundhogg.settings.tabs',
		lodash.uniqueId( `${settingsPanel.id}_` ),
		( panels ) => ( { ...panels, settingsPanel } ),
		settingsPanel.priority || 10
	);
}