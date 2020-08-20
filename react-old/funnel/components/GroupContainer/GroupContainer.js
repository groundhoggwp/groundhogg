import React from 'react';
import { BenchmarkGroup } from '../BenchmarkGroup/BenchmarkGroup';
import { ActionGroup } from '../ActionGroup/ActionGroup';

export class GroupContainer extends React.Component {

	render () {

		const type = this.props.group.type;
		const steps = this.props.group.steps;
		const classes = [ type ].join( ' ' );

		return (
			<div className={classes}>
				{ type === 'action_group' && <ActionGroup id={this.props.group.id} afterReorder={this.props.afterReorder} steps={steps} />}
				{ type === 'benchmark_group' && <BenchmarkGroup id={this.props.group.id} afterReorder={this.props.afterReorder} steps={steps} />}
			</div>
		);

	}

}