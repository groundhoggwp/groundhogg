import React, { useEffect, useState } from 'react'
import {
  FadeIn,
  FadeOut,
  SlideInRight,
  SlideOutRight,
} from '../Animations/Animations'

import './component.scss'
import { disableBodyScrolling, enableBodyScrolling } from '../../App'

export function SlideInBarRight ({ show, children, onOverlayClick }) {

  const [isShowing, setIsShowing] = useState(false)

  // Similar to componentDidMount and componentDidUpdate:
  useEffect(() => {
    // Update the document title using the browser API
    if (show) {
      setIsShowing(true)
      disableBodyScrolling()
    }
    else {
      enableBodyScrolling()
    }
  })

  const handleAfterSlideOut = () => {
    setIsShowing(false)
  }

  if (!isShowing) {
    return <></>
  }

  else if (!show) {
    return (
      <div className={ 'slide-in-bar-right' }>
        <FadeOut>
          <div
            className={ ['modal-backdrop', 'show' ].join(
              ' ') }></div>
        </FadeOut>
        <div className={ 'container' }>
          <SlideOutRight
            then={ handleAfterSlideOut }
          >
            <div className={ 'content' }
            >
              { children }
            </div>
          </SlideOutRight>
        </div>
      </div>
    )
  }
  else {

    return (
      <div className={ 'slide-in-bar-right' }>
        <FadeIn>
          <div
            className={ ['modal-backdrop', 'show' ].join(
              ' ') } onClick={ onOverlayClick }></div>
        </FadeIn>
        <div className={ 'container' }>
          <SlideInRight>
            <div className={ 'content' }
            >
              { children }
            </div>
          </SlideInRight>
        </div>
      </div>
    )
  }
}