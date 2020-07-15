import React, {useState} from "react";
import {StepControl} from "./StepControl";

export function StepControls({controls, initialSettings, update}) {

    return (
        <>
            {controls.map(control => <StepControl
				control={control}
				update={update}
				value={initialSettings[control.id] || ""}
			/>)}
        </>
    );

}