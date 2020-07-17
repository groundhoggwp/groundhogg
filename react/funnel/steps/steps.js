import React, { useState } from 'react';
import {
	EmailPicker,
	ItemsCommaAndList,
	TagPicker, YesNoToggle,
} from '../components/BasicControls/basicControls';
import Modal from 'react-bootstrap/Modal';
import Button from 'react-bootstrap/Button';
import { Col, Row } from 'react-bootstrap';

export const Steps = [];

/**
 * Register a new step type through the step type API
 *
 * @param type string
 * @param attributes object
 */
export function registerStepType (type, attributes) {

	if (attributes.title === 'undefined') {
		attributes.title = ({ data }) => {
			return data.title;
		};
	}

	Steps[type] = attributes;
}

export function StepTitle ({ type, data, settings }) {
	let contr0l;

	if (typeof Steps[type] === 'undefined') {
		contr0l = <div>{ data.step_title || type }</div>;
	}
	else {
		contr0l = React.createElement(Steps[type].title, {
			data: data,
			settings: settings,
		});
	}

	return contr0l;
}

StepTitle.defaultProps = {
	data: {},
	settings: {},
	type: '',
};

export function StepEdit ({ type, data, settings, updateSettings, commit, done }) {

	let contr0l;

	if (typeof Steps[type] === 'undefined') {

		// alert('This step has not been implemented yet...');

		return <></>
	}
	else {
		contr0l = React.createElement(Steps[type].edit, {
			data: data,
			settings: settings,
			updateSettings: updateSettings,
			commit: commit,
			done: done,
		});
	}

	return contr0l;

}

StepTitle.StepEdit = {
	type: '',
	data: {},
	settings: {},
	updateSettings: function(){},
	commit: function(){},
	done: function(){},
};


registerStepType('apply_tag', {

	icon: ghEditor.steps.apply_tag.icon,
	group: ghEditor.steps.apply_tag.group,

	title: ({ data, settings }) => {

		if (!settings || !settings.tags_display ||
			!settings.tags_display.length) {
			return <>{ 'Select tags to add...' }</>;
		}

		return <>{ 'Apply' } <ItemsCommaAndList
			items={ settings.tags_display.map(tag => tag.label) }/></>;
	},

	edit: ({ data, settings, updateSettings, commit, done }) => {

		const tagsChanged = (values) => {
			updateSettings({
				tags: values.map(tag => tag.value),
				tags_display: values,
			});
		};

		const saveAndClose = () => {
			commit();
			done();
		};

		return (
			<Modal
				size="md"
				aria-labelledby="contained-modal-title-vcenter"
				className={ 'tag-picker' }
				centered
				show={ true }
				onHide={ done }
			>
				<Modal.Header closeButton>
					<Modal.Title id="contained-modal-title-vcenter">
						{ 'Apply tags...' }
					</Modal.Title>
				</Modal.Header>
				<Modal.Body>
					<TagPicker id={ 'tags' }
					           value={ ( settings && settings.tags_display ) ||
					           false }
					           update={ tagsChanged }/>
					<p className={ 'description' }>{ 'Add new tags by hitting [enter] or [tab]' }</p>
				</Modal.Body>
				<Modal.Footer>
					<Button onClick={ done }
					        variant={ 'secondary' }>{ 'Cancel' }</Button>
					<Button onClick={ saveAndClose }
					        variant={ 'primary' }>{ 'Save & Close' }</Button>
				</Modal.Footer>
			</Modal>
		);
	},

});

registerStepType('remove_tag', {

	icon: ghEditor.steps.remove_tag.icon,
	group: ghEditor.steps.remove_tag.group,

	title: ({ data, settings }) => {

		if (!settings || !settings.tags_display ||
			!settings.tags_display.length) {
			return <>{ 'Select tags to remove...' }</>;
		}

		return <>{ 'Remove' } <ItemsCommaAndList
			items={ settings.tags_display.map(tag => tag.label) }/></>;
	},

	edit: ({ data, settings, updateSettings, commit, done }) => {

		const tagsChanged = (values) => {
			updateSettings({
				tags: values.map(tag => tag.value),
				tags_display: values,
			});
		};

		const saveAndClose = () => {
			commit();
			done();
		};

		return (
			<Modal
				size="md"
				aria-labelledby="contained-modal-title-vcenter"
				className={ 'tag-picker' }
				centered
				show={ true }
				onHide={ done }
			>
				<Modal.Header closeButton>
					<Modal.Title id="contained-modal-title-vcenter">
						{ 'Remove tags...' }
					</Modal.Title>
				</Modal.Header>
				<Modal.Body>
					<TagPicker id={ 'tags' }
					           value={ ( settings && settings.tags_display ) ||
					           false }
					           update={ tagsChanged }/>
					<p className={ 'description' }>{ 'Add new tags by hitting [enter] or [tab]' }</p>
				</Modal.Body>
				<Modal.Footer>
					<Button onClick={ done }
					        variant={ 'secondary' }>{ 'Cancel' }</Button>
					<Button onClick={ saveAndClose }
					        variant={ 'primary' }>{ 'Save & Close' }</Button>
				</Modal.Footer>
			</Modal>
		);
	},

});

registerStepType('send_email', {

	icon: ghEditor.steps.send_email.icon,
	group: ghEditor.steps.send_email.group,

	title: ({ data, settings }) => {

		if (!settings || !settings.email_display) {
			return <>{ 'Select and email to send...' }</>;
		}

		return <>{ 'Send' } <b>{ settings.email_display.label }</b></>;
	},

	edit: ({ data, settings, updateSettings, commit, done }) => {

		const emailChanged = (value) => {
			updateSettings({
				email_display: value,
				email_id: value.value,
			});
		};

		const saveAndClose = () => {
			commit();
			done();
		};

		return (
			<Modal
				size="md"
				aria-labelledby="contained-modal-title-vcenter"
				className={ 'email-picker' }
				centered
				show={ true }
				onHide={ done }
			>
				<Modal.Header closeButton>
					<Modal.Title id="contained-modal-title-vcenter">
						{ 'Send email...' }
					</Modal.Title>
				</Modal.Header>
				<Modal.Body>
					<EmailPicker id={ 'email' } value={ ( settings &&
						settings.email_display ) || false }
					             update={ emailChanged }/>
				</Modal.Body>
				<Modal.Footer>
					<Button onClick={ done }
					        variant={ 'secondary' }>{ 'Cancel' }</Button>
					<Button onClick={ saveAndClose }
					        variant={ 'primary' }>{ 'Save & Close' }</Button>
				</Modal.Footer>
			</Modal>
		);
	},

});

registerStepType('email_confirmed', {

	icon: ghEditor.steps.email_confirmed.icon,
	group: ghEditor.steps.email_confirmed.group,

	title: ({ data, settings }) => {
		return 'Email confirmed';
	},

	edit: ({ data, settings, updateSettings, commit, done }) => {

		const valueChanged = (value) => {
			updateSettings({
				skip_to: value,
			});
		};

		const saveAndClose = () => {
			commit();
			done();
		};

		return (
			<Modal
				size="md"
				aria-labelledby="contained-modal-title-vcenter"
				centered
				show={ true }
				onHide={ done }
			>
				<Modal.Header closeButton>
					<Modal.Title id="contained-modal-title-vcenter">
						{ 'Send email...' }
					</Modal.Title>
				</Modal.Header>
				<Modal.Body>
					<Row className={ 'step-setting-control' }>
						<Col sm={ 8 }>
							<label>{ 'Skip to here if already confirmed?' }</label>
						</Col>
						<Col sm={ 4 }>
							<YesNoToggle
								value={ settings.skip_to }
								update={ valueChanged }
							/>
						</Col>
					</Row>
					<p className={ 'description' }>{ 'If the contact enters this funnel, but their email address has already been confirmed, automatically skip to this point.' }</p>
				</Modal.Body>
				<Modal.Footer>
					<Button onClick={ done }
					        variant={ 'secondary' }>{ 'Cancel' }</Button>
					<Button onClick={ saveAndClose }
					        variant={ 'primary' }>{ 'Save & Close' }</Button>
				</Modal.Footer>
			</Modal>
		);
	},

});
