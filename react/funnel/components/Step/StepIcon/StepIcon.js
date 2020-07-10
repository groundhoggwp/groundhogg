import React from 'react';
import './component.scss'

export class StepIcon extends React.Component{

	render () {

		const classes = [ this.props.type + '-icon', this.props.group + '-icon', 'step-icon' ].join( ' ' );

		return (
			<div className="step-icon-wrap">
				<img className={ classes } src={ this.props.src }/>
			</div>
		)
	}

}