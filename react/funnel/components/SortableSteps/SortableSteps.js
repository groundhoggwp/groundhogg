import React from 'react';
import { ReactSortable } from 'react-sortablejs';
import { Step } from '../Step/Step';

export class SortableSteps extends React.Component {

	render () {
		return (
			<ReactSortable
				group={this.props.group}
				animation={150}
				list={this.props.steps}
				setList={this.props.setList}
			>
				{ this.props.steps.map(step=><Step key={step.id} step={step}/>) }
			</ReactSortable>
		);
	}

}