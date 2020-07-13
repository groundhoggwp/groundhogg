import React from 'react';
import './component.scss';
import { SortableSteps } from '../SortableSteps/SortableSteps';
import { GroupControls } from './GroupControls/GroupControls';

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

		const groupType = this.props.steps[0].group;

		const classes = [
			groupType + '-group-container',
			groupType === 'benchmark' ? 'gh-box' : 'clear-box',
		].join(' ');
		const innerClasses = [groupType + '-group'];

		let explanation;

		if (groupType === 'action') {
			explanation = 'Then do the following...';
		}
		else {
			explanation = (
				<span>
					{ this.props.isFirst &&
					'Start the funnel when the following benchmarks are triggered...' }
					{ this.props.isLast &&
					'End the funnel when the following benchmarks are triggered...' }
					{ !this.props.isLast && !this.props.isFirst &&
					'Skip to this point when the following benchmarks are triggered...' }
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
				{/*{ this.state.showControls && <GroupControls group={groupType} after={lastStep.id}/> }*/}
				<GroupControls group={groupType} after={lastStep.id}/>
			</div>
		);
	}

}