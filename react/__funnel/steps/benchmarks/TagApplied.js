import React from 'react'
import {
  ItemsCommaAndList,
  ItemsCommaOrList,
  TagPicker, TagSpan,
} from '../../components/BasicControls/basicControls'
import { registerStepType, SimpleEditModal } from '../steps'

const { __, _x, _n, _nx } = wp.i18n

registerStepType('tag_applied', {

  icon: ghEditor.steps.tag_applied.icon,
  group: ghEditor.steps.tag_applied.group,
  description: ghEditor.steps.tag_applied.description,
  name: ghEditor.steps.tag_applied.name,
  keywords: [ 'tag', 'tags', 'apply' ],

  title: ({ data, context, settings }) => {

    if (!context || !context.tags_display ||
      !context.tags_display.length) {
      return <>{ 'Select tag requirements...' }</>
    }

    return <>{ 'When' } <ItemsCommaOrList
      separator={ '' }
      use={ settings.condition === 'any' ? __('or', 'groundhogg') : __('and',
        'groundhogg') }
      items={ context.tags_display.map(tag => <TagSpan
        tagName={ tag.label }
      />) }/> { 'are applied' }</>

  },

  edit: ({ data, context, settings, updateSettings, commit, done }) => {

    const tagsChanged = (values) => {
      updateSettings({
        tags: values.map(tag => tag.value),
      }, {
        tags_display: values,
      })
    }

    const conditionChanged = (e) => {
      updateSettings({
        condition: e.target.value,
      })
    }

    const conditions = [
      { value: 'any', label: 'Any' },
      { value: 'all', label: 'All' },
    ]

    return (
      <SimpleEditModal
        title={ 'Tag applied...' }
        done={ done }
        commit={ commit }
      >
        <div><p>{ 'Runs when' } <select
          value={ settings.condition }
          onChange={ conditionChanged }>
          { conditions.map(condition => <option
            value={ condition.value }>{ condition.label }</option>) }
        </select> { 'of the following tags are applied...' }
        </p></div>
        <TagPicker
          id={ 'tags' }
          value={ ( context && context.tags_display ) || false }
          update={ tagsChanged }
        />
        <p
          className={ 'description' }>{ 'Add new tags by hitting [enter] or [tab]' }</p>
      </SimpleEditModal>
    )
  },

})