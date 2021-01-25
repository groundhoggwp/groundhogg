import React from 'react'
import './component.scss'
import { StepGroup } from '../StepGroup/StepGroup'
import { AddStep } from '../AddStep/AddStep'
import axios from 'axios'
import { EditingWhileActiveWarning } from './EditingWhileActiveWarning'
import { Header } from '../Header/Header'
import { getRequest, objEquals, parseArgs } from '../../App'
import { FadeIn } from '../Animations/Animations'
import { ProgressBar } from 'react-bootstrap'

const { __, _x, _n, _nx } = wp.i18n

export class Editor extends React.Component {

  constructor (props) {
    super(props)

    this.state = {
      data: ghEditor.funnel.data,
      steps: ghEditor.funnel.steps,
      validationErrors: null,
      saving: false,
    }

    // this.handleSetList = this.handleSetList.bind(this);
    this.handleStepsSorted = this.handleStepsSorted.bind(this)
    this.handleReloadEditor = this.handleReloadEditor.bind(this)
    this.handleUpdateFunnel = this.handleUpdateFunnel.bind(this)
  }

  componentDidMount () {

    // document.addEventListener('groundhogg-add-step', this.handleAddStep );
    document.addEventListener('groundhogg-steps-sorted',
      this.handleStepsSorted)
    document.addEventListener('groundhogg-reload-editor',
      this.handleReloadEditor)
  }

  handleStepsSorted (e) {

    let id
    let self = this

    const newStepOrder = []

    var steps = document.querySelectorAll('.step');

    Array.prototype.forEach.call(steps, function(el, i){
      id = el.getAttribute('id');
      newStepOrder.push(self.state.steps.find(step => step.ID == id))
    });

    this.setState({
      steps: newStepOrder,
      saving: true,
    })

    axios.patch(groundhogg_endpoints.funnels, {
      funnel_id: ghEditor.funnel.ID,
      steps: newStepOrder,
    }).then(result => this.setState({
      saving: false,
    }))
  }

  /**
   * Arbitrary function to handle updates to the funnel
   * like title and status
   *
   * @param newData object
   */
  handleUpdateFunnel (newData) {

    const curData = this.state.data
    const updatedData = parseArgs(newData, curData)

    // No need to update if nothing changed
    if (objEquals(curData, updatedData)) {
      return
    }

    if (typeof newData.status !== 'undefined' && newData.status === 'active') {
      // todo, run validation and activation script.
      console.log('Activating funnel!')

      this.setState({
        saving: true,
      })

      axios.patch(groundhogg_endpoints.funnels + '/activate/', {
        funnel_id: ghEditor.funnel.ID,
      }).then(result => this.setState({
        saving: false,
        validationErrors: result.data.errors,
        data: result.data.funnel.data,
      }))

      return
    }

    this.setState({
      data: {
        ...curData,
        ...newData,
      },
      saving: true,
    })

    axios.patch(groundhogg_endpoints.funnels, {
      funnel_id: ghEditor.funnel.ID,
      data: newData,
    }).then(result => this.setState({
      data: result.data.funnel.data,
      saving: false,
    }))

  }

  /**
   * Reloads the funnel state and steps from the API
   * Useful when making arbitrary changes to the steps API
   *
   * @param e
   */
  handleReloadEditor (e) {

    axios.get(getRequest(groundhogg_endpoints.funnels,
      {
        funnel_id: ghEditor.funnel.ID,
      }),
    ).then(result => this.setState({
      steps: result.data.funnel.steps,
      funnel: result.data.funnel,
    })).then(e.detail.callback)
  }

  render () {

    const status = this.state.data.status

    const rawGroups = reduceStepsToGroups(this.state.steps)
    const groups = rawGroups.map((group, i) => <StepGroup
      key={ i }
      steps={ group }
      isFirst={ i === 0 }
      isLast={ i === rawGroups.length - 1 }
    />)

    return (
      <>
        <Header
          updateFunnel={ this.handleUpdateFunnel }
          data={ this.state.data }
          isSaving={ this.state.saving }
        />
        {
          status === 'active' && <FadeIn>
            <EditingWhileActiveWarning/>
          </FadeIn>
        }
        <div
          id="groundhogg-funnel-editor"
          className="groundhogg-funnel-editor"
        >
          <div className={ 'step-groups' }>
            { groups }
          </div>
          <div className={ 'editor-controls' }>
            <AddStep/>
          </div>
        </div>
      </>
    )

  }

}

/**
 * Reduce the given steps to sorted groups
 *
 * @param steps
 * @returns {*}
 */
function reduceStepsToGroups (steps) {
  return steps.reduce(function (prev, curr) {

    // console.debug(prev, curr);

    if (prev.length && curr.data.step_group ===
      prev[prev.length - 1][0].data.step_group) {
      prev[prev.length - 1].push(curr)
    }
    else {
      prev.push([curr])
    }
    return prev
  }, [])
}

/**
 * Reloads the editor steps with the most updated information.
 * Kind of hacky but not sure what other methods to deploy
 * Will do better in the future.
 *
 * @param callback a callback function
 */
export function reloadEditor (callback) {
  const event = new CustomEvent('groundhogg-reload-editor', {
    detail: {
      callback: callback,
    },
  })

  document.dispatchEvent(event)
}