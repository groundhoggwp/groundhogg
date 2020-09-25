import React from 'react';
import {
	ItemsCommaAndList,
	ItemsCommaOrList,
	TagPicker, TagSpan,
} from '../../components/BasicControls/basicControls';
import { registerStepType, SimpleEditModal } from '../steps';

const { __, _x, _n, _nx } = wp.i18n;

registerStepType('tag_removed', {

	icon: ghEditor.steps.tag_removed.icon,
	group: ghEditor.steps.tag_removed.group,

	title: ({ data, context, settings }) => {

		if (!context || !context.tags_display ||
			!context.tags_display.length) {
			return <>{ __('Select tag requirements...', 'groundhogg') }</>;
		}

		return <>{ _x('When', 'tag step title', 'groundhogg') }
			<ItemsCommaOrList
				separator={ '' }
				use={ settings.condition === 'any'
					? __('or', 'groundhogg')
					: __('and', 'groundhogg') }
				items={ context.tags_display.map(tag => <TagSpan
					tagName={ tag.label }
				/>) }/> { _x('are removed', 'tag step title',
				'groundhogg') }</>;
	},

	edit: ({ data, context, settings, updateSettings, commit, done }) => {

		const tagsChanged = (values) => {
			updateSettings({
				tags: values.map(tag => tag.value),
			}, {
				tags_display: values,
			});
		};

		const conditionChanged = (e) => {
			updateSettings({
				condition: e.target.value,
			});
		};

		const conditions = [
			{ value: 'any', label: 'Any' },
			{ value: 'all', label: 'All' },
		];

		return (
			<SimpleEditModal
				title={ __( 'Tag removed...', 'groundhogg' ) }
				done={ done }
				commit={ commit }
			>
				<div><p>{ _x( 'Runs when', 'tag step setting', 'groundhogg' ) } <select
					value={ settings.condition }
					onChange={ conditionChanged }>
					{ conditions.map(condition => <option
						value={ condition.value }>{ condition.label }</option>) }
				</select> { __( 'of the following tags are are removed...' ) }
				</p></div>
				<TagPicker
					id={ 'tags' }
					value={ ( context && context.tags_display ) || false }
					update={ tagsChanged }
				/>
			</SimpleEditModal>
		);
	},

});