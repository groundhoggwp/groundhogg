import React, { Fragment } from 'react'
import { Alert, Col, Container, Row } from 'react-bootstrap'
import {
  DayOfMonthPicker,
  DayPicker, DelayAttrIsValid, DisplayTimeDelayFragment,
  Highlight,
  MonthPicker,
  RunAt,
} from './components'
import {
  ClearFix,
  ItemsCommaOrList,
  SimpleSelect, YesNoToggle,
} from '../../../BasicControls/basicControls'
import moment from 'moment'
import { Dashicon } from '../../../Dashicon/Dashicon'
import { ReplacementsButton } from '../../../ReplacementsButton/ReplacementsButton'
import { Tooltip } from '../../../Tooltip/Tooltip'

const { __, _x, _n, _nx } = wp.i18n

export function RenderDelay ({ delay }) {

  if (DelayTypes.hasOwnProperty(delay.type)) {
    return React.createElement(DelayTypes[delay.type].render, {
      delay: delay,
    })
  }

  return <><code>{ 'render()' }</code> { 'method not implemented.' }</>
}

export function DelayIcon ({ type }) {

  let icon = 'editor-help'

  if (DelayTypes.hasOwnProperty(type)) {
    icon = DelayTypes[type].icon
  }

  return <Dashicon icon={ icon }/>
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
      return <>{ _x('Run', 'step delay', 'groundhogg') } <Highlight>{ _x(
        'instantly!', 'step delay', 'groundhogg') }</Highlight></>
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

      if (delay.interval !== 'none') {
        text.push('Wait at least ',
          <Highlight>{ delay.period } { intervalTranslate(delay.interval,
            delay.period) }</Highlight>)
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
          case 'days_of_week':

            text.push(delay.days_of_week_type === 'any'
              ? ' on any '
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
          case 'days_of_month':

            text.push(' on the ')

            text.push(<Highlight><ItemsCommaOrList
              items={ delay.days_of_month.map(
                item => item.label) }/></Highlight>)

            if (delay.months_of_year_type !== 'any') {
              text.push(' of ', <Highlight><ItemsCommaOrList
                items={ delay.months_of_year.map(
                  item => item.label) }/></Highlight>)
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
                  <DelayAttrIsValid
                    isValid={ delay.interval === 'none' || delay.period > 0 }
                    errMsg={ commonErrors.invalidDelayPeriod }
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
                  { delay.run_on === 'days_of_week' && (
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
                  { delay.run_on === 'days_of_month' && (
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
                  <DelayAttrIsValid
                    isValid={ moment(delay.date).isAfter() }
                    errMsg={ commonErrors.invalidDate }
                  />
                </div>
                <div className={ 'run-at col-controls' }>
                  { ['between'].includes(delay.run_on) &&
                  <>
                    <input
                      name={ 'date_to' }
                      min={ delay.date }
                      type={ 'date' }
                      value={ delay.date_to || '' }
                      onChange={ (e) => updateDelay(
                        { date_to: e.target.value }) }
                    />
                    <DelayAttrIsValid
                      isValid={ moment(delay.date_to).isSameOrAfter(delay.date) }
                      errMsg={ commonErrors.invalidDate }
                    />
                  </> }
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
    render: ({ delay }) => {

      const text = [
        'Wait until ',
      ]

      if (delay.interval !== 'none') {
        text.push(
          <Highlight>{ delay.period } { intervalTranslate(delay.interval,
            delay.period) } { translate[delay.wait_type] }</Highlight>,
        )
      }

      text.push(' the ')

      if (delay.use_next_occurrence) {
        text.push('next ')
      }

      text.push(<Highlight>{ delay.replacement }</Highlight>)

      text.push(' then run')

      text.push(<DisplayTimeDelayFragment delay={ delay }/>)

      return <>{ text.map(
        (item, i) => <Fragment key={ i }>{ item }</Fragment>) }</>
    },
    edit: ({ delay, updateDelay }) => {
      return (
        <div className={ 'dynamic-delay' }>
          <Container>
            <Row className={ 'no-padding' }>
              <Col>
                { _x('Wait until...', 'step delay', 'groundhogg') }
                <div className={ 'interval-period wait-type col-controls' }>
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
                  <SimpleSelect
                    name={ 'wait_type' }
                    className={ 'wait_type' }
                    value={ delay.wait_type }
                    disabled={ delay.interval === 'none' }
                    onChange={ (e) => updateDelay(
                      { wait_type: e.target.value }) }
                    options={ waitTypes }
                  />
                  <DelayAttrIsValid
                    isValid={ delay.interval === 'none' || delay.period > 0 }
                    errMsg={ commonErrors.invalidDelayPeriod }
                  />
                </div>
              </Col>
              <Col xs={ 5 }>
                { _x('Replacement Code', 'step delay', 'groundhogg') }
                <Tooltip
                  content={ __(
                    'Insert a replacement code that is expected to return a date. If a non-valid date is given the action will be run instantly.',
                    'groundhogg') }
                  id={ 'delay-replacement-code' }
                  placement={ 'right' }
                />
                <div className={ 'replacement-controls' }>
                  <input
                    id={ 'replacement-dynamic-delay' }
                    name={ 'replacement' }
                    className={ 'replacement w100' }
                    value={ delay.replacement }
                    type={ 'text' }
                    onChange={ (e) => updateDelay(
                      { replacement: e.target.value }) }
                  />
                  <DelayAttrIsValid
                    isValid={ !!delay.replacement }
                    errMsg={ commonErrors.invalidReplacementCode }
                  />
                  <div className={ 'replacements-wrap' }>
                    <ReplacementsButton
                      onInsert={ (v) => updateDelay(
                        { replacement: v }) }
                    />
                    <ClearFix/>
                  </div>
                </div>
                <div className={ 'replacement-controls' }
                     style={ { marginTop: 20 } }>
                  <p>
                    { 'Use next occurrence?' }
                    <Tooltip
                      content={ __(
                        'If enabled, the year from the date will be ignored.',
                        'groundhogg') }
                      id={ 'use-next-occurrence' }
                      placement={ 'right' }
                    />
                    <div className={ 'alignright' }><YesNoToggle
                      value={ delay.use_next_occurrence }
                      update={ (v) => updateDelay(
                        { use_next_occurrence: v }) }
                    /></div>
                  </p>
                </div>
              </Col>
              <Col xs={ 3 }>
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
}
// Todo: Translate the below

const fixedRunOnTypes = [
  { value: 'any', label: _x('Any day', 'step delay', 'groundhogg') },
  { value: 'weekday', label: 'Weekday' },
  { value: 'weekend', label: 'Weekend' },
  { value: 'days_of_week', label: 'Day of week' },
  { value: 'days_of_month', label: 'Day of Month' },
]

const dateRunOnTypes = [
  { value: 'specific', label: 'On a specific date' },
  { value: 'between', label: 'Between' },
]

const runAtTypes = [
  { value: 'any', label: 'Any time' },
  { value: 'specific', label: 'Specific time' },
]

const waitTypes = [
  { value: 'before', label: 'Before' },
  { value: 'after', label: 'After' },
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

const intervalTranslate = (interval, period) => {

  if (!intervalTranslations.hasOwnProperty(interval)) {
    return interval
  }

  return parseInt(period) === 1
    ? intervalTranslations[interval].single
    : intervalTranslations[interval].plural
}

const intervalTranslations = {
  minutes: {
    single: _x('minutes', 'step delay', 'groundhogg'),
    plural: _x('minutes', 'step delay', 'groundhogg'),
  },
  hours: {
    single: _x('hour', 'step delay', 'groundhogg'),
    plural: _x('hours', 'step delay', 'groundhogg'),
  },
  days: {
    single: _x('day', 'step delay', 'groundhogg'),
    plural: _x('days', 'step delay', 'groundhogg'),
  },
  weeks: {
    single: _x('week', 'step delay', 'groundhogg'),
    plural: _x('weeks', 'step delay', 'groundhogg'),
  },
  months: {
    single: _x('month', 'step delay', 'groundhogg'),
    plural: _x('months', 'step delay', 'groundhogg'),
  },
  years: {
    single: _x('year', 'step delay', 'groundhogg'),
    plural: _x('years', 'step delay', 'groundhogg'),
  },
}

const translate = {
  before: _x('before', 'step delay', 'groundhogg'),
  after: _x('after', 'step delay', 'groundhogg'),
}

export const commonErrors = {
  invalidDate: __('Please select a valid date.', 'groundhogg'),
  invalidTime: __('Please select a valid time.', 'groundhogg'),
  invalidReplacementCode: __('Please select a valid replacement code.', 'groundhogg'),
  invalidDelayPeriod: __('The delay period must be at least 1.', 'groundhogg'),
  invalidDaysOfWeek: __('Please select at least one day of the week.',
    'groundhogg'),
  invalidDaysOfMonth: __('Please select at least one day of the month.',
    'groundhogg'),
  invalidMonthsOfYear: __('Please select at least one month of the year.',
    'groundhogg'),
}