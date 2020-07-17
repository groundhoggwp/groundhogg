import React from 'react';
import moment from 'moment';
import { ItemsCommaOrList } from '../../../BasicControls/basicControls';

function TimeDelayFragment ({ delay }) {

	const text = [];

	switch (delay.run_at) {
		default:
		case 'any':
			text.push(' at ', <b>{ 'any time' }</b>);
			break;
		case 'specific':
			text.push(' at ', <b>{ moment(delay.time, 'HH:mm').
				format('hh:mm a') }</b>);
			break;
		case 'between':
			text.push(' between ', <b>{ moment(delay.time, 'HH:mm').
				format('LT') }</b>, ' and ', <b>{ moment(delay.time_to,
				'HH:mm').format('LT') }</b>);
			break;

	}

	return <>{ text }</>;
}

function FixedDelay ({ delay }) {

	const text = [];

	if (delay.interval !== 'none') {
		text.push('Wait at least ', <b>{ delay.period } { delay.interval }</b>);
	}

	if (delay.run_on !== 'any') {

		if (!text.length) {
			text.push('Run');
		}
		else {
			text.push(' then run');
		}

		switch (delay.run_on) {
			case 'weekday':
				text.push(' on a ', <b>{ 'weekday' }</b>);
				break;
			case 'weekend':
				text.push(' on a ', <b>{ 'weekend' }</b>);
				break;
			case 'day_of_week':

				text.push(delay.days_of_week_type === 'any'
					? ' on any '
					: ( <>{ ' on the' }
						<b>{ delay.days_of_week_type }</b> </> ),
				);

				const days = delay.days_of_week;

				text.push(<ItemsCommaOrList items={ delay.days_of_week.map( item=>item.label ) }/>);

				if (delay.months_of_year_type !== 'any') {
					text.push(' of ', <ItemsCommaOrList
						items={ delay.months_of_year }/>);
				}

				break;
			case 'day_of_month':

				text.push(' on the ');

				text.push(<ItemsCommaOrList items={ delay.days_of_month.map( item=>item.label ) }/>);

				if (delay.months_of_year_type !== 'any') {
					text.push(' of ', <ItemsCommaOrList
						items={ delay.months_of_year.map( item=>item.label ) }/>);
				}

				break;
		}

	}

	if (!text.length) {
		text.push('Run');
	}
	else if (delay.run_on === 'any') {
		text.push(' then run');
	}

	text.push(<TimeDelayFragment delay={ delay }/>);

	return <>{ text }</>;
}

function DateDelay ({ delay }) {

	const text = [];

	switch (delay.run_on) {
		default:
		case 'specific':
			text.push('Run on ', <b>{ moment(delay.date, 'YYYY-MM-DD').
				format('LL') }</b>);
			break;
		case 'between':
			text.push('Run between ', <b>{ moment(delay.date, 'YYYY-MM-DD').
				format('LL') }</b>, ' and ', <b>{ moment(delay.date_to,
				'YYYY-MM-DD').format('LL') }</b>);
			break;
	}

	text.push(<TimeDelayFragment delay={ delay }/>);

	return <>{ text }</>;
}

export function DisplayDelay (props) {

	const delay = props.delay;

	let timeDisplay;

	switch (delay.type) {
		default:
		case 'instant':
			timeDisplay = 'Run immediately...';
			break;
		case 'fixed':
			timeDisplay = <FixedDelay delay={ delay }/>;
			break;
		case 'date':
			timeDisplay = <DateDelay delay={ delay }/>;
			break;
		case 'dynamic':
			timeDisplay = 'Wait until the contact\'s {0} then run at {2}...'.format(
				delay.field, delay.time);
			break;
	}

	return <>{ timeDisplay }</>;
}