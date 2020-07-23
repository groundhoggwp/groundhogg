import React, { Fragment } from 'react'
import { Col, Container, Row } from 'react-bootstrap'
import {
  DayOfMonthPicker,
  DayPicker, DisplayTimeDelayFragment,
  Highlight,
  MonthPicker,
  RunAt,
} from './components'
import {
  ItemsCommaOrList,
  SimpleSelect,
} from '../../../BasicControls/basicControls'
import moment from 'moment'
import { parseArgs } from '../../../../App'

const { __, _x, _n, _nx } = wp.i18n

export function RenderDelay ({ delay }) {

  if (DelayTypes.hasOwnProperty(delay.type)) {
    return React.createElement(DelayTypes[delay.type].render, {
      delay: delay,
    })
  }

  return <><code>{ 'render()' }</code> { 'method not implemented.' }</>
}

export function EditDelay ({ delay, updateDelay }) {

  if (DelayTypes.hasOwnProperty(delay.type)) {
    return React.createElement(DelayTypes[delay.type].edit, {
      delay: delay,
      updateDelay: updateDelay,
    })
  }

  return <><code>{ 'edit()' }</code> { 'method not implemented.' }</>
}

export const DelayTypes = {
  instant: {
    type: 'instant',
    name: _x('Instant', 'step delay', 'groundhogg'),
    icon: 'marker',
    render: ({ delay }) => {
      return <>{ _x('Run instantly', 'step delay', 'groundhogg') }</>
    },
    edit: () => {
      return <></>
    },
  },
  fixed: {
    type: 'fixed',
    name: _x('Fixed Delay', 'step delay', 'groundhogg'),
    icon: 'clock',
    render: ({ delay }) => {
      const text = []

      delay = parseArgs(delay, {
        period: 0,
        interval: 'none',
        run_on: 'any',
        days_of_week_type: 'any',
        months_of_year_type: 'any',
        days_of_week: [],
        months_of_year: [],
      })

      if (delay.interval !== 'none') {
        text.push('Wait at least ',
          <Highlight>{ delay.period } { delay.interval }</Highlight>)
      }

      if (delay.run_on !== 'any') {

        if (!text.length) {
          text.push('Run')
        }
        else {
          text.push(' then run')
        }

        switch (delay.run_on) {
          case 'weekday':
            text.push(' on a ', <Highlight>{ 'weekday' }</Highlight>)
            break
          case 'weekend':
            text.push(' on a ', <Highlight>{ 'weekend' }</Highlight>)
            break
          case 'day_of_week':

            text.push(delay.days_of_week_type === 'any'
              ? ' on '
              : ( <>{ ' on the ' }
                <Highlight>{ delay.days_of_week_type }</Highlight> </> ),
            )

            const days = delay.days_of_week

            text.push(<Highlight><ItemsCommaOrList
              items={ delay.days_of_week.map(item => item.label) }
            /></Highlight>)

            if (delay.months_of_year_type !== 'any') {
              text.push(' of ', <Highlight><ItemsCommaOrList
                items={ delay.months_of_year.map(item => item.label) }
              /></Highlight>)
            }

            break
          case 'day_of_month':

            text.push(' on the ')

            text.push(<ItemsCommaOrList
              items={ delay.days_of_month.map(item => item.label) }/>)

            if (delay.months_of_year_type !== 'any') {
              text.push(' of ', <ItemsCommaOrList
                items={ delay.months_of_year.map(item => item.label) }/>)
            }

            break
        }

      }

      if (!text.length) {
        text.push('Run')
      }
      else if (delay.run_on === 'any') {
        text.push(' then run')
      }

      text.push(<DisplayTimeDelayFragment delay={ delay }/>)

      return <>{ text.map(
        (item, i) => <Fragment key={ i }>{ item }</Fragment>) }</>
    },
    edit: ({ delay, updateDelay }) => {

      return (
        <div className={ 'fixed-delay' }>
          <Container>
            <Row className={ 'no-padding' }>
              <Col>
                { _x('Wait at least...', 'step delay', 'groundhogg') }
                <div className={ 'interval-period col-controls' }>
                  <input
                    name={ 'period' }
                    className={ 'period' }
                    value={ delay.period }
                    min={ 1 }
                    type={ 'number' }
                    disabled={ delay.interval === 'none' }
                    onChange={ (e) => updateDelay(
                      { period: e.target.value }) }
                  />
                  <SimpleSelect
                    name={ 'interval' }
                    className={ 'interval' }
                    value={ delay.interval }
                    onChange={ (e) => updateDelay(
                      { interval: e.target.value }) }
                    options={ intervalTypes }
                  />
                </div>
              </Col>
              <Col xs={ 5 }>
                { _x('Run on ', 'step delay', 'groundhogg') }
                <SimpleSelect
                  name={ 'run_on' }
                  className={ 'run-on' }
                  value={ delay.run_on }
                  onChange={ (e) => updateDelay({ run_on: e.target.value }) }
                  options={ fixedRunOnTypes }
                />
                <div className={ 'col-controls run-on' }>
                  { delay.run_on === 'day_of_week' && (
                    <>

                      <DayPicker
                        days={ delay.days_of_week }
                        type={ delay.days_of_week_type }
                        updateDelay={ updateDelay }
                      />
                      <MonthPicker
                        type={ delay.months_of_year_type }
                        months={ delay.months_of_year }
                        updateDelay={ updateDelay }
                      />
                    </>
                  ) }
                  { delay.run_on === 'day_of_month' && (
                    <>

                      <DayOfMonthPicker
                        days={ delay.days_of_month }
                        updateDelay={ updateDelay }
                      />
                      <MonthPicker
                        type={ delay.months_of_year_type }
                        months={ delay.months_of_year }
                        updateDelay={ updateDelay }
                      />
                    </>
                  ) }
                </div>
              </Col>
              <Col>
                <RunAt
                  time={ delay.time }
                  timeTo={ delay.time_to }
                  type={ delay.run_at }
                  updateDelay={ updateDelay }
                />
              </Col>
            </Row>
          </Container>
        </div>
      )
    },
  },
  date: {
    type: 'date',
    name: _x('Date Delay', 'step delay', 'groundhogg'),
    icon: 'calendar-alt',
    render: ({ delay }) => {
      const text = []

      switch (delay.run_on) {
        default:
        case 'specific':
          text.push('Run on ', <Highlight>{ moment(delay.date, 'YYYY-MM-DD').
            format('LL') }</Highlight>)
          break
        case 'between':
          text.push('Run between ', <Highlight>{ moment(delay.date,
            'YYYY-MM-DD').
            format('LL') }</Highlight>, ' and ', <Highlight>{ moment(
            delay.date_to,
            'YYYY-MM-DD').format('LL') }</Highlight>)
          break
      }

      text.push(<DisplayTimeDelayFragment delay={ delay }/>)

      return <>{ text.map(
        (item, i) => <Fragment key={ i }>{ item }</Fragment>) }</>
    },
    edit: ({ delay, updateDelay }) => {

      return (
        <div className={ 'date-delay' }>
          <Container>
            <Row className={ 'no-padding' }>
              <Col>
                { 'Run ' }
                <SimpleSelect
                  name={ 'run_on' }
                  className={ 'run-on' }
                  value={ delay.run_on }
                  onChange={ (e) => updateDelay({
                    run_on:
                    e.target.value,
                  }) }
                  options={ dateRunOnTypes }
                />
                <div className={ 'run-at col-controls' }>
                  <input
                    min={ moment().format('YYYY-MM-DD') }
                    name={ 'date' }
                    type={ 'date' }
                    value={ delay.date || '' }
                    onChange={ (e) => updateDelay({ date: e.target.value }) }
                  />
                </div>
                <div className={ 'run-at col-controls' }>
                  { ['between'].includes(delay.run_on) &&
                  <input
                    name={ 'date_to' }
                    min={ delay.date }
                    type={ 'date' }
                    value={ delay.date_to || '' }
                    onChange={ (e) => updateDelay({ date_to: e.target.value }) }
                  /> }
                </div>
              </Col>
              <Col>
                <RunAt
                  time={ delay.time }
                  timeTo={ delay.time_to }
                  type={ delay.run_at }
                  updateDelay={ updateDelay }
                />
              </Col>
            </Row>
          </Container>
        </div>
      )
    },
  },
  dynamic: {
    type: 'dynamic',
    name: _x('Dynamic Delay', 'step delay', 'groundhogg'),
    icon: 'groups',
    edit: () => {
      return <></>
    },
    render: () => {
      return <></>
    },
  },
}

// Todo: Translate the below

const fixedRunOnTypes = [
  { value: 'any', label: _x('Any day', 'step delay', 'groundhogg') },
  { value: 'weekday', label: 'Weekday' },
  { value: 'weekend', label: 'Weekend' },
  { value: 'day_of_week', label: 'Day of week' },
  { value: 'day_of_month', label: 'Day of Month' },
]

const dateRunOnTypes = [
  { value: 'specific', label: 'On a specific date' },
  { value: 'between', label: 'Between' },
]

const runAtTypes = [
  { value: 'any', label: 'Any time' },
  { value: 'specific', label: 'Specific time' },
]

const intervalTypes = [
  { value: 'minutes', label: 'Minutes' },
  { value: 'hours', label: 'Hours' },
  { value: 'days', label: 'Days' },
  { value: 'weeks', label: 'Weeks' },
  { value: 'months', label: 'Months' },
  { value: 'years', label: 'Years' },
  { value: 'none', label: 'No delay' },
]
