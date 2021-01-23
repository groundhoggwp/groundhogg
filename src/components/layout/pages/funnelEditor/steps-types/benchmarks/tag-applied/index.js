import LocalOfferIcon from '@material-ui/icons/LocalOffer'
import { BENCHMARK, BENCHMARK_TYPE_DEFAULTS } from '../../constants'
import { registerStepType } from 'data/step-type-registry'
import TagPicker from 'components/core-ui/tag-picker'
import { useDispatch } from '@wordpress/data'
import { TAGS_STORE_NAME } from 'data/tags'
import { useEffect, useState } from '@wordpress/element'

const STEP_TYPE = 'tag_applied'

const stepAtts = {

  ...BENCHMARK_TYPE_DEFAULTS,

  type: STEP_TYPE,

  group: BENCHMARK,

  name: 'Tag Applied',

  icon: <LocalOfferIcon/>,

  view: ({ data, meta, stats }) => {
    return <>{'When tags are applied!'}</>
  },

  edit: ({ data, settings, updateSettings }) => {

    const { tag_ids } = settings

    const handleTagsChosen = (e, tags) => {

      updateSettings({
        ...settings,
        tag_ids: tags.map(tag => tag.ID)
      })
    }

    return <>
      <TagPicker selected={tag_ids || []} onChange={handleTagsChosen}/>
    </>
  }
}

registerStepType(STEP_TYPE, stepAtts)