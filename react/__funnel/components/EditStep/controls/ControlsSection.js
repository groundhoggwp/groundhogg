import React from "react";
import {StepControls} from "./StepControls";

export function ControlsSection({section, settings, update}) {

    return (
        <div id={section.id} className={'controls-section'}>
            <div className={'controls-section-title'}>
                <h4>{section.label}</h4>
            </div>
            <StepControls
                controls={ section.controls }
                initialSettings={ settings }
                update={ update }
            />
        </div>
    )
}