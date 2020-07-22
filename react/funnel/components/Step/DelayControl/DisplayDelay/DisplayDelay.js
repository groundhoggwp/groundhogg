import React, { Fragment } from 'react';
import moment from 'moment';
import { ItemsCommaOrList } from '../../../BasicControls/basicControls';
import { parseArgs } from '../../../../App';

const { __, _x, _n, _nx } = wp.i18n;

function Highlight ({children}) {
	return <span className={'gh-highlight'}>{children}</span>
}

function wrapInSpace (text) {
	return text.padStart( text.length + 1 ).padEnd( text.length + 2 );
}

function TimeDelayFragment ({ delay }) {

	const text = [];

	const at = wrapInSpace( _x( 'at', 'step delay', 'groundhogg' ) );
	const and = wrapInSpace( _x( 'and', 'step delay', 'groundhogg' ) );
	const between = wrapInSpace( _x( 'between', 'step delay', 'groundhogg' ) );
	const anyTime = _x( 'any time', 'step delay', 'groundhogg' );

	switch (delay.run_at) {
		default:
		case 'any':
			text.push( at, <Highlight>{ anyTime }</Highlight>);
			break;
		case 'specific':
			text.push( at, <Highlight>{ moment(delay.time, 'HH:mm').
				format('hh:mm a') }</Highlight>);
			break;
		case 'between':
			text.push(between, <Highlight>{ moment(delay.time, 'HH:mm').
				format('LT') }</Highlight>, and, <Highlight>{ moment(delay.time_to,
				'HH:mm').format('LT') }</Highlight>);
			break;

	}

	return <>{ text.map( (item,i) => <Fragment key={i}>{item}</Fragment>) }</>;
}

function FixedDelay ({ delay }) {

	const text = [];

	delay = parseArgs( delay, {
		period: 0,
		interval: 'none',
		run_on: 'any',
		days_of_week: [],
		months_of_year: []
	} );

	if (delay.interval !== 'none') {
		text.push('Wait at least ', <Highlight>{ delay.period } { delay.interval }</Highlight>);
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
				text.push(' on a ', <Highlight>{ 'weekday' }</Highlight>);
				break;
			case 'weekend':
				text.push(' on a ', <Highlight>{ 'weekend' }</Highlight>);
				break;
			case 'day_of_week':

				text.push(delay.days_of_week_type === 'any'
					? ' on '
					: ( <>{ ' on the ' }
						<Highlight>{ delay.days_of_week_type }</Highlight> </> ),
				);

				const days = delay.days_of_week;

				text.push(<Highlight><ItemsCommaOrList
					items={ delay.days_of_week.map( item=>item.label ) }
				/></Highlight>);

				if (delay.months_of_year_type !== 'any') {
					text.push(' of ', <Highlight><ItemsCommaOrList
						items={ delay.months_of_year.map( item=>item.label ) }
					/></Highlight>);
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

	return <>{ text.map( (item,i) => <Fragment key={i}>{item}</Fragment>) }</>;
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

	return <>{ text.map( (item,i) => <Fragment key={i}>{item}</Fragment>) }</>;
}

export function DisplayDelay (props) {

	const delay = props.delay;

	let timeDisplay;

	switch (delay.type) {
		default:
		case 'instant':
			timeDisplay = __( 'Run immediately...', 'groundhogg' );
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