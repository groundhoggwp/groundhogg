import React from 'react'
import axios from 'axios'
import AsyncCreatableSelect
  from 'react-select/async-creatable'
import AsyncSelect from 'react-select/async'

import { parseArgs } from '../functions'

export const FaIcon = ({ classes }) => {
  return <i className={ 'fa ' + classes.map(c => 'fa-' + c).join(' ') }></i>
}

export function TagPicker ({ selectProps, onChange, value, isCreatable }) {

  selectProps = parseArgs(selectProps || {}, {
    cacheOptions: true,
    isMulti: true,
    ignoreCase: true,
    isClearable: true,
    defaultOptions: [],
  })

  const promiseOptions = inputValue => new Promise(resolve => {
    axios.get(groundhogg.rest_base + '/tags?axios=1&q=' + inputValue).
      then(result => {
        resolve(result.data.tags)
      })
  })

  const TagSelect = isCreatable ? AsyncCreatableSelect : AsyncSelect

  return (
    <TagSelect
      { ...selectProps }
      loadOptions={ promiseOptions }
      onChange={ onChange }
      value={ value }
    />
  )
}
