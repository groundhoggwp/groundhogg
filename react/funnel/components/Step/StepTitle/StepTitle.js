import React from 'react';
import './component.scss'

export function StepTitle(props) {
	return <span className={'step-title'}>{props.title}</span>;
}