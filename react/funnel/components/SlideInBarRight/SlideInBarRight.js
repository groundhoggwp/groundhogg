import React, { useState } from 'react';
import { FadeIn, SlideInRight } from '../Animations/Animations';

import './component.scss';

export function SlideInBarRight ({show, onOverlayClick, children}) {

    return (
        <div className={ 'slide-in-bar-right' }>
            <FadeIn>
                <div className={ [ 'modal-backdrop', show ? 'show' : 'hide', 'fade' ].join( ' ' ) } onClick={onOverlayClick}></div>
            </FadeIn>
            <div className={ 'container' }>
                <SlideInRight>
                    <div className={ 'content' }
                    >
                        {children}
                    </div>
                </SlideInRight>
            </div>
        </div>
    );
}