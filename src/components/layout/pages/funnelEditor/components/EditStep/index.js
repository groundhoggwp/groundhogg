import { useTempState } from 'utils/index'
import Typography from '@material-ui/core/Typography'
import { Button } from '@material-ui/core'
import { useEffect } from '@wordpress/element'
import { objEquals } from 'utils/core'
import { makeStyles } from '@material-ui/core/styles'
import { useCurrentStep } from 'components/layout/pages/funnelEditor/utils/hooks'
import SettingsRow from '../SettingsRow'

const useStyles = makeStyles((theme) => ({
  actions : {
    float: 'right'
  },
  saveButtons : {
    '& .MuiButton-root:first-child' :{
      marginRight: theme.spacing(2)
    }
  }
}))

export default () => {

  const classes = useStyles()

  const { step, funnelId, stepId, isUpdating, StepType, updateStep, deleteStep } = useCurrentStep()
  const { tempState, setTempState, resetTempState } = useTempState(step.meta)

  const commitChanges = () => {
    updateStep(funnelId, stepId, {
      meta: {
        ...tempState
      }
    })
  }

  // Reset the meta once the step is updated.
  useEffect(() => {
    setTempState(step.meta)
  }, [isUpdating])

  // Cleanup after unmount
  useEffect(() => {
    return () => {
      resetTempState()
    }
  }, [])

  return (
    <>
      <Typography variant={'h1'}>{StepType.name}</Typography>
      <div className={'edit-panel'}>
        <StepType.edit data={step.data} settings={tempState} updateSettings={setTempState}/>
        {!objEquals(tempState, step.meta) &&
        <SettingsRow>
          <div className={classes.saveButtons}>
            <Button onClick={commitChanges} variant={'contained'} color={'primary'}>{'Save Changes'}</Button>
            <Button onClick={resetTempState} variant={'contained'}>{'Cancel'}</Button>
          </div>
        </SettingsRow>
        }
      </div>
    </>
  )

}