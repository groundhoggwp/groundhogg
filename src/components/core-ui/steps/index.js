import { __ } from '@wordpress/i18n'
import { Fragment, useState } from '@wordpress/element'
import { makeStyles } from '@material-ui/core/styles'
import Stepper from '@material-ui/core/Stepper'
import Step from '@material-ui/core/Step'
import StepLabel from '@material-ui/core/StepLabel'
import Typography from '@material-ui/core/Typography'
import { useLocation } from 'react-router-dom'

const useStyles = makeStyles((theme) => ({
  root: {
    width: '100%',
  },
  backButton: {
    marginRight: theme.spacing(1),
  },
  instructions: {
    marginTop: theme.spacing(1),
    marginBottom: theme.spacing(1),
  },
}))


export const Steps = (props) => {

  const classes = useStyles();

  // used to get file details send from the table
  const {steps ,getStepContent } = props
  const [activeStep, setActiveStep] = useState(0)


  const handleNext = () => {
    setActiveStep((prevActiveStep) => prevActiveStep + 1)
  }

  const handleBack = () => {
    setActiveStep((prevActiveStep) => prevActiveStep - 1)
  }

  return (
    <div className={classes.root}>
      <Stepper activeStep={activeStep} alternativeLabel>
        {steps.map((label) => (
          <Step key={label}>
            <StepLabel>{label}</StepLabel>
          </Step>
        ))}
      </Stepper>
      <div>
        {activeStep === steps.length ? (
          <div>
            <Typography className={classes.instructions}>All steps completed</Typography>
          </div>
        ) : (
          <div>
            <Typography
              className={classes.instructions}>{
              getStepContent(activeStep, handleNext, handleBack)
            }
            </Typography>
          </div>
        )}
      </div>
    </div>
  )

}