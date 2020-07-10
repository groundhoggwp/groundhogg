import React from 'react';
import './component.scss';

export class AddStepControl extends React.Component {

	render () {

		const step = this.props.step;
		const classes = [ 'add-step-control', 'gh-box', 'round-borders', step.type, step.group ].join( ' ' );

		return (
			<div className={ classes }>
				<img className={ 'step-icon' } src={ step.icon }/>
				<div className={ 'details' }>
					<h3 className={ 'step-name' }>{ step.name }</h3>
					<p className={ 'description' }>{ step.description }</p>
				</div>
			</div>
		);
	}

}