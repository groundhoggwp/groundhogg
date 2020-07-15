import React from 'react';
import './component.scss'

export function StepIcon({type, group, src}) {
	const classes = [ type + '-icon', group + '-icon', 'step-icon' ].join( ' ' );
	const wrapperClasses = [ type + '-icon-wrap', group + '-icon-wrap', 'step-icon-wrap' ].join( ' ' );

	return (
		<div className={wrapperClasses}>
			<img className={ classes } src={ src }/>
		</div>
	)
}