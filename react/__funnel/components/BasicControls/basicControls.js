import React, { useEffect, useState } from 'react'
import AsyncCreatableSelect from 'react-select/async-creatable'
import AsyncSelect from 'react-select/async'
import CreatableSelect from 'react-select/creatable'
import Select from 'react-select'
import axios from 'axios'
import Autocomplete from 'react-autocomplete'
import { Button } from 'react-bootstrap'
import { Dashicon } from '../Dashicon/Dashicon'
import ButtonGroup from 'react-bootstrap/ButtonGroup'
import { ReplacementsButton } from '../ReplacementsButton/ReplacementsButton'

import './component.scss'
import { parseArgs } from '../../App'

const { __, _x, _n, _nx } = wp.i18n

function Text ({ id, options, update, value }) {
  return <input id={ id } className={ 'form-control' }
                onChange={ event => update(id, event.target.value) }
                value={ value } { ...options }/>
}

export function TagPicker ({ id, options, update, value }) {

  options = parseArgs( options || {}, {
    cacheOptions: true,
    isMulti: true,
    ignoreCase: true,
    isClearable: true,
    defaultOptions: [],
  } )

  const promiseOptions = inputValue => new Promise(resolve => {
    axios.get(groundhogg_endpoints.tags + '?axios=1&q=' + inputValue).
      then(result => {
        resolve(result.data.tags)
      })
  })

  return (
    <AsyncCreatableSelect
      id={ id }
      { ...options }
      loadOptions={ promiseOptions }
      onChange={ update }
      value={ value }
    />
  )
}

function validateEmail (email) {
  const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
  return re.test(email)
}

/**
 * Email Address picker
 *
 * @param id
 * @param options
 * @param update
 * @param value
 * @returns {*}
 * @constructor
 */
function EmailAddressPicker ({ id, options, update, value }) {

  const [inputValue, setInputValue] = useState('')

  const values = value && value.split(',').map(function (item) {
    return { value: item, label: item }
  })

  const addedEmail = values => {
    update(id, values && values.map(value => value.value).join(','))
  }

  const handleInputChange = (inputValue) => {
    setInputValue(inputValue)
  }

  const handleKeyDown = (event) => {
    if (!inputValue) {
      return
    }
    switch (event.key) {
      case 'Enter':
      case 'Tab':

        if (validateEmail(inputValue)) {
          setInputValue('')
          addedEmail([
            ...values, {
              label: inputValue,
              value: inputValue,
            },
          ])
        }
    }
  }

  const components = {
    DropdownIndicator: null,
  }

  return (
    <CreatableSelect
      components={ components }
      isClearable
      isMulti
      menuIsOpen={ false }
      onChange={ addedEmail }
      onKeyDown={ handleKeyDown }
      onInputChange={ handleInputChange }
      placeholder={ 'Type an email address...' }
      inputValue={ inputValue }
      value={ values }
    />
  )
}

function EmailAddress ({ id, options, update, value }) {

  const [valid, setValid] = useState(true)
  const [useDefault, setUseDefault] = useState(true)

  const handleOnChange = e => {
    setUseDefault(false)

    if (validateEmail(e.target.value)) {
      setValid(true)
    }
    else {
      setValid(false)
    }

    update(id, e.target.value)
  }

  const classes = [
    'email-address',
    valid ? 'valid' : 'invalid',
  ]

  value = !value && useDefault ? options.default : value

  return (
    <input
      id={ id }
      type={ 'email' }
      onChange={ handleOnChange }
      className={ classes.join(' ') }
      value={ value }
    />
  )

}

/**
 * Pick roles!
 *
 * @param id
 * @param options
 * @param update
 * @param value
 * @returns {*}
 * @constructor
 */
export function RolesPicker ({ id, options, update, value }) {
  return (
    <Select
      id={ id }
      ignoreCase
      isClearable
      isMulti
      onChange={ update }
      value={ value }
      options={ ghEditor.roles }
      { ...options }
    />
  )
}

export function EmailPicker ({ id, options, update, value }) {

  const promiseOptions = inputValue => new Promise(resolve => {
    axios.get(
      groundhogg_endpoints.emails + '?selectReact=1&q=' + inputValue).
      then(result => {
        resolve(result.data.emails)
      })
  })

  return (
    <>
      <AsyncSelect
        id={ id }
        cacheOptions
        defaultOptions
        isClearable
        ignoreCase={ true }
        loadOptions={ promiseOptions }
        onChange={ update }
        value={ value }
        { ...options }
      />
      <div className={ 'btn-control-group' }>
        <Button variant="outline-primary"><Dashicon
          icon={ 'edit' }/> { 'Edit Email' }</Button>
        <Button variant="outline-secondary"><Dashicon
          icon={ 'plus' }/> { 'Create New Email' }</Button>
      </div>
    </>
  )

}

export function YesNoToggle ({ id, options, update, value }) {

  return (
    <div className={ 'yes-no-toggle' }>
      <ButtonGroup>
        <Button
          onClick={ e => update(true) }
          variant={ value ? 'primary' : 'outline-primary' }
        >
          { options.yes || __('Yes', 'toggle', 'groundhogg') }
        </Button>
        <Button
          onClick={ e => update(false) }
          variant={ !value ? 'secondary' : 'outline-secondary' }
        >
          { options.no || __('No', 'toggle', 'groundhogg') }
        </Button>
      </ButtonGroup>
    </div>

  )
}

YesNoToggle.defaultProps = {
  options: {},
  id: '',
  update: function () {
  },
  value: false,
}

/**
 * Number
 *
 * @param props
 * @constructor
 */
function Number (props) {

}

export function ClearFix () {
  return <div className={ 'wp-clearfix' }></div>
}

/**
 * Textarea
 */
export function TextArea ({ id, hasReplacements, update, value, options }) {

  const handeReplacementInsert = newValue => {
    update(newValue)
  }

  return (
    <div className={ 'textarea-control-wrap' }>
      { hasReplacements &&
      <div className={ 'replacements-wrap' }>
        <ReplacementsButton
          insertTargetId={ id }
          onInsert={ handeReplacementInsert }
        />
        <ClearFix/>
      </div> }
      <textarea
        id={ id }
        onChange={ e => update(e.target.value) }
        value={ value }
        { ...options }
      />
    </div>

  )
}

TextArea.defaultProps = {
  id: '',
  hasReplacements: false,
  update: function (v) {},
  value: '',
  options: {},
}

/**
 * Build a link picker
 *
 * @param id
 * @param update
 * @param value
 * @param options
 * @returns {*}
 * @constructor
 */
export function LinkPicker ({ id, update, value, options }) {

  const [items, setItems] = useState([])

  const getItemsAsync = (search) => {

    var bodyFormData = new FormData()
    bodyFormData.set('action', 'wp-link-ajax')
    bodyFormData.set('_ajax_linking_nonce',
      groundhogg_nonces._ajax_linking_nonce)
    bodyFormData.set('term', search)

    axios({
      method: 'post',
      url: ghEditor.ajaxurl,
      data: bodyFormData,
      headers: { 'Content-Type': 'multipart/form-data' },
    }).then((result) => {
      if (result.data) {
        setItems(result.data.map((item) => {
          return {
            value: item.permalink,
            label: item.title + ' (' + item.info + ')',
          }
        }))
      }
      else {
        setItems([])
      }
    })
  }

  const handleOnChange = (e) => {
    const _search = e.target.value
    update(_search)
    getItemsAsync(_search)
  }

  return <Autocomplete
    inputProps={ {
      className: 'input w100',
      type: 'url',
    } }
    getItemValue={ (item) => item.value }
    items={ items }
    renderItem={ (item, isHighlighted) =>
      <div style={ {
        background: isHighlighted ? 'lightgray' : 'white',
        padding: 5,
      } }>
        { item.label }
      </div>
    }
    value={ value }
    onChange={ handleOnChange }
    onSelect={ (val) => update(val) }
    wrapperStyle={ {
      display: 'block',
    } }
    menuStyle={ {
      borderRadius: '3px',
      boxShadow: '0 2px 12px rgba(0, 0, 0, 0.1)',
      background: 'rgba(255, 255, 255, 0.9)',
      padding: '2px 2px',
      fontSize: '90%',
      position: 'fixed',
      overflowY: 'auto',
      maxHeight: 200,
    } }
  />
}

LinkPicker.defaultProps = {
  id: '',
  update: function (v) {},
  value: '',
  options: {},
}

/**
 * Build a custom picker
 *
 * @param id
 * @param update
 * @param value
 * @param options
 * @returns {*}
 * @constructor
 */
export function CustomFieldPicker ({ id, update, value, options }) {

  const [items, setItems] = useState([])

  const getItemsAsync = (search) => {

    axios({
      method: 'get',
      url: groundhogg_endpoints.meta_keys + '?search=' + search,
    }).then((result) => {
      if (result.data) {
        setItems(Object.keys(result.data.keys).map((item) => {
          return {
            value: item,
            label: item,
          }
        }))
      }
      else {
        setItems([])
      }
    })
  }

  const handleOnChange = (e) => {
    const _search = e.target.value
    update(_search)
    getItemsAsync(_search)
  }

  return <Autocomplete
    inputProps={ {
      className: 'input w100',
      type: 'text',
    } }
    getItemValue={ (item) => item.value }
    items={ items }
    renderItem={ (item, isHighlighted) =>
      <div style={ {
        background: isHighlighted ? 'lightgray' : 'white',
        padding: 5,
      } }>
        { item.label }
      </div>
    }
    value={ value }
    onChange={ handleOnChange }
    onSelect={ (val) => update(val) }
    wrapperStyle={ {
      display: 'block',
    } }
    menuStyle={ {
      borderRadius: '3px',
      boxShadow: '0 2px 12px rgba(0, 0, 0, 0.1)',
      background: 'rgba(255, 255, 255, 0.9)',
      padding: '2px 2px',
      fontSize: '90%',
      position: 'fixed',
      overflowY: 'auto',
      maxHeight: 200,
      zIndex: 999,
      cursor: 'pointer',
    } }
  />
}

CustomFieldPicker.defaultProps = {
  id: '',
  update: function (v) {},
  value: '',
  options: {},
}

/**
 * Copy readonly input
 *
 * @param content
 * @param options
 * @returns {*}
 * @constructor
 */
export function CopyInput ({ content, options }) {
  return (
    <input
      type={ 'text' }
      className={ 'code w100' }
      value={ content }
      readOnly={ true }
      onFocus={ (e) => e.target.select() }
      { ...options }
    />
  )
}

/**
 * Creates a pretty list based on the select results...
 *
 * @param separator
 * @param use
 * @param items
 * @param highlight
 * @returns {*}
 * @constructor
 */
export function ItemsCommaOrList ({ separator, use, items, highlight }) {

  if (!items) {
    return <></>
  }

  return ( <>{ items.map(
      (item, i) => <span className={ 'items-comma-list' } key={ i }>{ highlight(
        { children: item }) }{ i < items.length -
      2
        ? separator
        : i ===
        items.length - 2 ? ' ' + use : '' } </span>) }</>
  )
}

function Bold ({ children }) {
  return <b>{ children }</b>
}

ItemsCommaOrList.defaultProps = {
  separator: ',',
  use: __('or', 'groundhogg'),
  items: [],
  highlight: Bold,
}

/**
 *
 * Creates a pretty list based on the select results...
 *
 * @param items
 * @param highlight
 * @param separator
 * @returns {*}
 * @constructor
 */
export function ItemsCommaAndList ({ items, highlight, separator }) {
  return <ItemsCommaOrList
    use={ __('and', 'groundhogg') }
    separator={ separator }
    highlight={ highlight }
    items={ items }
  />
}

ItemsCommaAndList.defaultProps = {
  separator: ',',
  items: [],
  highlight: Bold,
}

export function TagSpan ({ icon, tagName }) {
  return <span className={ 'gh-tag-span' }><Dashicon
    icon={ icon }/> { tagName }</span>
}

TagSpan.defaultProps = {
  icon: 'tag',
  tagName: '',
}

/**
 *
 * @param props
 * @returns {*}
 * @constructor
 */
export function SimpleSelect (props) {

  const { options } = props
  // delete props['options'];

  // if ( props.hasOwnProperty('options')){
  //   delete props.options;
  // }

  return (
    <select
      { ...props }
    >
      { options.map(item => <option key={ item.value }
                                  value={ item.value }>{ item.label }</option>) }
    </select>
  )
}

export const IsValidError = ({ isValid, errMsg }) => {
  if (!isValid) {
    return <div className={ 'is-valid-error' }><Dashicon
      icon={ 'warning' }/> { errMsg }</div>
  }
  return <></>
}