import React, { useState } from 'react';
import { registerStepType, SimpleEditModal } from '../steps';
import { Col, Row, Tab, Tabs } from 'react-bootstrap';
import {
	CopyInput,
	LinkPicker,
	TextArea,
	YesNoToggle,
} from '../../components/BasicControls/basicControls';

import '../../../../assets/css/frontend/form.css';
import { ReactSortable } from 'react-sortablejs';

registerStepType('form_fill', {

	icon: ghEditor.steps.form_fill.icon,
	group: ghEditor.steps.form_fill.group,

	title: ({ data, context, settings }) => {
		return <>{ 'When' } <b>{ settings.form_name }</b> { 'is filled' }</>;
	},

	edit: ({ data, context, settings, updateSettings, commit, done }) => {

		const updateSetting = (name, value) => {
			updateSettings({
				[name]: value,
			});
		};

		return (
			<SimpleEditModal
				title={ 'Form Filled...' }
				done={ done }
				commit={ commit }
				modalProps={ {
					size: 'lg',
				} }
				modalBodyProps={ {
					className: 'no-padding',
				} }
			>
				<Tabs defaultActiveKey="form">
					<Tab eventKey={ 'form' } title={ 'Form' }>
						<FormBuilder
							formJSON={ context.as_json }
						/>
					</Tab>
					<Tab eventKey={ 'submit' } title={ 'Submit' }>
						<Row className={ 'step-setting-control' }>
							<Col sm={ 4 }>
								<label>{ 'Stay on page after submit?' }</label>
								<p className={ 'description' }>{ 'This will prevent the contact from being redirected after submitting the form.' }</p>
							</Col>
							<Col sm={ 8 }>
								<YesNoToggle
									value={ settings.enable_ajax }
									update={ (v) => updateSetting('enable_ajax',
										v) }
								/>
							</Col>
						</Row>
						{ settings.enable_ajax && <Row>
							<Col sm={ 4 }>
								<label>{ 'Success message' }</label>
								<p className={ 'description' }>{ 'Message displayed when the contact submits the form.' }</p>
							</Col>
							<Col sm={ 8 }>
								<TextArea
									id={ 'success_message' }
									value={ settings.success_message }
									hasReplacements={ true }
									update={ (v) => updateSetting(
										'success_message', v) }/>
							</Col>
						</Row> }
						{ !settings.enable_ajax && <Row>
							<Col sm={ 4 }>
								<label>{ 'Success page' }</label>
								<p className={ 'description' }>{ 'Where the contact will be directed upon submitting the form.' }</p>
							</Col>
							<Col sm={ 8 }>
								<LinkPicker value={ settings.success_page }
								            update={ (v) => updateSetting(
									            'success_page', v) }/>
							</Col>
						</Row> }
					</Tab>
					<Tab eventKey={ 'embed' } title={ 'Embed' }>
						<Row className={ 'step-setting-control' }>
							<Col sm={ 4 }>
								<label>{ 'Shortcode' }</label>
								<p className={ 'description' }>{ 'Insert anywhere WordPress shortcodes are accepted.' }</p>
							</Col>
							<Col sm={ 8 }>
								<CopyInput
									content={ context.embed.shortcode }
								/>
							</Col>
						</Row>
						<Row className={ 'step-setting-control' }>
							<Col sm={ 4 }>
								<label>{ 'iFrame' }</label>
								<p className={ 'description' }>{ 'For use when embedding forms on none WordPress sites.' }</p>
							</Col>
							<Col sm={ 8 }>
								<CopyInput
									content={ context.embed.iframe }
								/>
							</Col>
						</Row>
						<Row className={ 'step-setting-control' }>
							<Col sm={ 4 }>
								<label>{ 'Raw HTML' }</label>
								<p className={ 'description' }>{ 'For use when embedding forms on none WordPress sites and HTML web form integrations (Thrive).' }</p>
							</Col>
							<Col sm={ 8 }>
								<CopyInput
									content={ context.embed.html }
								/>
							</Col>
						</Row>
						<Row className={ 'step-setting-control' }>
							<Col sm={ 4 }>
								<label>{ 'Hosted URL' }</label>
								<p className={ 'description' }>{ 'Direct link to the web form.' }</p>
							</Col>
							<Col sm={ 8 }>
								<CopyInput
									content={ context.embed.hosted }
								/>
							</Col>
						</Row>
					</Tab>
				</Tabs>
			</SimpleEditModal>
		);
	},
});

function prettifyForm (form) {

	form = form.trim().
		replace(/(\])\s*(\[)/gm, '$1$2').
		replace(/(\])/gm, '$1\n');
	// form = form.trim().replace(/(\])\s*(\[)/gm, "$1$2").replace(/(\])/gm,
	// "$1\n");
	let codes = form.split('\n');

	console.debug(codes);

	let depth = 0;
	let pretty = '';

	codes.forEach(function (shortcode, i) {

		shortcode = shortcode.trim();

		if (!shortcode) {
			return;
		}

		if (shortcode.match(/\[(col|row)\b[^\]]*\]/)) {
			pretty += ' '.repeat(4).repeat(depth) + shortcode;
			depth++;
		}
		else if (shortcode.match(/\[\/(col|row)\]/)) {
			depth--;
			pretty += ' '.repeat(4).repeat(depth) + shortcode;
		}
		else {
			pretty += ' '.repeat(4).repeat(depth) + shortcode;
		}

		pretty += '\n';
	});

	return pretty;

}

function FormBuilder ({ formJSON }) {



	return (
		<Row className={ 'no-margins no-padding' }>
			<Col className={ 'no-padding' }>
				<div className={ 'form-builder-wrap' }>
					<ReactSortable>
						{ formJSON.map((field) => renderField(field)) }
					</ReactSortable>
				</div>
			</Col>
		</Row>
	);

}

function InputFieldGroup ({ type, attributes, inputProps }) {

	attributes = parseArgs(attributes, {
		showLabel: true,
	});

	const input = <input
		type={ type }
		id={ attributes.id }
		className={ ['gh-input', attributes.class].join(' ') }
		name={ attributes.name }
		value={ attributes.value }
		placeholder={ attributes.value }
		title={ attributes.title }
		required={ attributes.required }
		disabled={ true }
		{ ...inputProps }
	/>;

	if (attributes.showLabel) {
		return (
			<label className={ 'gh-input-label' }>
				{ attributes.label }
				{ input }
			</label>
		);
	}

	return input;
}

InputFieldGroup.defaultProps = {
	type: 'text',
	attributes: {},
};

function parseArgs (given, defaults) {
	return {
		...defaults,
		...given,
	};
}

function renderField (field) {
	return React.createElement(FieldTypes[field.type].render, {
		attributes: field.attributes || {},
		children: field.children || [],
	});
}

const FieldTypes = {
	row: {
		shortcode: 'row',
		name: 'Row',
		attributes: ['id', 'class'],
		render: function ({ attributes, children }) {
			return (
				<div
					id={ attributes.id }
					className={ [
						'gh-form-row',
						'clearfix',
						attributes.class,
					].join(' ') }
				>
					{ children.map((field) => renderField(field)) }
				</div> );
		},
	},
	col: {
		shortcode: 'col',
		name: 'Col',
		attributes: ['id', 'class'],
		render: function ({ attributes, children }) {

			let width = attributes.width;

			const widthMap = {
				'1/1': 'col-1-of-1',
				'1/2': 'col-1-of-2',
				'1/3': 'col-1-of-3',
				'1/4': 'col-1-of-4',
				'2/3': 'col-2-of-3',
				'3/4': 'col-3-of-4',
			};

			return <div id={ attributes.id } className={ [
				'gh-form-column',
				widthMap[width],
				attributes.class,
			].join(' ') }>
				{ children.map((field) => renderField(field)) }
			</div>;
		},
	},
	first: {
		shortcode: 'first',
		name: 'First',
		attributes: ['required', 'label', 'placeholder', 'id', 'class'],
		render: function ({ attributes }) {

			attributes = parseArgs(attributes, {
				name: 'first_name',
				label: 'First Name',
				required: true,
			});

			return <InputFieldGroup
				type={ 'text' }
				attributes={ attributes }
			/>;
		},
	},
	last: {
		shortcode: 'last',
		name: 'Last',
		attributes: ['required', 'label', 'placeholder', 'id', 'class'],
		render: function ({ attributes }) {

			attributes = parseArgs(attributes, {
				name: 'last_name',
				label: 'Last Name',
				required: true,
			});

			return <InputFieldGroup
				type={ 'text' }
				attributes={ attributes }
			/>;
		},
	},
	email: {
		shortcode: 'email',
		name: 'Email',
		attributes: ['label', 'placeholder', 'id', 'class'],
		render: function ({ attributes }) {

			attributes = parseArgs(attributes, {
				name: 'email',
				label: 'Email',
				required: true,
			});

			return <InputFieldGroup
				type={ 'email' }
				attributes={ attributes }
			/>;
		},
	},
	phone: {
		shortcode: 'phone',
		name: 'Phone',
		attributes: ['required', 'label', 'placeholder', 'id', 'class'],
		render: function ({ attributes }) {

			attributes = parseArgs(attributes, {
				name: 'primary_phone',
				label: 'Phone',
				required: true,
			});

			return <InputFieldGroup
				type={ 'tel' }
				attributes={ attributes }
			/>;
		},
	},
	gdpr: {
		shortcode: 'gdpr',
		name: 'GDPR',
		attributes: ['label', 'tag', 'id', 'class'],
	},
	terms: {
		shortcode: 'terms',
		name: 'Terms',
		attributes: ['label', 'tag', 'id', 'class'],
	},
	recaptcha: {
		shortcode: 'recaptcha',
		name: 'reCaptcha',
		attributes: ['captcha-theme', 'captcha-size', 'id', 'class'],
	},
	submit: {
		shortcode: 'submit',
		name: 'Submit',
		attributes: ['text', 'id', 'class'],
		render: function ({ attributes }) {

			attributes = parseArgs(attributes, {
				text: 'Submit',
			});

			return ( <div className={ 'gh-button-wrapper' }>
				<button type={ 'submit' } id={ attributes.id }
				        className={ ['gh-submit-button', attributes.class].join(
					        ' ') }>
					{ attributes.text }
				</button>
			</div> );
		},
	},
	text: {
		shortcode: 'text',
		name: 'Text',
		attributes: [
			'required',
			'label',
			'placeholder',
			'name',
			'id',
			'class',
		],
	},
	textarea: {
		shortcode: 'textarea',
		name: 'Textarea',
		attributes: [
			'required',
			'label',
			'placeholder',
			'name',
			'id',
			'class',
		],
	},
	number: {
		shortcode: 'number',
		name: 'Number',
		attributes: [
			'required',
			'label',
			'name',
			'min',
			'max',
			'id',
			'class',
		],
	},
	dropdown: {
		shortcode: 'dropdown',
		name: 'Dropdown',
		attributes: [
			'required',
			'label',
			'name',
			'default',
			'options',
			'multiple',
			'id',
			'class',
		],
	},
	radio: {
		shortcode: 'radio',
		name: 'Radio',
		attributes: ['required', 'label', 'name', 'options', 'id', 'class'],
	},
	checkbox: {
		shortcode: 'checkbox',
		name: 'Checkbox',
		attributes: [
			'required',
			'label',
			'name',
			'value',
			'tag',
			'id',
			'class',
		],
	},
	address: {
		shortcode: 'address',
		name: 'Address',
		attributes: ['required', 'label', 'id', 'class'],
	},
	birthday: {
		shortcode: 'birthday',
		name: 'Birthday',
		attributes: ['required', 'label', 'id', 'class'],
	},
	date: {
		shortcode: 'date',
		name: 'Date',
		attributes: [
			'required',
			'label',
			'name',
			'min_date',
			'max_date',
			'id',
			'class',
		],
	},
	time: {
		shortcode: 'time',
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
	},
	file: {
		shortcode: 'file',
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
	},
};

const fieldAttributes = {
	required: {
		edit: ({ value, updateAttribute }) => {
			return (
				<Row className={ 'field-attribute-control' }>
					<Col sm={ 4 }>
						<label>{ 'Is this field required?' }</label>
					</Col>
					<Col sm={ 8 }>
						<YesNoToggle
							value={ value }
							update={ (value) => updateAttribute('required',
								value) }
						/>
					</Col>
				</Row>
			);
		},
	},
	hideLabel: {
		edit: ({ value, updateAttribute }) => {
			return (
				<Row className={ 'field-attribute-control' }>
					<Col sm={ 4 }>
						<label>{ 'Hide field label?' }</label>
					</Col>
					<Col sm={ 8 }>
						<YesNoToggle
							value={ value }
							update={ (value) => updateAttribute('hideLabel',
								value) }
						/>
					</Col>
				</Row>
			);
		},
	},
	id: {
		edit: ({ value, updateAttribute }) => {
			return (
				<Row className={ 'field-attribute-control' }>
					<Col sm={ 4 }>
						<label>{ 'CSS ID' }</label>
					</Col>
					<Col sm={ 8 }>
						<input
							value={ value }
							onChange={ (e) => updateAttribute('ID', value) }
						/>
					</Col>
				</Row>
			);
		},
	},
	class: {
		edit: ({ value, updateAttribute }) => {
			return (
				<Row className={ 'field-attribute-control' }>
					<Col sm={ 4 }>
						<label>{ 'CSS Class' }</label>
					</Col>
					<Col sm={ 8 }>
						<input
							value={ value }
							onChange={ (e) => updateAttribute('class', value) }
						/>
					</Col>
				</Row>
			);
		},
	},
};

function FieldBuilder ({ shortcode, attributes, onBuild }) {

	const [field, setField] = useState({});

	const updateAttribute = (id, value) => {
		setField({
			[id]: value,
		});
	};

}