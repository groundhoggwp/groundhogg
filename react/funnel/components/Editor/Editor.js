import React from 'react'
import './component.scss'
import Spinner from 'react-bootstrap/Spinner'
import { StepGroup } from '../StepGroup/StepGroup'
import { AddStep } from '../AddStep/AddStep'
import axios from 'axios'
import { EditingWhileActiveWarning } from './EditingWhileActiveWarning'
import { Header } from '../Header/Header'
import { getRequest } from '../../App'

const { __, _x, _n, _nx } = wp.i18n

export class Editor extends React.Component {

  constructor (props) {
    super(props)

    this.state = {
      data: [],
      steps: [],
    }

    // this.handleSetList = this.handleSetList.bind(this);
    this.handleStepsSorted = this.handleStepsSorted.bind(this)
    this.handleReloadEditor = this.handleReloadEditor.bind(this)
  }

  componentDidMount () {

    // document.addEventListener('groundhogg-add-step', this.handleAddStep );
    document.addEventListener('groundhogg-steps-sorted',
      this.handleStepsSorted)
    document.addEventListener('groundhogg-reload-editor',
      this.handleReloadEditor)

    this.setState({
      data: ghEditor.funnel.data,
      steps: ghEditor.funnel.steps,
    })
  }

  handleStepsSorted (e) {

    let id
    let self = this

    const newStepOrder = []

    const steps = jQuery('.step')

    steps.each(function () {
      id = jQuery(this).attr('id')
      newStepOrder.push(self.state.steps.find(step => step.ID == id))
    })

    this.setState({ steps: newStepOrder })

    axios.patch(groundhogg_endpoints.funnels, {
      funnel_id: ghEditor.funnel.ID,
      steps: newStepOrder,
    })
  }

  /**
   * Arbitrary function to handle updates to the funnel
   * like title and status
   *
   * @param newData object
   */
  handleUpdateFunnel (newData) {

    const curData = this.state.data;

    this.setState({
      data: {
        ...curData,
        ...newData
      },
    });

  }

  handleReloadEditor (e) {
    axios.get(getRequest(groundhogg_endpoints.funnels,
      {
        funnel_id: ghEditor.funnel.ID,
      }),
    ).then(result => this.setState({
      steps: result.data.funnel.steps,
      funnel: result.data.funnel,
    }))
  }

  render () {

    const status = this.state.funnel.status

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
          updateFunnel={ }
        />
        <div
          id="groundhogg-funnel-editor"
          className="groundhogg-funnel-editor"
        > {
          status === 'active' && <EditingWhileActiveWarning/>
        }
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

export function reloadEditor () {
  const event = new CustomEvent('groundhogg-reload-editor')
  document.dispatchEvent(event)
}