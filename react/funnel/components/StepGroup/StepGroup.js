import React from 'react';
import './component.scss';
import { SortableSteps } from '../SortableSteps/SortableSteps';
import { GroupControls } from './GroupControls/GroupControls';

const { __, _x, _n, _nx } = wp.i18n;

export class StepGroup extends React.Component {

	constructor (props) {
		super(props);

		this.state = {
			showControls: false,
		};

		this.handleOnMouseEnter = this.handleOnMouseEnter.bind(this);
		this.handleMouseLeave = this.handleMouseLeave.bind(this);
	}

	handleOnMouseEnter (e) {
		this.setState({
			showControls: true,
		});
	}

	handleMouseLeave (e) {
		this.setState({
			showControls: false,
		});
	}

	render () {

		if ( this.props.steps.length === 0 ){
			return <div></div>;
		}

		const groupType = this.props.steps[0].data.step_group;

		const classes = [
			groupType + '-group-container',
			groupType === 'benchmark' ? 'gh-box' : 'clear-box',
		].join(' ');
		const innerClasses = [groupType + '-group'];

		let explanation;

		if (groupType === 'benchmark') {
			explanation = (
				<span className={'group-explanation'}>
					{ this.props.isFirst &&
					__( 'Start the funnel when any of the following benchmarks are triggered...', 'groundhogg' ) }
					{ this.props.isLast &&
					__( 'End the funnel when any of the following benchmarks are triggered...', 'groundhogg' ) }
					{ !this.props.isLast && !this.props.isFirst &&
					__( 'Skip to this point when any of the following benchmarks are triggered...', 'groundhogg' ) }
				</span>
			);
		}

		const steps = this.props.steps;
		const lastStep = steps[steps.length-1];

		return (
			<div className={ classes }
			     onMouseEnter={ this.handleOnMouseEnter }
			     onMouseLeave={ this.handleMouseLeave }
			>
				{ groupType === 'benchmark' && <div className="explanation">{ explanation }</div> }
				<div className={ innerClasses }>
					<SortableSteps
						steps={ this.props.steps }
					    group={ groupType }
					/>
				</div>
				<GroupControls group={groupType} after={lastStep.ID}/>
			</div>
		);
	}

}