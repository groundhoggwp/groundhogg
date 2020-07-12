import React from 'react';
import './component.scss';
import { AddStep } from './AddStep/AddStep';
import { FadeIn } from '../../Animations/Animations';

export const GroupControls = (props) => {

	const classes = [props.group + '-controls', 'group-controls'].join( ' ' );

	return (
		<div className={ classes }>
			<FadeIn>
				<AddStep group={props.group} after={props.after}/>
			</FadeIn>
		</div>
	);
};