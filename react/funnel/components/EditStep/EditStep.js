import React from 'react';

import { Button, Container, Navbar, Spinner } from 'react-bootstrap';
import { ExitButton } from '../ExitButton/ExitButton';
import { SlideInBarRight } from '../SlideInBarRight/SlideInBarRight';
import { StepControls } from './controls/StepControls';
import axios from 'axios';
import moment from 'moment';
import './component.scss';

export function showEditStepForm (step) {
	const event = new CustomEvent('groundhogg-edit-step',
		{ detail: { step: step } });
	// Dispatch the event.
	document.dispatchEvent(event);
}

function LastEdited ({ date, by }) {

	const theDate = moment(date);

	let text;

	if (theDate.isSame(moment(), 'day')) {
		text = <>{ 'Saved at' } { theDate.format('LT') }</>;
	}
	else {
		text = <>{ 'Saved on' } { theDate.format('LLL') }</>;
	}

	return <span className={ 'last-edited' }>{ text } { 'by' } { by }</span>;
}

export class EditStep extends React.Component {

	constructor (props) {
		super(props);

		this.state = {
			isShowing: false,
			saving: false,
			step: {},
			settings: {},
		};

		this.handleExit = this.handleExit.bind(this);
		this.handleEditStep = this.handleEditStep.bind(this);
		this.updateSetting = this.updateSetting.bind(this);
		this.handleSave = this.handleSave.bind(this);
	}

	handleExit () {
		this.setState({
			isShowing: false,
		});
	}

	updateSetting (name, value) {
		const currentSettings = this.state.settings;
		currentSettings[name] = value;

		this.setState({
			settings: currentSettings,
		});
	}

	handleSave (e) {

		this.setState({ saving: true });

		console.debug(this.state);

		axios.patch(groundhogg_endpoints.steps, {
			step_id: this.state.step.ID,
			settings: this.state.settings,
		}).then(result => this.setState({
			settings: result.data.step.settings,
			step: result.data.step,
			saving: false,
		})).catch(error => this.setState({
			error: error,
			saving: false,
		}));
	}

	handleEditStep (e) {
		axios.get(groundhogg_endpoints.steps + '?step_id=' + e.detail.step.id).
			then(result => this.setState({
				step: result.data.step,
				settings: result.data.step.settings,
				isShowing: true,
			}));
	}

	componentDidMount () {
		document.addEventListener('groundhogg-edit-step', this.handleEditStep);
	}

	render () {

		if (!this.state.isShowing || !this.state.step) {
			return <div className={ 'hidden-step-edit' }></div>;
		}

		const step = this.state.step;
		const settings = this.state.settings;

		return (
			<div className={ 'edit-step' }>
				<SlideInBarRight onOverlayClick={ this.handleExit }>
					<div className={ 'inner' }>
						<Navbar bg="white" expand="sm" fixed="top">
							<Navbar.Brand>
								<div className={'step-title'}>
									{ 'Edit ' }
									<b>{ step.data.step_title }</b>
								</div>
							</Navbar.Brand>
							<Navbar.Toggle
								aria-controls="basic-navbar-nav"/>
							<Navbar.Collapse
								id="edit-nav-bar"
								className="justify-content-end groundhogg-nav"
							>
								<LastEdited by={ step.last_edited_by }
								            date={ step.data.last_edited }/>
								{ !this.state.saving
									? <Button variant={ 'primary' }
									          onClick={ this.handleSave }>{ 'Save' }</Button>
									: <Spinner animation={ 'border' }/> }
							</Navbar.Collapse>
							<ExitButton onExit={ this.handleExit }/>
						</Navbar>
						<div className={ 'step-settings' }>
							<StepControls
								controls={ step.controls }
								initialSettings={ settings }
								update={ this.updateSetting }
							/>
						</div>
					</div>
				</SlideInBarRight>
			</div>
		);
	}
}