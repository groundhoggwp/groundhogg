import React from 'react';
import { FadeIn, SlideInRight } from '../Animations/Animations';

import './component.scss';

export class SlideInBarRight extends React.Component {

    constructor (props) {
        super(props);
    }

    render () {

        return (
            <div className={ 'slide-in-bar-right' }>
                <FadeIn>
                    <div className={ 'overlay' } onClick={this.props.onOverlayClick}></div>
                </FadeIn>
                <div className={ 'container' }>
                    <SlideInRight>
                        <div className={ 'content' }
                        >
                            {this.props.children}
                        </div>
                    </SlideInRight>
                </div>
            </div>
        );
    }
};