import React, { useState } from 'react'
import { EditDelayModal } from './EditDelayControl/EditDelayModal'
import { parseArgs } from '../../../App'
import { DelayIcon, RenderDelay } from './EditDelayControl/delay'

export function DelayControl ({ delay, updateDelay }) {

  const [show, setShow] = useState(false)
  const [tempDelay, setTempDelay] = useState(delay)

  const updateTempDelay = (newTempDelay) => {
    setTempDelay({
      ...tempDelay,
      ...newTempDelay,
    });
  }

  const saveChanges = () => {
    updateDelay(tempDelay)
    setShow(false)
  }

  const cancelChanges = () => {
    setTempDelay(delay)
    setShow(false)
  }

  const mergedDelay = mergeDelayDefaults( tempDelay );
  const displayDelay = mergeDelayDefaults( delay )

  return (
    <div className={ 'delay' }>
      <DelayIcon type={ displayDelay.type }/>
      <span className={ 'delay-text' } onClick={ () => setShow(true) }>
                <RenderDelay delay={ displayDelay }/>
            </span>
      <EditDelayModal
        show={ show }
        delay={ mergedDelay }
        updateDelay={ updateTempDelay }
        save={ saveChanges }
        cancel={ cancelChanges }/>
    </div>
  )

}

function mergeDelayDefaults ( delay ) {
  return parseArgs(delay, {
    period: 0,
    type: 'instant',
    interval: 'none',
    run_on: 'any',
    days_of_week_type: 'any',
    months_of_year_type: 'any',
    days_of_week: [],
    months_of_year: [],
    days_of_month: [],
    wait_type: 'after',
  });
}