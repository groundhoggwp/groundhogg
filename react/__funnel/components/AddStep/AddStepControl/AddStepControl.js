import React from 'react'
import './component.scss'
import Spinner from 'react-bootstrap/Spinner'

export function AddStepControl ({step, isChosen, onSelect}) {

  const classes = [
    'add-step-control',
    'gh-box',
    step.type,
    step.group,
      isChosen ? 'active' : 'inactive',
  ].join(' ')

  return (
    <div className={ classes } onClick={() => onSelect(step.type)}>
      <div className={ 'step-icon-wrap' }>
        <img alt={ step.name } className={ 'step-icon' } src={ step.icon }/>
        { isChosen &&
        <Spinner animation={ 'border' } variant={ 'white' }/> }
      </div>
      <div className={ 'details' }>
        <h3 className={ 'step-name' }>{ step.name }</h3>
        <p className={ 'description' }>{ step.description }</p>
      </div>
    </div>
  )
}