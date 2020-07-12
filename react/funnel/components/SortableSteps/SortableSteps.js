import React from 'react';
import { ReactSortable } from 'react-sortablejs';
import { Step } from '../Step/Step';

export class SortableSteps extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			steps: props.steps
		};

		this.handleSetList = this.handleSetList.bind(this);
		this.handleOnEnd = this.handleOnEnd.bind(this);
	}

	handleSetList(newSteps){
		this.setState({ steps: newSteps} );
	}

	handleOnEnd(e){
		const event = new CustomEvent( 'groundhogg-steps-sorted' );
		// Dispatch the event.
		document.dispatchEvent(event);
	}

	componentWillReceiveProps(nextProps, nextContext) {
		this.setState({steps:nextProps.steps});
	}

	render () {
		return (
			<ReactSortable
				// group={this.props.group}
				group={'steps'}
				animation={150}
				list={this.state.steps}
				setList={this.handleSetList}
				onEnd={this.handleOnEnd}
				handle={'.sortable-handle'}
				// scroll={true}
				// scrollSensitivity={100}
				swapThreshold={0.75}
				forceFallback={true}
			>
				{ this.state.steps.map(step=><Step key={step.id} step={step}/>) }
			</ReactSortable>
		);
	}

}