import { __ } from '@wordpress/i18n'
import { Fragment, useState } from '@wordpress/element'
import { makeStyles } from '@material-ui/core/styles'
import Stepper from '@material-ui/core/Stepper'
import Step from '@material-ui/core/Step'
import StepLabel from '@material-ui/core/StepLabel'
import Typography from '@material-ui/core/Typography'
import { Map } from './steps/map'
import { Import } from './steps/import'
import { Upload } from './steps/upload'
import { useLocation } from 'react-router-dom'
import { Steps } from 'components/core-ui/steps'

export const ImportSteps = (props) => {

  const location = useLocation()

  // top level props to manage the data
  const [data, setData] = useState({
    file: location.file ? location.file : null,
    delimiter: ';',
    map: {},
    tags: [],
    confirm: false
  })

  let steps = [
    __('Upload File', 'goundhogg'),
    __('Map Fields', 'goundhogg'),
    __('Import Contacts', 'goundhogg'),
  ]

  /**
   *
   * @param activeStep
   * @param handleNext
   * @param handleBack
   * @returns {JSX.Element|string}
   */
  const getStepContent = (activeStep, handleNext, handleBack) => {
    switch (activeStep) {
      case 0:
        return <Upload
          handleNext={handleNext}
          handleBack={handleBack}
          data={data}
          setData={setData}

        />
      case 1:
        return <Map
          handleNext={handleNext}
          handleBack={handleBack}
          data={data}
          setData={setData}
        />
      case 2:
        return <Import
          handleNext={handleNext}
          handleBack={handleBack}
          data={data}
          setData={setData}
        />
      default:
        return 'Unknown stepIndex'
    }
  }

  return (
    <Fragment>
      <Steps steps={steps} getStepContent={getStepContent}/>
    </Fragment>

  )

}