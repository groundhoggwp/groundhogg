import React from 'react';
import './component.scss';
import { fadeIn, fadeOut, slideInRight} from "react-animations";
import Radium, { StyleRoot } from 'radium';

const styles = {
	fadeIn: {
		animation: 'x 750ms',
		animationName: Radium.keyframes(fadeIn, 'fadeIn'),
	},
	fadeOut: {
		animation: 'x 250ms',
		animationName: Radium.keyframes(fadeOut, 'fadeOut'),
	},
	slideInRight: {
		animation: 'x 750ms',
		animationName: Radium.keyframes(slideInRight, 'slideInRight'),
	},
};

export const FadeIn = (props) => {
	return (
		<StyleRoot>
			<div style={styles.fadeIn} className={'animated'}>
				{props.children}
			</div>
		</StyleRoot>
	);
};

export const FadeOut = (props) => {
	return (
		<StyleRoot>
			<div style={styles.fadeOut} className={'animated'} onAnimationEnd={props.then}>
				{props.children}
			</div>
		</StyleRoot>
	);
};

export const SlideInRight = (props) => {
	return (
		<StyleRoot>
			<div style={styles.slideInRight} className={'animated'}>
				{props.children}
			</div>
		</StyleRoot>
	);
};