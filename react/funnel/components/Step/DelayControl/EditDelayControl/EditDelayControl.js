import React from 'react';
import { Form, FormControl, InputGroup } from 'react-bootstrap';
import Button from 'react-bootstrap/Button';
import { GroundhoggModal } from '../../../Modal/Modal';
import { FixedDelay } from './FixedDelay';

import './component.scss';
import { DateDelay } from './DateDelay';
import { DisplayDelay } from '../DisplayDelay/DisplayDelay';
import { Dashicon } from '../../../Dashicon/Dashicon';

export class EditDelayControl extends React.Component {

	constructor (props) {
		super(props);

		this.state = props.delay;

		this.handleTypeChange = this.handleTypeChange.bind(this);
		this.handleDone = this.handleDone.bind(this);
		this.handleCancel = this.handleCancel.bind(this);
		this.handleDelayControlUpdated = this.handleDelayControlUpdated.bind(this);
	}

	handleTypeChange (e) {
		this.setState({
			type: e.target.value,
		});
	}

	/**
	 * Update a control value
	 *
	 * @param name
	 * @param value
	 */
	handleDelayControlUpdated (name, value) {
		this.setState({
			[name]: value,
		});
	}

	handleDone (e) {
		this.props.save( this.state );
	}

	handleCancel(e) {
		this.setState(this.props.delay);
		this.props.cancel()
	}

	render () {

		const controls = [];

		switch (this.state.type) {
			case 'fixed':
				controls.push(
					<FixedDelay
						handleInputChange={ this.handleDelayControlUpdated }
						period={ this.state.period }
						interval={ this.state.interval }
						runOn={ this.state.run_on }
						daysOfWeek={ this.state.days_of_week }
						daysOfWeekType={ this.state.days_of_week_type }
						daysOfMonth={ this.state.days_of_month }
						monthsOfYear={ this.state.months_of_year }
						monthsOfYearType={ this.state.months_of_year_type }
						runAt={ this.state.run_at }
						time={ this.state.time }
						timeTo={ this.state.time_to }
					/>,
				);

				break;
			case 'date':
				controls.push(
					<DateDelay
						handleInputChange={ this.handleDelayControlUpdated }
						runOn={ this.state.run_on }
						date={ this.state.date }
						dateTo={ this.state.date_to }
						runAt={ this.state.run_at }
						time={ this.state.time }
						timeTo={ this.state.time_to }
					/>,
				);
				break;
			case 'dynamic':
				// timeDisplay = "Wait until the contact's {0} then run at
				// {2}...".format(step.delay.field, step.delay.time);
				break;
		}

		const delayTypes = [
			{ value: 'instant', label: 'Instant' },
			{ value: 'fixed', label: 'Fixed delay' },
			{ value: 'date', label: 'Date delay' },
			{ value: 'dynamic', label: 'Dynamic delay' },
		];

		return (
			<GroundhoggModal
				show={ this.props.show }
				heading={ 'Edit step delay' }
				onSave={ this.handleDone }
				onHide={ this.handleCancel }
				closeText={ 'Save' }
			>
				<div className={ 'edit-delay-controls' }>
					<div className={ 'delay-text'}>
						<Dashicon icon={'clock'}/> <DisplayDelay delay={this.state}/>
					</div>
					<div className={ 'delay-type' }>
						{ 'What kind of delay is this? ' }
						<select
							name={ 'type' }
							value={ this.state.type }
							onChange={ this.handleTypeChange }
						>
							{ delayTypes.map(type => <option
								value={ type.value }>{ type.label }</option>) }
						</select>
					</div>
					{ controls.length > 0 &&
					<div className={ 'edit-delay-controls-inner' }>
						{ controls }
					</div>
					}
				</div>
			</GroundhoggModal>
		);
	}

}