import React from 'react';

import { StepControls } from './StepControls/StepControls';
import { StepTitle } from './StepTitle/StepTitle';
import { StepIcon } from './StepIcon/StepIcon';

import './component.scss';

export class Step extends React.Component {

	constructor (props) {
		super(props);

		this.state = {
			showControls: false
		};

		this.handleOnMouseEnter = this.handleOnMouseEnter.bind(this);
		this.handleMouseLeave = this.handleMouseLeave.bind(this);
		this.handleControlAction = this.handleControlAction.bind(this);
	}

	handleOnMouseEnter (e) {
		this.setState({
			showControls: true
		});
	}

	handleMouseLeave (e) {
		this.setState({
			showControls: false
		});
	}

	handleControlAction(key, e){
		console.debug(key, e)
	}

	render () {

		const step = this.props.step;
		const classes = [
			step.group,
			step.type,
			'step',
			'gh-box',
			'round-borders'];

		return (
			<div
				key={ this.props.key }
				className={ classes.join(' ') }
				onMouseEnter={ this.handleOnMouseEnter }
				onMouseLeave={ this.handleMouseLeave }
			>
				<StepIcon type={ step.type } group={ step.group }
				          src={ step.icon }/>
				<StepTitle title={ step.title }/>
				{this.state.showControls && <StepControls handleSelect={this.handleControlAction}/>}
				<div className={'wp-clearfix'}></div>
			</div>
		);
	}

}