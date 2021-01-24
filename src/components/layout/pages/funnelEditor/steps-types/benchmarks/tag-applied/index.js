import LocalOfferIcon from '@material-ui/icons/LocalOffer'
import { BENCHMARK, BENCHMARK_TYPE_DEFAULTS } from '../../constants'
import { registerStepType } from 'data/step-type-registry'
import TagPicker from 'components/core-ui/tag-picker'
import { useDispatch } from '@wordpress/data'
import { TAGS_STORE_NAME } from 'data/tags'
import { useEffect, useState } from '@wordpress/element'
import { makeStyles } from '@material-ui/core/styles'
import Box from '@material-ui/core/Box'

const STEP_TYPE = 'tag_applied'

const useStyles = makeStyles((theme) => ({
  root: {
    marginTop: theme.spacing(2),
    marginBottom: theme.spacing(1),
  }
}))

const SettingsRow = ({children}) => {

  const classes = useStyles()

  return (<>
    <Box className={classes.root}>
      {children}
    </Box>
  </>)
}

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

    const handleTagsChosen = (tagIds) => {

      updateSettings({
        ...settings,
        tag_ids: tagIds
      })
    }

    return <>
      <SettingsRow>
        <TagPicker selected={tag_ids || []} onChange={handleTagsChosen}/>
      </SettingsRow>
      <SettingsRow>
      </SettingsRow>
    </>
  }
}

registerStepType(STEP_TYPE, stepAtts)