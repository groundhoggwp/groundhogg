import React from 'react';
import './component.scss';
import Spinner from 'react-bootstrap/Spinner';
import { StepGroup } from '../StepGroup/StepGroup';
import { AddStep } from '../AddStep/AddStep';

export function showAddStepForm ( group ) {
	console.debug(group);
	const event = new CustomEvent('groundhogg-add-step', { group: group } );
	// Dispatch the event.
	document.dispatchEvent(event);
}

export class Editor extends React.Component {

	constructor (props) {
		super(props);

		this.state = {
			funnel: {},
			steps: [],
			action: 'edit',
			addGroup: 'actions',
		};

		this.handleSetList = this.handleSetList.bind(this);
		this.handleAddStep = this.handleAddStep.bind(this);
	}

	componentDidMount () {

		document.addEventListener('groundhogg-add-step', this.handleAddStep );

		this.setState({
			funnel: ghEditor.funnel,
			steps: ghEditor.funnel.steps,
		});
	}

	handleSetList (newOrder) {
		console.debug({
			newOrder: newOrder,
		});
	}

	/**
	 *
	 * @param type the type of step
	 * @param after
	 */
	addStep (type, after) {

	}

	handleAddStep (e) {

		console.debug(e);

		this.setState({
			action: 'add',
			addGroup: e.group,
		});
	}

	render () {

		if (!this.state.steps.length) {
			return <Spinner animation={ 'border' }/>;
		}

		const inner = [];

		const groups = this.state.steps.reduce(function (prev, curr) {
			if (prev.length && curr.group === prev[prev.length - 1][0].group) {
				prev[prev.length - 1].push(curr);
			}
			else {
				prev.push([curr]);
			}
			return prev;
		}, []);

		const self = this;

		groups.forEach(function (group, i) {
			inner.push(<StepGroup
				steps={ group }
				isFirst={ i === 0 }
				isLast={ i === groups.length - 1 }
				setList={ self.handleSetList }
			/>);
		});

		return (
			<div
				id="groundhogg-funnel-editor"
				className="groundhogg-funnel-editor"
			>
				{ inner }
				{ this.state.action === 'add' &&
				<AddStep group={ this.state.addGroup }/> }
			</div>
		);

	}

}