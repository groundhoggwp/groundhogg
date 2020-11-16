import { __ } from '@wordpress/i18n'
import { Fragment, useState } from '@wordpress/element'
import { useLocation } from 'react-router-dom'

import { Steps } from 'components/core-ui/steps'
import { Exporting } from './steps/exproting'
import { ExportFields } from './steps/export-fields'
import { Download } from './steps/download'

export const ExportSteps = (props) => {

  // top level props to manage the data
  const [data, setData] = useState({
    fields :[],
    file : {}
  })

  let steps = [
    __('Select Fields', 'goundhogg'),
    __('Build Export File', 'goundhogg'),
    __('Download', 'goundhogg'),
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
        return <ExportFields
          handleNext={handleNext}
          handleBack={handleBack}
          data={data}
          setData={setData}

        />
      case 1:
        return <Exporting
          handleNext={handleNext}
          handleBack={handleBack}
          data={data}
          setData={setData}
        />
      case 2:
        return <Download
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