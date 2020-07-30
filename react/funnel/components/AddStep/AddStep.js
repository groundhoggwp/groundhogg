import React from 'react'
import { AddStepControl } from './AddStepControl/AddStepControl'

import './component.scss'
import { Navbar } from 'react-bootstrap'
import { ExitButton } from '../ExitButton/ExitButton'
import { SlideInBarRight } from '../SlideInBarRight/SlideInBarRight'
import axios from 'axios'
import { reloadEditor } from '../Editor/Editor'
import { disableBodyScrolling, enableBodyScrolling } from '../../App'

const { __, _x, _n, _nx } = wp.i18n

export function showAddStepForm (group, after) {
  // console.debug(group);
  const event = new CustomEvent('groundhogg-add-step',
    { detail: { group: group, after: after } })
  // Dispatch the event.
  document.dispatchEvent(event)
}

export class AddStep extends React.Component {

  constructor (props) {
    super(props)

    this.state = {
      isLoading: false,
      isShowing: false,
      group: 'action',
      after: 0,
      search: '',
    }

    this.handleAddStep = this.handleAddStep.bind(this)
    this.handleExit = this.handleExit.bind(this)
    this.handleStepChosen = this.handleStepChosen.bind(this)
    this.handleSearchChange = this.handleSearchChange.bind(this)
  }

  handleAddStep (e) {
    this.setState({
      isShowing: true,
      stepChosen: false,
      group: e.detail.group,
      after: e.detail.after,
      search: '',
    })
  }

  handleExit () {
    this.setState({
      isShowing: false,
      stepChosen: false,
    })
  }

  handleAdded (result) {
    reloadEditor()
    this.handleExit()
  }

  handleStepChosen (type) {

    this.setState({
      stepChosen: true,
      search: '',
    })

    axios.post(groundhogg_endpoints.steps, {
      funnel_id: ghEditor.funnel.ID,
      after: this.state.after,
      type: type,
    }).then(result => this.handleAdded(result))
  }

  componentDidMount () {
    document.addEventListener('groundhogg-add-step', this.handleAddStep)
  }

  handleSearchChange (e) {
    this.setState({
      search: e.target.value,
    })
  }

  render () {

    const search = this.state.search
    const group = this.state.group
    const regex = new RegExp(search, 'gi')

    const steps = Object.values(ghEditor.stepComponents).
      filter(function (step, i) {
        return step.group === group &&
          ( !search || ( step.name.match(regex) || step.type.match(regex) ) )
      })

    const classes = [
      'add-new-step-choices',
    ]

    if (this.state.stepChosen) {
      classes.push('step-chosen')
    }

    return (
      <div className={ 'add-new-step' }>
        <SlideInBarRight show={ this.state.isShowing }
                         onOverlayClick={ this.handleExit }>
          <div className={ 'inner' }>
            <Navbar bg="white" expand="sm" fixed="top">
              <Navbar.Brand>
                <strong>{ __('Pick a step...', 'groundhogg') }</strong>
              </Navbar.Brand>
              <Navbar.Toggle
                aria-controls="basic-navbar-nav"/>
              <ExitButton onExit={ this.handleExit }/>
            </Navbar>
            <div className={ classes.join(' ') }>
              <input
                placeholder={ _x('Search...', 'add step', 'groundhogg') }
                type={ 'search' } className={ 'search-steps' }
                value={ this.state.search }
                onChange={ this.handleSearchChange }/>
              { steps.map(
                step => <AddStepControl step={ step }
                                        stepChosen={ this.handleStepChosen }/>) }
            </div>
          </div>
        </SlideInBarRight>
      </div>
    )
  }
}