import { Fragment } from '@wordpress/element'
import { __ } from '@wordpress/i18n'
import { filter, forEach } from 'lodash'
import TabPanel from '../../../core-ui/tab-panel'
import { SettingsSection } from './settings-section'

export const Settings = () => {

	const prepareSections = ( id, sections ) => {
		let tabSections = filter( sections, ( section ) => ( section.tab === id ) );

		tabSections.map( ( section, index ) => (
			tabSections[ index ].settings = filter( window.Groundhogg.preloadSettings.settings, ( setting ) => ( setting.section === section.id ) )
		) );

		return tabSections;
	}

	const tabs = [];

	forEach( window.Groundhogg.preloadSettings.tabs, ( tab ) => {
		let section = prepareSections( tab.id, window.Groundhogg.preloadSettings.sections );
		tabs.push({
			label: tab.title,
			component : () => {
				return (
				  <SettingsSection section={ section } />
				);
			}
		})
	} );

	console.log(tabs);

	return (
		<Fragment>
			<TabPanel tabs={tabs} />
		</Fragment>
	);
};

