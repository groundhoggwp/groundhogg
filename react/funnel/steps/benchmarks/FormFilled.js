import React, { useState } from 'react'
import { registerStepType, SimpleEditModal } from '../steps'
import { Button, ButtonGroup, Col, Row, Tab, Tabs } from 'react-bootstrap'
import {
  ClearFix,
  CopyInput, CustomFieldPicker, IsValidError,
  LinkPicker, SimpleSelect, TagPicker, TagSpan,
  TextArea,
  YesNoToggle,
} from '../../components/BasicControls/basicControls'

import '../../../../assets/css/frontend/form.css'
import { ReactSortable } from 'react-sortablejs'
import { Dashicon } from '../../components/Dashicon/Dashicon'
import { isValidUrl, parseArgs, uniqId } from '../../App'
import { Tooltip } from '../../components/Tooltip/Tooltip'

const { __, _x, _n, _nx } = wp.i18n

registerStepType('form_fill', {

  icon: ghEditor.steps.form_fill.icon,
  group: ghEditor.steps.form_fill.group,

  defaultSettings: {
    form_name: __('Web Form', 'groundhogg'),
  },

  title: ({ data, context, settings }) => {
    return <>{ _x('When', 'form step title', 'groundhogg') } <TagSpan
      icon={ 'editor-table' } tagName={ settings.form_name }/> { _x(
      'is filled', 'form step title', 'groundhogg') }</>
  },

  edit: ({ data, context, settings, updateSettings, commit, done }) => {

    const updateSetting = (name, value) => {
      updateSettings({
        [name]: value,
      })
    }

    const [tab, setTab] = useState('form')

    return (
      <SimpleEditModal
        title={ 'Form Filled...' }
        done={ done }
        commit={ commit }
        modalProps={ {
          size: 'lg',
          dialogClassName: tab === 'form'
            ? 'modal-90w form'
            : 'modal-md form',
        } }
        modalBodyProps={ {
          className: 'no-padding',
        } }
      >
        <Tabs activeKey={ tab } onSelect={ (t) => setTab(t) }>
          { Object.keys(editFormTabs).
            map(tab => renderTab(tab, data, settings, context,
              updateSettings, updateSetting)) }
        </Tabs>
      </SimpleEditModal>
    )
  },
})

function renderTab (
  tab, data, settings, context, updateSettings, updateSetting) {
  const tabContent = React.createElement(editFormTabs[tab].render, {
    data: data,
    settings: settings,
    context: context,
    updateSettings: updateSettings,
    updateSetting: updateSetting,
  })

  return (
    <Tab key={ tab } eventKey={ tab } title={ editFormTabs[tab].title }
         tabClassName={ tab }>
      <div className={ tab + ' tab-content-inner' }>
        { tabContent }
      </div>
    </Tab>
  )
}

const editFormTabs = {
  form: {
    id: 'form',
    title: __('Form', 'tab name', 'groundhogg'),
    render: ({ context, settings, updateSettings, updateSetting }) => {

      const formUpdated = (newJson) => {
        updateSettings({
          form_json: newJson,
        })
      }

      const formJSON = settings.form_json || context.default_form_json

      return (
        <>
          <FormBuilder
            formJSON={ formJSON }
            formUpdated={ formUpdated }
            settings={ settings }
            updateSetting={ updateSetting }
          />
        </>
      )
    },
  },
  submit: {
    id: 'submit',
    title: __('Submit', 'tab name', 'groundhogg'),
    render: ({ settings, updateSetting, context }) => {
      return (
        <>
          { context.recaptcha_is_v3 &&
          <Row className={ 'step-setting-control no-margins' }>
            <Col sm={ 5 }>
              <label>{ __('Enable reCaptcha V3?',
                'groundhogg') }</label>
              <p className={ 'description' }>{ __(
                'Enable Google reCaptcha V3 on this form to prevent spam.',
                'groundogg') }</p>
            </Col>
            <Col>
              <YesNoToggle
                value={ settings.enable_recaptcha_v3 }
                update={ (v) => updateSetting('enable_recaptcha_v3',
                  v) }
              />
            </Col>
          </Row>
          }
          <Row className={ 'step-setting-control no-margins' }>
            <Col sm={ 5 }>
              <label>{ __('Stay on page after submit?',
                'groundhogg') }</label>
              <p className={ 'description' }>{ __(
                'This will prevent the contact from being redirected after submitting the form.',
                'groundogg') }</p>
            </Col>
            <Col>
              <YesNoToggle
                value={ settings.enable_ajax }
                update={ (v) => updateSetting('enable_ajax',
                  v) }
              />
            </Col>
          </Row>
          { settings.enable_ajax && <Row className={ 'no-margins' }>
            <Col sm={ 5 }>
              <label>{ 'Success message' }</label>
              <p
                className={ 'description' }>{ 'Message displayed when the contact submits the form.' }</p>
            </Col>
            <Col>
              <TextArea
                id={ 'success_message' }
                value={ settings.success_message }
                hasReplacements={ true }
                options={ {
                  className: 'w100',
                } }
                update={ (v) => updateSetting(
                  'success_message', v) }/>
            </Col>
          </Row> }
          { !settings.enable_ajax && <Row className={ 'no-margins' }>
            <Col sm={ 5 }>
              <label>{ 'Success page' }</label>
              <p
                className={ 'description' }>{ 'Where the contact will be directed upon submitting the form.' }</p>
            </Col>
            <Col>
              <LinkPicker value={ settings.success_page }
                          update={ (v) => updateSetting(
                            'success_page', v) }/>
              <IsValidError
                isValid={ isValidUrl(settings.success_page) }
                errMsg={ __('Please provide a valid url!', 'groundhogg') }
              />
            </Col>
          </Row> }
        </>
      )
    },
  },
  embed: {
    id: 'embed',
    title: __('Embed', 'groundhogg'),
    render: ({ context }) => {
      return ( <>
        <Row className={ 'step-setting-control no-margins' }>
          <Col sm={ 5 }>
            <label>{ 'Shortcode' }</label>
            <p
              className={ 'description' }>{ 'Insert anywhere WordPress shortcodes are accepted.' }</p>
          </Col>
          <Col>
            <CopyInput
              content={ context.embed.shortcode }
            />
          </Col>
        </Row>
        <Row className={ 'step-setting-control no-margins' }>
          <Col sm={ 5 }>
            <label>{ 'iFrame' }</label>
            <p
              className={ 'description' }>{ 'For use when embedding forms on none WordPress sites.' }</p>
          </Col>
          <Col>
            <CopyInput
              content={ context.embed.iframe }
            />
          </Col>
        </Row>
        <Row className={ 'step-setting-control no-margins' }>
          <Col sm={ 5 }>
            <label>{ 'Raw HTML' }</label>
            <p
              className={ 'description' }>{ 'For use when embedding forms on none WordPress sites and HTML web form integrations (Thrive).' }</p>
          </Col>
          <Col>
            <CopyInput
              content={ context.embed.html }
            />
          </Col>
        </Row>
        <Row className={ 'step-setting-control no-margins' }>
          <Col sm={ 5 }>
            <label>{ 'Hosted URL' }</label>
            <p
              className={ 'description' }>{ 'Direct link to the web form.' }</p>
          </Col>
          <Col>
            <CopyInput
              content={ context.embed.hosted }
            />
          </Col>
        </Row>
      </> )
    },
  },
}

function FormBuilder ({ formJSON, formUpdated, settings, updateSetting }) {

  let formRowsJSON = formJSON.reduce((prev, curr) => {

    // get sum of previous rows width
    let prevWidth = prev.length ? prev[prev.length - 1].reduce(
      (acc, cur) => acc + widthMap[cur.width].value, 0) : 0

    if (prev.length && prevWidth + widthMap[curr.width].value <= 1) {
      prev[prev.length - 1].push(curr)
    }
    else {
      prev.push([curr])
    }
    return prev

  }, [])

  return (
    <Row className={ 'no-margins no-padding' }>
      <Col className={ 'no-padding form-builder-wrap' }>
        <div className={ 'form-builder-wrap' }>
          <div className={ 'form-name-wrap' }>
            <label>{ 'Form Name' }</label>
            <input
              type={ 'text' }
              value={ settings.form_name }
              className={ 'form-name alignright' }
              onChange={ e => updateSetting('form_name',
                e.target.value) }
            />
            <ClearFix/>
          </div>
          <FieldsEditor
            fields={ formJSON }
            formUpdated={ formUpdated }
          />
        </div>
      </Col>
      <Col className={ 'form-preview' }>
        { formRowsJSON.map((row) =>
          <div className={ 'gh-form-row' }>
            { row.map((field) => renderField(field)) }
            <ClearFix/>
          </div>,
        ) }
      </Col>
    </Row>
  )
}

function FieldsEditor ({ fields, formUpdated }) {

  const [active, setActive] = useState(false)

  const addField = () => {
    fields.push({
      type: 'text',
      id: uniqId('field_'),
      width: '1/1',
      attributes: {
        name: 'new_custom_field_name',
        label: __('New Field', 'groundhogg'),
      },
    })

    formUpdated(fields)
  }

  const deleteField = (fieldId, e) => {
    e.stopPropagation()
    formUpdated(fields.filter(field => field.id !== fieldId))
  }

  const editField = (fieldId) => {
    setActive(fields.find(field => field.id === fieldId))
  }

  const updateField = (fieldId, name, value) => {
    const i = fields.map(field => field.id).indexOf(fieldId)
    fields[i][name] = value
    formUpdated(fields)
  }

  const doneEditing = () => {
    setActive(false)
  }

  if (active) {
    return (
      <div className={ 'form-fields' }>
        <FieldEditor
          field={ active }
          updateField={ updateField }
          onEdit={ doneEditing }
        />
      </div>
    )
  }

  return (
    <>
      <ReactSortable
        list={ fields }
        setList={ formUpdated }
        className={ 'form-fields' }
      >
        { fields.map(field => <FieldSortable
          key={ field.id }
          field={ field }
          onDelete={ deleteField }
          onEdit={ editField }
        />) }
      </ReactSortable>
      <div className={ 'add-field-wrap' }>
        <Button variant={ 'outline-secondary' } onClick={ addField }>
          <Dashicon icon={ 'plus' }/>
          { 'Add Field' }
        </Button>
      </div>
    </>
  )
}

function FieldLabel ({ field }) {
  let name

  if (FieldTypes.hasOwnProperty(field.type) &&
    FieldTypes[field.type].hasOwnProperty('renderName')) {
    // console.debug( field );
    name = FieldTypes[field.type].renderName({ attributes: field.attributes })
  }
  else {
    name = field.attributes.label
  }

  return <span className={ 'field-label' }>{ name }</span>
}

function FieldSortable ({ field, onDelete, onEdit }) {

  return (
    <div
      className={ 'field-sortable-item form-field' }
      onClick={ (e) => onEdit(field.id) }
    >
      <FieldLabel field={ field }/>
      <button
        className={ 'delete-field-button' }
        onClick={ (e) => onDelete(field.id, e) }
      >
        <Dashicon icon={ 'no-alt' }/>
      </button>
    </div>
  )

}

function FieldEditor ({ field, onEdit, updateField }) {

  const attrs = FieldTypes[field.type].attributes
  const attributes = field.attributes
  const fieldId = field.id

  const updateAttribute = (attr, value) => {

    if (typeof attr === 'object' && attr !== null) {
      updateField(fieldId, 'attributes', {
        ...attributes,
        ...attr,
      })
    }
    else {
      attributes[attr] = value
      updateField(fieldId, 'attributes', attributes)
    }
  }

  return (
    <>
      <div
        key={ field.id }
        className={ 'field-sortable-item form-field' }
        onClick={ onEdit }
      >
        <FieldLabel field={ field }/>
        <button
          onClick={ onEdit }
          className={ 'edit-field-button' }
        >
          <Dashicon icon={ 'yes' }/>
        </button>
      </div>
      <div className={ 'field-attributes' }>
        <BasicAttributeControlGroup
          label={ 'Field Type' }
        >
          <select
            className={ 'w100' }
            value={ field.type }
            onChange={ (e) => updateField(fieldId, 'type',
              e.target.value) }>
            { Object.values(FieldTypes).map(type => <option
              key={ type.type }
              value={ type.type }>{ type.name }</option>) }
          </select>
        </BasicAttributeControlGroup>
        { attrs.map(attr => <FieldAttribute
          key={ attr }
          type={ attr }
          value={ attributes[attr] }
          updateAttribute={ updateAttribute }
          allAttributes={ attributes }
        />) }
        <BasicAttributeControlGroup
          label={ 'Column Width' }
        >
          <ButtonGroup vertical>
            <ButtonGroup aria-label="field-width" className={ 'field-width' }>
              { Object.keys(widthMap).splice(0, 3).map((width) =>
                <Button
                  key={ width }
                  variant={ field.width === width
                    ? 'primary'
                    : 'outline-secondary' }
                  onClick={ (e) => updateField(fieldId, 'width', width) }
                >
                  { width }
                </Button>) }
            </ButtonGroup>
            <ButtonGroup aria-label="field-width" className={ 'field-width' }>
              { Object.keys(widthMap).splice(3, 3).map((width) =>
                <Button
                  key={ width }
                  variant={ field.width === width
                    ? 'primary'
                    : 'outline-secondary' }
                  onClick={ (e) => updateField(fieldId, 'width', width) }
                >
                  { width }
                </Button>) }
            </ButtonGroup>
          </ButtonGroup>
        </BasicAttributeControlGroup>
      </div>
    </>
  )
}

function FieldAttribute ({ type, value, updateAttribute, allAttributes }) {

  if (!fieldAttributes.hasOwnProperty(type)) {
    return <div className={ 'not-implemented' }>{ 'Not implemented' }</div>
  }

  return React.createElement(fieldAttributes[type].edit, {
    value: value,
    updateAttribute: updateAttribute,
    allAttributes: allAttributes,
  })

}

function BasicAttributeControlGroup ({ label, children }) {
  return (
    <Row className={ 'field-attribute-control' }>
      <Col>
        <label>{ label }</label>
      </Col>
      <Col>
        { children }
      </Col>
    </Row>
  )
}

const fieldAttributes = {
  required: {
    edit: ({ value, updateAttribute }) => {
      return (
        <Row className={ 'field-attribute-control' }>
          <Col>
            <label>{ 'Is this field required?' }</label>
          </Col>
          <Col>
            <YesNoToggle
              value={ value }
              update={ (value) => updateAttribute('required',
                value) }
            />
          </Col>
        </Row>
      )
    },
  },
  label: {
    edit: ({ value, updateAttribute }) => {
      return (
        <Row className={ 'field-attribute-control' }>
          <Col>
            <label>{ 'Field Label' }</label>
          </Col>
          <Col>
            <input
              type={ 'text' }
              value={ value }
              className={ 'w100' }
              onChange={ (e) => updateAttribute('label',
                e.target.value) }
            />
          </Col>
        </Row>
      )
    },
  },
  text: {
    edit: ({ value, updateAttribute }) => {
      return (
        <BasicAttributeControlGroup
          label={ 'Button Text' }
        >
          <input
            type={ 'text' }
            value={ value }
            className={ 'w100' }
            onChange={ (e) => updateAttribute('text',
              e.target.value) }
          />
        </BasicAttributeControlGroup>
      )
    },
  },
  hideLabel: {
    edit: ({ value, updateAttribute }) => {
      return (
        <Row className={ 'field-attribute-control' }>
          <Col>
            <label>{ 'Hide field label?' }</label>
          </Col>
          <Col>
            <YesNoToggle
              value={ value }
              update={ (value) => updateAttribute('hideLabel',
                value) }
            />
          </Col>
        </Row>
      )
    },
  },
  name: {
    edit: ({ value, updateAttribute }) => {
      return (
        <BasicAttributeControlGroup
          label={ <>{ 'Custom Field ID' } <Tooltip content={ __(
            'This is the ID of the custom field the value will be saved to.',
            'groundhogg') }/></> }
        >
          <CustomFieldPicker
            value={ value }
            update={ (value) => updateAttribute('name',
              value) }
          />
        </BasicAttributeControlGroup>
      )
    },
  },
  description: {
    edit: ({ value, updateAttribute, allAttributes }) => {

      if (!allAttributes.showDescription) {
        return <></>
      }

      return (
        <BasicAttributeControlGroup
          label={ 'Field Description' }
        >
					<textarea
            value={ value }
            className={ 'w100' }
            onChange={ (e) => updateAttribute('description',
              e.target.value) }
          />
        </BasicAttributeControlGroup>
      )
    },
  },
  showDescription: {
    edit: ({ value, updateAttribute }) => {
      return (
        <Row className={ 'field-attribute-control' }>
          <Col>
            <label>{ 'Show description?' }</label>
          </Col>
          <Col>
            <YesNoToggle
              value={ value }
              update={ (value) => updateAttribute(
                'showDescription',
                value) }
            />
          </Col>
        </Row>
      )
    },
  },
  placeholder: {
    edit: ({ value, updateAttribute }) => {
      return (
        <Row className={ 'field-attribute-control' }>
          <Col>
            <label>{ 'Placeholder' }</label>
          </Col>
          <Col>
            <input
              type={ 'text' }
              value={ value }
              className={ 'w100' }
              onChange={ (e) => updateAttribute('placeholder',
                e.target.value) }
            />
          </Col>
        </Row>
      )
    },
  },
  id: {
    edit: ({ value, updateAttribute }) => {

      const updateCssID = (v) => {
        v = v.replace(/[^A-z0-9-_]/, '')
        updateAttribute({ id: v })
      }

      return (
        <Row className={ 'field-attribute-control' }>
          <Col>
            <label>{ 'CSS ID' }</label>
          </Col>
          <Col>
            <input
              type={ 'text' }
              value={ value }
              className={ 'w100' }
              onChange={ (e) => updateCssID(e.target.value) }
            />
          </Col>
        </Row>
      )
    },
  },
  class: {
    edit: ({ value, updateAttribute }) => {

      const updateCssClass = (v) => {
        v = v.replace(/[^A-z0-9-_]/, '')
        updateAttribute({ class: v })
      }

      return (
        <Row className={ 'field-attribute-control' }>
          <Col>
            <label>{ 'CSS Class' }</label>
          </Col>
          <Col>
            <input
              type={ 'text' }
              value={ value }
              className={ 'w100' }
              onChange={ (e) => updateCssClass(e.target.value) }
            />
          </Col>
        </Row>
      )
    },
  },
  options: {
    edit: ({ value, updateAttribute }) => {

      const curOptions = value || [
        {
          value: '',
          label: '',
          tag: null,
        },
      ]

      if (curOptions.length === 0) {
        value.push({
          value: '',
          label: '',
          tag: null,
        })
      }

      const addOption = () => {
        const newOptions = [
          ...curOptions,
          {
            value: '',
            label: '',
            tag: null,
          },
        ]

        updateAttribute({
          options: newOptions,
        })

      }

      const updateOption = (i, newAttr) => {
        curOptions[i] = { ...curOptions[i], ...newAttr }
        updateAttribute({
          options: curOptions,
        })
      }

      const deleteOption = (index) => {
        curOptions.splice(index, 1)
        updateAttribute({
          options: curOptions,
        })
      }

      return (
        <div className={ 'field-attribute-control attribute-options' }>
          <Row className={ 'attribute-option no-margins' }>
            <Col sm={ 3 }>
              <label>
                { __('Option Value', 'groundhogg') }
              </label>
            </Col>
            <Col>
              <label>
                { __('Option Label', 'groundhogg') }
              </label>
            </Col>
            <Col sm={ 3 }>
              <label>
                { __('Tag', 'groundhogg') }
                <Tooltip
                  content={ __(
                    'You can select a tag to be applied when a user picks a specific option.',
                    'groundhogg') }
                />
              </label>
            </Col>
            <Col sm={ 1 }>
              <button className={ 'alignright clear-button' }
                      onClick={ addOption }>
                <Dashicon icon={ 'plus' }/>
              </button>
            </Col>
          </Row>
          {
            curOptions && curOptions.map((option, i) => <AttrOptionControl
              key={ i }
              index={ i }
              value={ option.value }
              label={ option.label }
              tag={ option.tag }
              onUpdate={ updateOption }
              onDelete={ deleteOption }
            />)
          }
        </div>
      )
    },
  },
  multiple: {
    edit: ({ value, updateAttribute }) => {
      return (
        <Row className={ 'field-attribute-control' }>
          <Col>
            <label>{ 'Select multiple?' }</label>
          </Col>
          <Col>
            <YesNoToggle
              value={ value }
              update={ (value) => updateAttribute({ multiple: value }) }
            />
          </Col>
        </Row>
      )
    },
  },
  default: {
    edit: ({ value, updateAttribute, allAttributes }) => {
      return (
        <Row className={ 'field-attribute-control' }>
          <Col>
            <label>{ 'Default Selection' }</label>
          </Col>
          <Col>
            <SimpleSelect
              value={ value }
              onChange={ (e) => updateAttribute({ default: e.target.value }) }
              options={ allAttributes.options || [] }
              className={ 'w100' }
            />
          </Col>
        </Row>
      )
    },
  },
  value: {
    edit: ({ value, updateAttribute }) => {
      return (
        <Row className={ 'field-attribute-control' }>
          <Col>
            <label>{ 'Value' } <Tooltip
              content={ __('This is what will be saved to the custom field.',
                'groundhogg') }/></label>
          </Col>
          <Col>
            <input
              type={ 'text' }
              value={ value }
              className={ 'w100' }
              onChange={ (e) => updateAttribute({ value: e.target.value }) }
            />
          </Col>
        </Row>
      )
    },
  },
  tag: {
    edit: ({ value, updateAttribute }) => {
      return (
        <Row className={ 'field-attribute-control' }>
          <Col>
            <label>{ 'Apply a tag' } <Tooltip
              content={ __(
                'This tag will be applied if the checkbox is selected.',
                'groundhogg') }/></label>
          </Col>
          <Col>
            <TagPicker
              value={ value }
              update={ (v) => updateAttribute({ tag: v }) }
              options={ {
                isMulti: false,
              } }
            />
          </Col>
        </Row>
      )
    },
  },
  captchaTheme: {
    edit: ({ value, updateAttribute, allAttributes }) => {

      const captchaThemes = [
        { value: 'light', label: 'Light' },
        { value: 'dark', label: 'Dark' },
      ]

      return (
        <Row className={ 'field-attribute-control' }>
          <Col>
            <label>{ 'reCaptcha Theme' }</label>
          </Col>
          <Col>
            <SimpleSelect
              value={ value }
              onChange={ (e) => updateAttribute(
                { captchaTheme: e.target.value }) }
              options={ captchaThemes }
              className={ 'w100' }
            />
          </Col>
        </Row>
      )
    },
  },
  captchaSize: {
    edit: ({ value, updateAttribute }) => {

      const captchaSizes = [
        { value: 'normal', label: 'Normal' },
        { value: 'compact', label: 'Compact' },
      ]

      return (
        <Row className={ 'field-attribute-control' }>
          <Col>
            <label>{ 'reCaptcha Size' }</label>
          </Col>
          <Col>
            <SimpleSelect
              value={ value }
              onChange={ (e) => updateAttribute(
                { captchaSize: e.target.value }) }
              options={ captchaSizes }
              className={ 'w100' }
            />
          </Col>
        </Row>
      )
    },
  },
  min: {
    edit: ({ value, updateAttribute }) => {
      return (
        <BasicAttributeControlGroup
          label={ <>{ 'Min' } <Tooltip content={ __(
            'The minimum value which can be chosen.',
            'groundhogg') }/></> }
        >
          <input
            type={'number'}
            value={value}
            className={'w100'}
            onChange={(e) => updateAttribute({min: e.target.value})}
          />
        </BasicAttributeControlGroup>
      )
    },
  },
  max: {
    edit: ({ value, updateAttribute, allAttributes }) => {
      return (
        <BasicAttributeControlGroup
          label={ <>{ 'Max' } <Tooltip content={ __(
            'The maximum value which can be chosen.',
            'groundhogg') }/></> }
        >
          <input
            type={'number'}
            value={value}
            min={allAttributes.min}
            className={'w100'}
            onChange={(e) => updateAttribute({max: e.target.value})}
          />
        </BasicAttributeControlGroup>
      )
    },
  },

}

const AttrOptionControl = ({ index, value, label, tag, onUpdate, onDelete }) => {

  const handleChange = (newAttr) => {
    onUpdate(index, newAttr)
  }

  return (
    <Row className={ 'attribute-option no-margins' }>
      <Col sm={ 3 }>
        <input
          type={ 'text' }
          value={ value }
          placeholder={ __('Value', 'form editor', 'groundhogg') }
          onChange={ (e) => handleChange({ value: e.target.value }) }
        />
      </Col>
      <Col>
        <input
          type={ 'text' }
          value={ label }
          placeholder={ __('Label', 'form editor', 'groundhogg') }
          onChange={ (e) => handleChange({ label: e.target.value }) }
        />
      </Col>
      <Col sm={ 3 }>
        <TagPicker
          value={ tag }
          options={ {
            isMulti: false,
          } }
          update={ (v) => handleChange({ tag: v }) }
        />
      </Col>
      <Col sm={ 1 }>
        <button className={ 'alignright clear-button delete-button' }
                onClick={ () => onDelete(index) }>
          <Dashicon icon={ 'no' }/>
        </button>
      </Col>
    </Row>
  )
}

const FieldTypes = {
  first: {
    type: 'first',
    name: 'First',
    attributes: [
      'label',
      'hideLabel',
      'required',
      'placeholder',
      'showDescription',
      'description',
      'id',
      'class',
    ],
    render: function ({ attributes }) {

      attributes = parseArgs(attributes, {
        label: 'First Name',
        required: true,
      })

      attributes = parseArgs({
        name: 'first_name',
      }, attributes)

      return <InputFieldGroup
        type={ 'text' }
        attributes={ attributes }
      />
    },
  },
  last: {
    type: 'last',
    name: 'Last',
    attributes: [
      'label',
      'hideLabel',
      'required',
      'placeholder',
      'showDescription',
      'description',
      'id',
      'class',
    ],
    render: function ({ attributes }) {

      attributes = parseArgs(attributes, {
        label: 'Last Name',
        required: true,
      })

      attributes = parseArgs({
        name: 'last_name',
      }, attributes)

      return <InputFieldGroup
        type={ 'text' }
        attributes={ attributes }
      />
    },
  },
  email: {
    type: 'email',
    name: 'Email',
    attributes: [
      'label',
      'hideLabel',
      'placeholder',
      'showDescription',
      'description',
      'id',
      'class',
    ],
    render: function ({ attributes }) {

      attributes = parseArgs(attributes, {
        label: 'Email',
      })

      attributes = parseArgs({
        name: 'email',
        required: true,
      }, attributes)

      return <InputFieldGroup
        type={ 'email' }
        attributes={ attributes }
      />
    },
  },
  phone: {
    type: 'phone',
    name: 'Phone',
    attributes: [
      'required', 'label', 'placeholder', 'showDescription',
      'description', 'id', 'class',
    ],
    render: function ({ attributes }) {

      attributes = parseArgs(attributes, {
        label: 'Phone',
        required: true,
      })

      attributes = parseArgs({
        name: 'primary_phone',
      }, attributes)

      return <InputFieldGroup
        type={ 'tel' }
        attributes={ attributes }
      />
    },
  },
  gdpr: {
    type: 'gdpr',
    name: 'GDPR',
    attributes: ['label', 'tag', 'id', 'class'],
    renderName: ({ attributes }) => {
      return __('GDPR Consent', 'groundhogg')
    },
    render: function ({ attributes }) {

      attributes = parseArgs(attributes, {
        class: 'gh-gdpr',
        label: 'I consent...',
      })

      attributes = parseArgs({
        name: 'gdpr_consent',
        id: 'gdpr_consent',
        required: true,
        value: 'yes',
      }, attributes)

      return <CheckboxFieldGroup
        attributes={ attributes }
      />
    },
  },
  terms: {
    type: 'terms',
    name: 'Terms',
    attributes: ['label', 'tag', 'id', 'class'],
    renderName: ({ attributes }) => {
      return __('Terms Agreement', 'groundhogg')
    },
    render: function ({ attributes }) {

      attributes = parseArgs(attributes, {
        class: 'gh-terms',
        label: <>{ 'I agree to the' } <i>{ 'terms of service' }</i></>,
        required: true,
      })

      attributes = parseArgs({
        name: 'agree_terms',
        id: 'agree_terms',
        value: 'yes',
      }, attributes)

      return <CheckboxFieldGroup
        attributes={ attributes }
      />
    },
  },
  recaptcha: {
    type: 'recaptcha',
    name: 'reCaptcha V2',
    attributes: ['captchaTheme', 'captchaSize'],
    renderName: ({ attributes }) => {
      return 'reCaptcha V2'
    },
    render: ({ attributes }) => {
      return <div className={ 'gh-recaptcha' }>{ __(
        'The reCaptcha field will be rendered on the frontend.',
        'groundhogg') }</div>
    },
  },
  submit: {
    type: 'submit',
    name: 'Submit',
    attributes: ['text', 'id', 'class'],
    renderName: ({ attributes }) => {
      return attributes.text
    },
    render: function ({ attributes }) {

      attributes = parseArgs(attributes, {
        text: 'Submit',
      })

      return ( <div className={ 'gh-button-wrapper' }>
        <button type={ 'submit' } id={ attributes.id }
                className={ [
                  'gh-submit-button btn btn-outline-primary button',
                  attributes.class,
                ].join(
                  ' ') }>
          { attributes.text }
        </button>
      </div> )
    },
  },
  text: {
    type: 'text',
    name: 'Text',
    attributes: [
      'required',
      'label',
      'placeholder',
      'name',
      'showDescription',
      'description',
      'id',
      'class',
    ],
    render: function ({ attributes }) {
      return <InputFieldGroup
        type={ 'text' }
        attributes={ attributes }
      />
    },
  },
  textarea: {
    type: 'textarea',
    name: 'Textarea',
    attributes: [
      'required',
      'label',
      'placeholder',
      'name',
      'showDescription',
      'description',
      'id',
      'class',
    ],
    render: function ({ attributes }) {
      return <BasicFieldGroup
        label={ attributes.label }
        hideLabel={ attributes.hideLabel }
        isRequired={ attributes.required }
      >
				<textarea
          id={ attributes.id }
          className={ ['gh-input', attributes.class].join(' ') }
          name={ attributes.name }
          value={ attributes.value }
          placeholder={ attributes.placeholder }
          title={ attributes.title }
          required={ attributes.required }
        />
      </BasicFieldGroup>
    },
  },
  number: {
    type: 'number',
    name: 'Number',
    attributes: [
      'required',
      'label',
      'name',
      'min',
      'max',
      'showDescription',
      'description',
      'id',
      'class',
    ],
    render: function ({ attributes }) {
      return <InputFieldGroup
        type={ 'number' }
        attributes={ attributes }
        inputProps={ {
          min: attributes.min,
          max: attributes.max,
        } }
      />
    },
  },
  dropdown: {
    type: 'dropdown',
    name: 'Dropdown',
    attributes: [
      'required',
      'label',
      'name',
      'options',
      'default',
      'multiple',
      'showDescription',
      'description',
      'id',
      'class',
    ],
    render: function ({ attributes }) {
      return <BasicFieldGroup
        label={ attributes.label }
        hideLabel={ attributes.hideLabel }
        isRequired={ attributes.required }
      >
        <SimpleSelect
          id={ attributes.id }
          className={ ['gh-input', attributes.class].join(' ') }
          name={ attributes.name }
          value={ attributes.default }
          multiple={ attributes.multiple }
          options={ attributes.options || [] }
        />
      </BasicFieldGroup>
    },
  },
  radio: {
    type: 'radio',
    name: 'Radio',
    attributes: [
      'required', 'label', 'name', 'options', 'showDescription',
      'description', 'id', 'class',
    ],
    render: function ({ attributes }) {

      parseArgs(attributes, {
        options: [],
      })

      return (
        <>
          <label className={ 'gh-input-label' }>
            { attributes.label } { attributes.required &&
          <span className={ 'is-required' }>*</span> }
          </label>

          { attributes.options && attributes.options.map((option, i) => <label>
            <input
              type={ 'radio' }
              className={ 'gh-radio ' + attributes.class }
              key={ i }
              name={ attributes.name }
              value={ option.value }/> { option.label }
          </label>) }
        </>
      )
    },
  },
  checkbox: {
    type: 'checkbox',
    name: 'Checkbox',
    attributes: [
      'required',
      'label',
      'name',
      'value',
      'tag',
      'showDescription',
      'description',
      'id',
      'class',
    ],
    render: function ({ attributes }) {
      return <CheckboxFieldGroup
        attributes={ attributes }
      />
    },
  },
  address: {
    type: 'address',
    name: 'Address',
    attributes: ['required', 'label', 'id', 'class'],
    render: function ({ attributes }) {

      attributes = parseArgs( attributes, {
        show: [
          'street_address',
          'city',
          'state',
          'country',
          'zip',
        ]
      } );

      const fields = [];

      // street address 1
      // street address 2

      if ( attributes.show.includes( 'street_address' ) ){
        fields.push( <FormRow>
          <FormColumn
            width={'3/4'}
          >
            <input
              type={'text'}
              className={'gh-input'}
              placeholder={ __( 'Street Address', 'groundhogg' ) }
              name={'street_address_1'}
            />
          </FormColumn>
          <FormColumn
            width={'1/4'}
          >
            <input
              type={'text'}
              className={'gh-input'}
              placeholder={ __( 'Unit/Apartment #', 'groundhogg' ) }
              name={'street_address_2'}
            />
          </FormColumn>
        </FormRow>);
      }

      // city
      // state
      // country
      // zip code
      return (
        <div className={'address-fields'}>
          {fields}
        </div>
      )

    },
  },
  birthday: {
    type: 'birthday',
    name: 'Birthday',
    attributes: ['required', 'label', 'id', 'class'],
  },
  date: {
    type: 'date',
    name: 'Date',
    attributes: [
      'required',
      'label',
      'name',
      'min_date',
      'max_date',
      'showDescription',
      'description',
      'id',
      'class',
    ],
    render: function ({ attributes }) {
      return <InputFieldGroup
        type={ 'date' }
        attributes={ attributes }
        inputProps={ {
          min: attributes.min_date,
          max: attributes.max_date,
        } }
      />
    },
  },
  time: {
    type: 'time',
    name: 'Time',
    attributes: [
      'required',
      'label',
      'name',
      'min_time',
      'max_time',
      'id',
      'class',
    ],
    render: function ({ attributes }) {
      return <InputFieldGroup
        type={ 'time' }
        attributes={ attributes }
        inputProps={ {
          min: attributes.min_time,
          max: attributes.max_time,
        } }
      />
    },
  },
  file: {
    type: 'file',
    name: 'File',
    attributes: [
      'required',
      'label',
      'name',
      'max_file_size',
      'file_types',
      'id',
      'class',
    ],
    render: function ({ attributes }) {
      return <InputFieldGroup
        type={ 'file' }
        attributes={ attributes }
      />
    },
  },
}

const widthMap = {
  '1/1': { className: 'col-1-of-1', value: 1 },
  '1/2': { className: 'col-1-of-2', value: 0.5 },
  '1/3': { className: 'col-1-of-3', value: 1 / 3 },
  '1/4': { className: 'col-1-of-4', value: 0.25 },
  '2/3': { className: 'col-2-of-3', value: 2 / 3 },
  '3/4': { className: 'col-3-of-4', value: 0.75 },
}

function renderField (field) {

  if (!FieldTypes.hasOwnProperty(field.type) ||
    !FieldTypes[field.type].hasOwnProperty('render')) {
    return <div className={ 'not-implemented' }>{ 'not implemented' }</div>
  }

  const input = React.createElement(FieldTypes[field.type].render, {
    attributes: field.attributes || {},
    children: field.children || [],
  })

  return (
    <div key={ field.id } className={ [
      'gh-form-column',
      widthMap[field.width].className,
    ].join(' ') }>
      { input }
    </div>
  )

}

function BasicFieldGroup ({ hideLabel, label, isRequired, children }) {

  if (hideLabel) {
    return <>{ children }</>
  }

  return (
    <label className={ 'gh-input-label' }>
      { label } { isRequired &&
    <span className={ 'is-required' }>*</span> }
      { children }
    </label>
  )
}

/**
 *
 * @param type
 * @param attributes
 * @param inputProps
 * @returns {*}
 * @constructor
 */
function InputFieldGroup ({ type, attributes, inputProps }) {

  attributes = parseArgs(attributes, {
    hideLabel: false,
  })

  const input = <input
    type={ type }
    id={ attributes.id }
    className={ ['gh-input', attributes.class].join(' ') }
    name={ attributes.name }
    value={ attributes.value }
    placeholder={ attributes.placeholder }
    title={ attributes.title }
    required={ attributes.required }
    { ...inputProps }
  />

  if (attributes.hideLabel) {
    return input
  }

  return (
    <>
      <label className={ 'gh-input-label' }>
        { attributes.label } { attributes.required &&
      <span className={ 'is-required' }>*</span> }
        { input }
      </label>
      { attributes.showDescription &&
      <p className={ 'description' }>{ attributes.description }</p> }
    </>
  )
}

const FormRow = ({children}) => {
  return (
    <div className={'gh-form-row'}>
      {children}
      <ClearFix/>
    </div>
  )
}

const FormColumn = ({width, children}) => {
  return (
    <div className={'gh-form-column ' + widthMap[width].className}>
      {children}
    </div>
  )
}

/**
 *
 * @param attributes
 * @param inputProps
 * @returns {*}
 * @constructor
 */
function CheckboxFieldGroup ({ attributes, inputProps }) {

  const input = <input
    type={ 'checkbox' }
    id={ attributes.id }
    className={ ['gh-checkbox', attributes.class].join(' ') }
    name={ attributes.name }
    value={ attributes.value }
    title={ attributes.title }
    required={ attributes.required }
    { ...inputProps }
  />

  return (
    <label
      className={ 'gh-checkbox-label' }>{ input } { attributes.label } { attributes.required &&
    <span className={ 'is-required' }>*</span> }
    </label>
  )
}

InputFieldGroup.defaultProps = {
  type: 'text',
  attributes: {},
}