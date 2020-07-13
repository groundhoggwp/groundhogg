import React from 'react';
import Select from 'react-select';
import { Col } from 'react-bootstrap';

export function DayPicker (props) {

	const days = props.days;
	const type = props.type !== undefined ? props.type : 'any';

	const updateDays = newDays => {
		props.updateDelayControl('days_of_week', newDays);
	};

	const updateType = event => {
		props.updateDelayControl('days_of_week_type', event.target.value);
	};

	const daysOfWeek = [
		{ value: 'monday', label: 'Monday' },
		{ value: 'tuesday', label: 'Tuesday' },
		{ value: 'wednesday', label: 'Wednesday' },
		{ value: 'thursday', label: 'Thursday' },
		{ value: 'friday', label: 'Friday' },
		{ value: 'saturday', label: 'Saturday' },
		{ value: 'sunday', label: 'Sunday' },
	];

	const daysOfWeekTypes = [
		{ value: 'any', label: 'Any' },
		{ value: 'first', label: 'First' },
		{ value: 'second', label: 'Second' },
		{ value: 'third', label: 'Third' },
		{ value: 'fourth', label: 'Fourth' },
		{ value: 'last', label: 'Last' },
	];

	return ( <>
			<select
				className={ 'control' }
				name={ 'days_of_week_type' }
				value={ type }
				onChange={ updateType }
			>
				{ daysOfWeekTypes.map(type => <option
					key={type.value}
					value={ type.value }>{ type.label }</option>) }
			</select>
			<Select
				className={ 'control' }
				options={ daysOfWeek }
				onChange={ updateDays }
				isMulti={ true }
				name={ 'days_of_week' }
				value={ days }
			/>
		</>
	);
}

export function DayOfMonthPicker (props) {

	const days = props.days;

	const updateDays = newMonths => {
		props.updateDelayControl('days_of_month', newMonths);
	};

	const daysOfMonth = [];

	for (let i = 1; i <= 31; i++) {
		daysOfMonth.push({ value: i, label: i });
	}

	daysOfMonth.push({ value: 'last', label: 'Last' });

	return ( <>
			<Select
				className={ 'control' }
				options={ daysOfMonth }
				onChange={ updateDays }
				isMulti={ true }
				name={ 'days_of_month' }
				value={ days }
			/>
		</>
	);
}

export function MonthPicker (props) {

	const months = props.months;
	const type = props.type !== undefined ? props.type : 'any';

	const updateMonths = newMonths => {
		props.updateDelayControl('months_of_year', newMonths);
	};

	const updateType = event => {
		props.updateDelayControl('months_of_year_type', event.target.value);
	};

	const monthsOfYear = [
		{ value: 'january', label: 'January' },
		{ value: 'february', label: 'February' },
		{ value: 'march', label: 'March' },
		{ value: 'april', label: 'April' },
		{ value: 'may', label: 'May' },
		{ value: 'june', label: 'June' },
		{ value: 'july', label: 'July' },
		{ value: 'august', label: 'August' },
		{ value: 'september', label: 'September' },
		{ value: 'november', label: 'November' },
		{ value: 'december', label: 'December' },
	];

	const monthsOfYearTypes = [
		{ value: 'any', label: 'Any month' },
		{ value: 'specific', label: 'Specific month(s)' },
	];

	return ( <>
			<select
				className={ 'control' }
				name={ 'months_of_year_type' }
				value={ type }
				onChange={ updateType }
			>
				{ monthsOfYearTypes.map(type => <option
					key={type.value}
					value={ type.value }>{ type.label }</option>) }
			</select>
			{ type === 'specific' &&
			<Select
				className={ 'control' }
				options={ monthsOfYear }
				onChange={ updateMonths }
				isMulti={ true }
				isSearchable={ true }
				name={ 'months_of_year' }
				value={ months }
			/>
			}
		</>
	);
}

export function RunAt (props) {

	const time = props.time;
	const timeTo = props.timeTo;
	const type = props.type !== undefined ? props.type : 'any';

	const updateTime = event => {
		props.updateControl('time',  event.target.value);
	};

	const updateTimeTo = event => {
		props.updateControl('time_to',  event.target.value);
	};

	const updateType = event => {
		props.updateControl('run_at', event.target.value);
	};

	const runAtTypes = [
		{ value: 'any', label: 'Any time' },
		{ value: 'specific', label: 'Specific time' },
		{ value: 'between', label: 'Between' },
	];

	return (
		<>
			{ 'Run at ' }
			<select
				name={ 'run_at' }
				className={ 'run-at' }
				value={ type }
				onChange={updateType}
			>
				{ runAtTypes.map(type => <option
					key={type.value}
					value={ type.value }
				>
					{ type.label }
				</option>) }
			</select>
			<div className={ 'run-at col-controls' }>
				{ [ 'specific', 'between' ].includes( type ) &&
				<input
					name={ 'time' }
					type={ 'time' }
					value={ time || '' }
					onChange={updateTime}
				/> }
			</div>

			<div className={ 'run-at col-controls' }>
				{ [ 'between' ].includes( type ) &&
				<input
					name={ 'time_to' }
					min={time}
					type={ 'time' }
					value={ timeTo || '' }
					onChange={updateTimeTo}
				/> }
			</div>
		</>
	);
}