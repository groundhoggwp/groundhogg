import React, { useState } from 'react';
import { Col, Container, Row } from 'react-bootstrap';
import { DayOfMonthPicker, DayPicker, MonthPicker, RunAt } from './Controls';
import moment from 'moment';


export function DateDelay (props) {

	const updateControl = (name, value) => {
		props.handleInputChange(name, value);
	};

	const runOnTypes = [
		{ value: 'specific', label: 'On a specific date' },
		{ value: 'between', label: 'Between' }
	];

	const runOnType = runOnTypes.find(type => type.value === props.runOn ) ? props.runOn : 'specific';

	return (
		<div className={ 'date-delay' }>
			<Container>
				<Row>
					<Col>
						{ 'Run ' }
						<select
							name={ 'run_on' }
							className={ 'run-on' }
							value={ runOnType }
							onChange={ (e) => updateControl('run_on',
								e.target.value) }
						>
							{ runOnTypes.map(type => <option
								value={ type.value }>{ type.label }</option>) }
						</select>
						<div className={ 'run-at col-controls' }>
							{ [ 'specific', 'between' ].includes( runOnType )  &&
							<input
								min={moment().format('YYYY-MM-DD')}
								name={ 'date' }
								type={ 'date' }
								value={ props.date || '' }
								onChange={(e)=>updateControl('date', e.target.value)}
							/> }
						</div>
						<div className={ 'run-at col-controls' }>
							{ [ 'between' ].includes( runOnType )  &&
							<input
								name={ 'date_to' }
								min={props.date}
								type={ 'date' }
								value={ props.dateTo || '' }
								onChange={(e)=>updateControl('date_to', e.target.value)}
							/> }
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