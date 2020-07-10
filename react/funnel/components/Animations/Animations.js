import React from 'react';
import ReactCSSTransitionGroup from 'react-addons-css-transition-group';
import './component.scss';
import { bounce, slideInRight } from 'react-animations';
import Radium, { StyleRoot } from 'radium';

const styles = {
	bounce: {
		animation: 'x 1s',
		animationName: Radium.keyframes(bounce, 'bounce'),
	},
	slideInRight: {
		animation: 'x 1s',
		animationName: Radium.keyframes(slideInRight, 'slideInRight'),
	},
};

export const FadeIn = (props) => {
	return (
		<ReactCSSTransitionGroup
			transitionName="fadeIn"
			transitionAppear={ true }
			transitionAppearTimeout={ 100 }
			transitionEnter={ false }
			transitionLeave={ false }
		>
			{ props.children }
		</ReactCSSTransitionGroup>
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