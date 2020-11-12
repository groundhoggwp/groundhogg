import { __ } from '@wordpress/i18n'
import { Fragment, useState } from '@wordpress/element'
import { makeStyles } from '@material-ui/core/styles'
import Stepper from '@material-ui/core/Stepper'
import StepLabel from '@material-ui/core/StepLabel'
import Typography from '@material-ui/core/Typography'
import { useLocation } from 'react-router-dom'
import { STEPS_STORE_NAME } from 'data/steps'

import { Steps } from 'components/core-ui/steps'

export const ExportSteps = (props) => {
  //
  // // top level props to manage the data
  // const [data, setData] = useState({
  //   file: location.file ? location.file : null,
  //   delimiter: ';',
  //   map: {},
  //   tags: [],
  //   confirm: false
  // })
  //
  // let steps = [
  //   __('Select Fields', 'goundhogg'),
  //   __('Build Export File', 'goundhogg'),
  //   __('Download', 'goundhogg'),
  // ]
  //
  // /**
  //  *
  //  * @param activeStep
  //  * @param handleNext
  //  * @param handleBack
  //  * @returns {JSX.Element|string}
  //  */
  // const getStepContent = (activeStep, handleNext, handleBack) => {
  //   switch (activeStep) {
  //     case 0:
  //       return <Upload
  //         handleNext={handleNext}
  //         handleBack={handleBack}
  //         data={data}
  //         setData={setData}
  //
  //       />
  //     case 1:
  //       return <Map
  //         handleNext={handleNext}
  //         handleBack={handleBack}
  //         data={data}
  //         setData={setData}
  //       />
  //     case 2:
  //       return <Import
  //         handleNext={handleNext}
  //         handleBack={handleBack}
  //         data={data}
  //         setData={setData}
  //       />
  //     default:
  //       return 'Unknown stepIndex'
  //   }
  // }
  //
  // return (
  //   <Fragment>
  //     <Steps steps={steps} getStepContent={getStepContent}/>
  //   </Fragment>
  //
  // )

}