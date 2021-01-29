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

  edit: ({ data, meta, update }) => {

    const { fetchItems } = useDispatch(TAGS_STORE_NAME)
    const { tag_ids } = meta
    const [selected, setSelected] = useState([])

    const handleTagsChosen = (e, tags) => {
      setSelected(tags)
    }

    useEffect(() => async () => {

      const result = await fetchItems({
        where: [
          ['id', 'in', tag_ids]
        ]
      })

      setSelected( result.items )

    }, [])

    return <>
      <TagPicker selected={selected} onChange={handleTagsChosen}/>
    </>
  }
}

registerStepType(STEP_TYPE, stepAtts)