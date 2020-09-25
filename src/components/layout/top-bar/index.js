import React from 'react'

const {
  bigG,
  logoBlack,
} = groundhogg.assets

import './style.scss'
import { Button, DropdownButton, Dropdown, Form, InputGroup } from 'react-bootstrap'
import { FaIcon } from '../basic-components'

export const TopBar = () => {

  return (
    <div className={ 'groundhogg-top-bar' }>
      <div className={ 'logo' }>
        <img src={ logoBlack } className={ 'logo-black' }/>
      </div>
      <div className={ 'search-and-quick-add' }>
        <div
          className={ 'quick-search filter' }
        >
          <InputGroup>
            <Form.Control
              type={ 'search' }
              size={'lg'}
              placeholder={ 'Search' }
            />
            <DropdownButton
              as={InputGroup.Append}
              variant="outline-secondary"
              title="Dropdown"
              id="input-group-dropdown-2"
            >
              <Dropdown.Item href="#">Action</Dropdown.Item>
              <Dropdown.Item href="#">Another action</Dropdown.Item>
              <Dropdown.Item href="#">Something else here</Dropdown.Item>
              <Dropdown.Divider />
              <Dropdown.Item href="#">Separated link</Dropdown.Item>
            </DropdownButton>
          </InputGroup>
        </div>
      </div>
      <div className={ 'topbar-actions' }>
        <Button variant={ 'outline-dark' } size={ 'lg' }>
          <FaIcon
            classes={ [
              'wordpress',
            ] }
          />
        </Button>
      </div>
    </div>
  )
}