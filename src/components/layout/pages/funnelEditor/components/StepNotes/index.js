import { useTempState } from 'utils/index'
import Typography from '@material-ui/core/Typography'
import { Button } from '@material-ui/core'
import { useEffect } from '@wordpress/element'
import { objEquals } from 'utils/core'
import TextField from '@material-ui/core/TextField'
import Fab from '@material-ui/core/Fab'
import SettingsIcon from '@material-ui/icons/Settings';
import { useCurrentStep } from 'components/layout/pages/funnelEditor/utils/hooks'

export default () => {

  const { step, funnelId, stepId, isUpdating, StepType, updateStep, deleteStep } = useCurrentStep()
  const { tempState, setTempState, resetTempState } = useTempState(step.meta)

  const commitChanges = () => {
    updateStep(funnelId, stepId, {
      meta: {
        ...tempState
      }
    })
  }

  useEffect(() => {
    setTempState(step.meta)
  }, [isUpdating])

  return (
    <>
      <Fab>
        <SettingsIcon/>
      </Fab>
      <Typography variant={'h1'}>Notes for {StepType.name}</Typography>
      <div className={'notes-panel'}>
        <TextField
          label="Step Notes"
          multiline
          rows={4}
          fullWidth
          variant="outlined"
          onChange={(e) => setTempState({
            ...tempState,
            notes: e.target.value
          })}
          value={step.meta.notes}
        />
        {!objEquals(tempState, step.meta) &&
        <>
          <Button onClick={commitChanges} variant={'contained'} color={'primary'}>{'Save Changes'}</Button>
          <Button onClick={resetTempState} variant={'contained'}>{'Cancel'}</Button>
        </>
        }
      </div>
    </>
  )

}