import React, { useState } from 'react'
import Modal from 'react-bootstrap/Modal'
import Button from 'react-bootstrap/Button'

import { parseArgs } from '../App'

import './component.scss'

/**
 * Register a new step type through the step type API
 *
 * @param type string
 * @param attributes object
 */
export function registerStepType (type, attributes) {

  attributes = parseArgs(attributes, {
    title: ({ data }) => {
      return data.step_title
    },
  })

  if (ghEditor.steps.hasOwnProperty(type)) {

    attributes = parseArgs(attributes, {
      name: ghEditor.steps[type].name,
      icon: ghEditor.steps[type].icon,
      description: ghEditor.steps[type].description,
      group: ghEditor.steps[type].group,
      type: type,
	    keywords: [],
    })
  }

  if (typeof ghEditor.stepComponents === 'undefined') {
    ghEditor.stepComponents = {}
  }

  ghEditor.stepComponents[type] = attributes
}

function stepTypeExists (type) {
  return typeof ghEditor.stepComponents[type] !== 'undefined'
}

function getStepType (type) {
  return stepTypeExists(type) ? ghEditor.stepComponents[type] : false
}

export function StepTitle ({ type, data, context, settings }) {
  let contr0l

  if (!stepTypeExists(type)) {
    contr0l = <div>{ data.step_title || type }</div>
  }
  else {
    contr0l = React.createElement(getStepType(type).title, {
      data: data,
      settings: settings,
      context: context,
    })
  }

  return contr0l
}

StepTitle.defaultProps = {
  data: {},
  settings: {},
  context: {},
  type: '',
}

export function StepEdit ({ type, data, context, settings, updateSettings, commit, done }) {

  let contr0l

  if (!stepTypeExists(type)) {

    // alert('This step has not been implemented yet...');

    return <></>
  }
  else {
    contr0l = React.createElement(getStepType(type).edit, {
      data: data,
      settings: settings,
      context: context,
      updateSettings: updateSettings,
      commit: commit,
      done: done,
    })
  }

  return contr0l

}

StepTitle.StepEdit = {
  type: '',
  data: {},
  settings: {},
  context: {},
  updateSettings: function () {
  },
  commit: function () {
  },
  done: function () {
  },
}

/**
 *
 * @param title
 * @param done
 * @param commit
 * @param children
 * @param modalProps
 * @param modalBodyProps
 * @param modalFooterProps
 * @param showFooter
 * @returns {*}
 * @constructor
 */
export function SimpleEditModal ({ title, done, commit, children, modalProps, modalBodyProps, modalFooterProps, showFooter }) {

  const [show, setShow] = useState(true)

  const handleSaveAndClose = () => {
    commit()
    setShow(false)
  }

  const handleExited = () => {
    done()
  }

  const handleHide = () => {
    setShow(false)
  }

  return (
    <Modal
      aria-labelledby="contained-modal-title-vcenter"
      className={ 'simple-edit-modal' }
      bsPrefix={ 'groundhogg modal' }
      centered
      show={ show }
      onHide={ handleHide }
      onExited={ handleExited }
      { ...modalProps }
    >
      <Modal.Header closeButton>
        <Modal.Title id="contained-modal-title-vcenter">
          { title }
        </Modal.Title>
      </Modal.Header>
      <Modal.Body { ...modalBodyProps }>
        { children }
      </Modal.Body>
      { showFooter &&
      <Modal.Footer
        { ...modalFooterProps }
      >
        <Button
          onClick={ handleHide }
          variant={ 'secondary' }>{ 'Cancel' }</Button>
        <Button
          onClick={ handleSaveAndClose }
          variant={ 'primary' }>{ 'Save & Close' }</Button>
      </Modal.Footer>
      }
    </Modal>
  )
}

SimpleEditModal.defaultProps = {
  title: '',
  done: function () {
  },
  commit: function () {
  },
  children: [],
  modalProps: {
    size: 'md',
  },
  modalBodyProps: {},
  modalFooterProps: {},
  showFooter: true,
}

import './actions/ApplyTag'
import './actions/RemoveTag'
import './actions/SendEmail'
import './actions/ApplyNote'

import './benchmarks/EmailConfirmed'
import './benchmarks/AccountCreated'
import './benchmarks/TagApplied'
import './benchmarks/TagRemoved'
import './benchmarks/LinkClicked'
import './benchmarks/FormFilled'