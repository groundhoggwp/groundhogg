import React, { Fragment } from 'react'
import Select from 'react-select'
import { SimpleSelect } from '../../../BasicControls/basicControls'
import moment from 'moment'

// Todo: translate
const { __, _x, _n, _nx } = wp.i18n;

const daysOfWeek = [
  { value: 'monday', label: 'Monday' },
  { value: 'tuesday', label: 'Tuesday' },
  { value: 'wednesday', label: 'Wednesday' },
  { value: 'thursday', label: 'Thursday' },
  { value: 'friday', label: 'Friday' },
  { value: 'saturday', label: 'Saturday' },
  { value: 'sunday', label: 'Sunday' },
]

const daysOfWeekTypes = [
  { value: 'any', label: 'Any' },
  { value: 'first', label: 'First' },
  { value: 'second', label: 'Second' },
  { value: 'third', label: 'Third' },
  { value: 'fourth', label: 'Fourth' },
  { value: 'last', label: 'Last' },
]

const monthsOfYear = [
  { value: 'january', label: 'January' },
  { value: 'february', label: 'February' },
  { value: 'march', label: 'March' },
  { value: 'april', label: 'April' },
  { value: 'may', label: 'May' },
  { value: 'june', label: 'June' },
  { value: 'july', label: 'July' },
  { value: 'august', label: 'August' },
  { value: 'september', label: 'September' },
  { value: 'november', label: 'November' },
  { value: 'december', label: 'December' },
]

const monthsOfYearTypes = [
  { value: 'any', label: 'Any month' },
  { value: 'specific', label: 'Specific month(s)' },
]

const runAtTypes = [
  { value: 'any', label: 'Any time' },
  { value: 'specific', label: 'Specific time' },
  { value: 'between', label: 'Between' },
]

export function DayPicker ({ days, type, updateDelay }) {

  return ( <>
      <SimpleSelect
        className={ 'control' }
        name={ 'days_of_week_type' }
        value={ type }
        onChange={ (e) => updateDelay({ days_of_week_type: e.target.value }) }
        options={ daysOfWeekTypes }
      />
      <Select
        className={ 'control' }
        options={ daysOfWeek }
        onChange={ (v) => updateDelay({ days_of_week: v }) }
        isMulti={ true }
        name={ 'days_of_week' }
        value={ days }
      />
    </>
  )
}

export function DayOfMonthPicker ({ days, updateDelay }) {

  const daysOfMonth = []

  for (let i = 1; i <= 31; i++) {
    daysOfMonth.push({ value: i, label: i })
  }

  daysOfMonth.push({ value: 'last', label: 'Last' })

  return ( <>
      <Select
        className={ 'control' }
        options={ daysOfMonth }
        onChange={ (v) => updateDelay({ days_of_month: v }) }
        isMulti={ true }
        name={ 'days_of_month' }
        value={ days }
      />
    </>
  )
}

export function MonthPicker ({ months, type, updateDelay }) {

  return ( <>
      <SimpleSelect
        className={ 'control' }
        name={ 'months_of_year_type' }
        value={ type }
        onChange={ (e) => updateDelay({ months_of_year_type: e.target.value }) }
        options={ monthsOfYearTypes }
      />
      { type === 'specific' &&
      <Select
        className={ 'control' }
        options={ monthsOfYear }
        onChange={ (v) => updateDelay({ months_of_year: v }) }
        isMulti={ true }
        isSearchable={ true }
        name={ 'months_of_year' }
        value={ months }
      />
      }
    </>
  )
}

/**
 *
 *
 * @param time
 * @param timeTo
 * @param type
 * @param updateDelay
 * @param showBetween
 * @returns {*}
 * @constructor
 */
export function RunAt ({ time, timeTo, type, updateDelay, showBetween }) {

  if ( ! showBetween ){

  }

  return (
    <>
      { 'Run at ' }
      <SimpleSelect
        name={ 'run_at' }
        className={ 'run-at' }
        value={ type }
        onChange={ (e) => updateDelay({ run_at: e.target.value }) }
        options={ runAtTypes }
      />
      <div className={ 'run-at col-controls' }>
        { ['specific', 'between'].includes(type) &&
        <input
          name={ 'time' }
          type={ 'time' }
          value={ time || '' }
          onChange={ (e) => updateDelay({ time: e.target.value }) }
        /> }
      </div>
      <div className={ 'run-at col-controls' }>
        { ['between'].includes(type) &&
        <input
          name={ 'time_to' }
          min={ time }
          type={ 'time' }
          value={ timeTo || '' }
          onChange={ (e) => updateDelay({ time_to: e.target.value }) }
        /> }
      </div>

    </>
  )
}

export function Highlight ({children}) {
  return <span className={'gh-highlight'}>{children}</span>
}

function wrapInSpace (text) {
  return text.padStart( text.length + 1 ).padEnd( text.length + 2 );
}


export function DisplayTimeDelayFragment ({ delay }) {

  const text = [];

  const at = wrapInSpace( _x( 'at', 'step delay', 'groundhogg' ) );
  const and = wrapInSpace( _x( 'and', 'step delay', 'groundhogg' ) );
  const between = wrapInSpace( _x( 'between', 'step delay', 'groundhogg' ) );
  const anyTime = _x( 'any time', 'step delay', 'groundhogg' );

  switch (delay.run_at) {
    default:
    case 'any':
      text.push( at, <Highlight>{ anyTime }</Highlight>);
      break;
    case 'specific':
      text.push( at, <Highlight>{ moment(delay.time, 'HH:mm').
        format('hh:mm a') }</Highlight>);
      break;
    case 'between':
      text.push(between, <Highlight>{ moment(delay.time, 'HH:mm').
        format('LT') }</Highlight>, and, <Highlight>{ moment(delay.time_to,
        'HH:mm').format('LT') }</Highlight>);
      break;

  }

  return <>{ text.map( (item,i) => <Fragment key={i}>{item}</Fragment>) }</>;
}