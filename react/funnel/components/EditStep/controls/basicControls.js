import React, { useEffect, useState } from 'react';
import AsyncCreatableSelect from 'react-select/async-creatable';
import CreatableSelect from 'react-select/creatable';
import axios from 'axios';
import { Button } from 'react-bootstrap';
import { Dashicon } from '../../Dashicon/Dashicon';
import ButtonGroup from 'react-bootstrap/ButtonGroup';
import { ReplacementsButton } from '../../ReplacementsButton/ReplacementsButton';

import './component.scss';

export const basicControls = {
	text: Text,
	textarea: TextArea,
	number: Number,
	tag_picker: TagPicker,
	email_picker: EmailPicker,
	email_address_picker: EmailAddressPicker,
	email_address: EmailAddress,
	yes_no_toggle: YesNoToggle,
};

function Text ({ id, options, update, value }) {
	return <input id={ id } className={ 'form-control' }
	              onChange={ event => update(id, event.target.value) }
	              value={ value } { ...options }/>;
}

function TagPicker ({ id, options, update, value }) {

	const [tagValues, setTagValues] = useState([]);
	const [loaded, setLoaded] = useState(false);

	const promiseOptions = inputValue => new Promise(resolve => {
		axios.get(groundhogg_endpoints.tags + '?axios=1&q=' + inputValue).
			then(result => {
				!loaded && setTagValues(
					result.data.tags.filter(
						tag => value.includes(parseInt(tag.value))));
				setLoaded(true);
				resolve(result.data.tags);
			});
	});

	return (
		<AsyncCreatableSelect
			id={ id }
			cacheOptions
			defaultOptions
			isMulti
			isClearable
			ignoreCase={ true }
			loadOptions={ promiseOptions }
			onChange={ value => {
				update(id, value.map(tag => tag.value));
				setTagValues(value);
			} }
			value={ tagValues }
			{ ...options }
		/>
	);
}

function validateEmail(email) {
	const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(email);
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

	const [inputValue,setInputValue] = useState('');

	const values = value && value.split( ',' ).map( function ( item ) {
		return { value: item, label: item }
	} );

	const addedEmail = values => {
		update(id, values && values.map(value => value.value ).join(',') );
	};

	const handleInputChange = (inputValue) => {
		setInputValue(inputValue)
	};

	const handleKeyDown = (event) => {
		if (!inputValue) return;
		switch (event.key) {
			case 'Enter':
			case 'Tab':

				if ( validateEmail(inputValue) ){
					setInputValue('');
					addedEmail( [ ...values, {
						label: inputValue,
						value: inputValue,
					}]);
				}
		}
	};

	const components = {
		DropdownIndicator: null,
	};

	return (
		<CreatableSelect
			components={components}
			isClearable
			isMulti
			menuIsOpen={false}
			onChange={addedEmail}
			onKeyDown={handleKeyDown}
			onInputChange={handleInputChange}
			placeholder={'Type an email address...'}
			inputValue={inputValue}
			value={values}
		/>
	);
}

function EmailAddress ({ id, options, update, value }) {

	const [valid, setValid] = useState(true);
	const [useDefault, setUseDefault] = useState(true);

	const handleOnChange = e => {
		setUseDefault(false);

		if ( validateEmail( e.target.value ) ){
			setValid(true);
		} else {
			setValid(false)
		}

		update(id, e.target.value );
	};

	const classes = [
		'email-address',
		valid ? 'valid' : 'invalid'
	];

	value = ! value && useDefault ? options.default : value;

	return (
		<input
			id={id}
			type={'email'}
			onChange={handleOnChange}
			className={classes.join( ' ' )}
			value={value}
		/>
	)

}

function EmailPicker ({ id, options, update, value }) {

	const [emailValue, setEmailValue] = useState({});
	const [loaded, setLoaded] = useState(false);

	const promiseOptions = inputValue => new Promise(resolve => {
		axios.get(
			groundhogg_endpoints.emails + '?selectReact=1&q=' + inputValue).
			then(result => {
				!loaded && setEmailValue(
					result.data.emails.find(
						email => parseInt(value) === parseInt(email.value)));
				setLoaded(true);
				resolve(result.data.emails);
			});
	});

	return (
		<>
			<AsyncCreatableSelect
				id={ id }
				cacheOptions
				defaultOptions
				isClearable
				ignoreCase={ true }
				loadOptions={ promiseOptions }
				onChange={ value => {
					update(id, value ? value.value : false, true);
					setEmailValue(value);
				} }
				value={ emailValue }
				{ ...options }
			/>
			<div className={ 'btn-control-group' }>
				<Button variant="outline-primary"><Dashicon
					icon={ 'edit' }/> { 'Edit Email' }</Button>
				<Button variant="outline-secondary"><Dashicon
					icon={ 'plus' }/> { 'Create New Email' }</Button>
			</div>
		</>
	);

}

function YesNoToggle ({ id, options, update, value }) {

	return (
		<div className={ 'yes-no-toggle' }>
			<ButtonGroup>
				<Button
					onClick={ e => update(id, true) }
					variant={ value ? 'primary' : 'outline-primary' }
				>
					{ options.yes || 'Yes' }
				</Button>
				<Button
					onClick={ e => update(id, false) }
					variant={ !value ? 'secondary' : 'outline-secondary' }
				>
					{ options.no || 'No' }
				</Button>
			</ButtonGroup>
		</div>

	);

}

/**
 * Number
 *
 * @param props
 * @constructor
 */
function Number (props) {

}

function ClearFix () {
	return <div className={'wp-clearfix'}></div>
}

/**
 * Textarea
 *
 * @param id
 * @param options
 * @param update
 * @param value
 * @returns {*}
 * @constructor
 */
function TextArea ({ id, options, update, value }) {

	return (
		<div className={ 'textarea-control-wrap' }>
			{ options.has_replacements && <div className={'replacements-wrap'}>
				<ReplacementsButton insertTargetId={id}/>
				<ClearFix/>
			</div>}
			<textarea
				id={ id }
				onChange={ e => update(id, e.target.value) }
				value={ value }
			/>
		</div>

	);

}

