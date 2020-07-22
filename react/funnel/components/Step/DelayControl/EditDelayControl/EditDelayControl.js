import React, { Fragment } from 'react';
import { GroundhoggModal } from '../../../Modal/Modal';
import { FixedDelay } from './FixedDelay';

import './component.scss';
import { DateDelay } from './DateDelay';
import { DisplayDelay } from '../DisplayDelay/DisplayDelay';
import { Dashicon } from '../../../Dashicon/Dashicon';

export function EditDelayControl ({ show, delay, updateDelay, save, cancel }) {

	const handleInputChange = (name, value) => {
		updateDelay({[name]:value});
	};

	const controls = [];
	const delayType = delay.type;

	switch (delayType) {
		case 'fixed':

			controls.push(
				<FixedDelay
					handleInputChange={handleInputChange}
					period={ delay.period }
					interval={ delay.interval }
					runOn={ delay.run_on }
					daysOfWeek={ delay.days_of_week }
					daysOfWeekType={ delay.days_of_week_type }
					daysOfMonth={ delay.days_of_month }
					monthsOfYear={ delay.months_of_year }
					monthsOfYearType={ delay.months_of_year_type }
					runAt={ delay.run_at }
					time={ delay.time }
					timeTo={ delay.time_to }
				/>,
			);

			break;
		case 'date':
			controls.push(
				<DateDelay
					handleInputChange={handleInputChange}
					runOn={ delay.run_on }
					date={ delay.date }
					dateTo={ delay.date_to }
					runAt={ delay.run_at }
					time={ delay.time }
					timeTo={ delay.time_to }
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
			show={ show }
			heading={ 'Edit step delay' }
			onSave={ save }
			onHide={ cancel }
			closeText={ 'Save' }
		>
			<div className={ 'edit-delay-controls' }>
				<div className={ 'delay-text' }>
					<Dashicon icon={ 'clock' }/> <DisplayDelay
					delay={ delay }/>
				</div>
				<div className={ 'delay-type' }>
					{ 'What kind of delay is this? ' }
					<select
						name={ 'type' }
						value={ delayType }
						onChange={(e) => handleInputChange('type', e.target.value)}
					>
						{ delayTypes.map(type => <option
							key={ type.value }
							value={ type.value }>{ type.label }</option>) }
					</select>
				</div>
				{ controls.length > 0 &&
				<div className={ 'edit-delay-controls-inner' }>
					{ controls.map((item, i) => <Fragment
						key={ i }>{ item }</Fragment>) }
				</div>
				}
			</div>
		</GroundhoggModal>
	);
}