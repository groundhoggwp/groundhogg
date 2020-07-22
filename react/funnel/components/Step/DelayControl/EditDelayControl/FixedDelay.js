import React, { useState } from 'react';
import { Col, Container, Row } from 'react-bootstrap';
import { DayOfMonthPicker, DayPicker, MonthPicker, RunAt } from './Controls';

export function FixedDelay (props) {

	const updateControl = (name, value) => {
		props.handleInputChange(name, value);
	};

	const runOnTypes = [
		{ value: 'any', label: 'Any day' },
		{ value: 'weekday', label: 'Weekday' },
		{ value: 'weekend', label: 'Weekend' },
		{ value: 'day_of_week', label: 'Day of week' },
		{ value: 'day_of_month', label: 'Day of Month' },
	];

	const runAtTypes = [
		{ value: 'any', label: 'Any time' },
		{ value: 'specific', label: 'Specific time' },
	];

	const intervalTypes = [
		{ value: 'minutes', label: 'Minutes' },
		{ value: 'hours', label: 'Hours' },
		{ value: 'days', label: 'Days' },
		{ value: 'weeks', label: 'Weeks' },
		{ value: 'months', label: 'Months' },
		{ value: 'years', label: 'Years' },
		{ value: 'none', label: 'No delay' },
	];

	return (
		<div className={ 'fixed-delay' }>
			<Container>
				<Row className={'no-padding'}>
					<Col>
						{ 'Wait at least...' }
						<div className={ 'interval-period col-controls' }>
							<input
								name={ 'period' }
								className={ 'period' }
								value={ props.period }
								min={ 1 }
								type={ 'number' }
								disabled={ props.interval === 'none' }
								onChange={ (e) => updateControl(
									'period', e.target.value) }
							/>
							<select
								name={ 'interval' }
								className={ 'interval' }
								value={ props.interval }
								onChange={ (e) => updateControl(
									'interval', e.target.value) }
							>
								{ intervalTypes.map(type => <option
									value={ type.value }>{ type.label }</option>) }
							</select>
						</div>
					</Col>
					<Col xs={ 5 }>
						{ 'Run on ' }
						<select
							name={ 'run_on' }
							className={ 'run-on' }
							value={ props.runOn }
							onChange={ (e) => updateControl('run_on',
								e.target.value) }
						>
							{ runOnTypes.map(type => <option
								value={ type.value }>{ type.label }</option>) }
						</select>
						<div className={ 'col-controls run-on' }>
							{ props.runOn === 'day_of_week' && (
								<>

									<DayPicker
										days={ props.daysOfWeek }
										type={ props.daysOfWeekType }
										updateDelayControl={ updateControl }/>
									<MonthPicker
										type={ props.monthsOfYearType }
										months={ props.monthsOfYear }
										updateDelayControl={ updateControl }
									/>
								</>
							) }
							{ props.runOn === 'day_of_month' && (
								<>

									<DayOfMonthPicker
										days={ props.daysOfMonth }
										updateDelayControl={ updateControl }/>
									<MonthPicker
										type={ props.monthsOfYearType }
										months={ props.monthsOfYear }
										updateDelayControl={ updateControl }/>
								</>
							) }
						</div>
					</Col>
					<Col>
						<RunAt
							time={props.time}
							timeTo={props.timeTo}
							type={props.runAt}
							updateControl={updateControl}
						/>
					</Col>
				</Row>
			</Container>
		</div>
	);
}