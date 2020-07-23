import React, { Fragment } from 'react'
import { GroundhoggModal } from '../../../Modal/Modal'

import './component.scss'
import { Dashicon } from '../../../Dashicon/Dashicon'
import { DelayTypes, EditDelay, RenderDelay } from './delay'
import { Button, ButtonGroup } from 'react-bootstrap'

export function EditDelayModal ({ show, delay, updateDelay, save, cancel }) {

  const delayTypes = Object.values(DelayTypes);
  const chosen = delay.type;

  return (
    <GroundhoggModal
      show={ show }
      heading={ 'Edit step delay' }
      onSave={ save }
      onHide={ cancel }
      closeText={ 'Save' }
    >
      <div className={ 'edit-delay-controls' }>
        <div className={ 'delay-text' }>
          <Dashicon icon={ 'clock' }/> <RenderDelay
          delay={ delay }
        />
        </div>
        <div className={ 'delay-type' }>
          <ButtonGroup aria-label="delay-types">
            { delayTypes.map((delayType) =>
              <Button
                key={delayType.type}
                variant={ chosen === delayType.type ? 'primary' : 'outline-primary' }
                onClick={(e) => updateDelay({type: delayType.type}) }
              >
                {delayType.name}
              </Button>) }
          </ButtonGroup>
        </div>
        <div className={ 'edit-delay-controls-inner' }>
          <EditDelay delay={ delay } updateDelay={ updateDelay }/>
        </div>
      </div>
    </GroundhoggModal>
  )
}