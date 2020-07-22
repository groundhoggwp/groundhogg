import React, { useState } from 'react';
import { Dashicon } from '../../Dashicon/Dashicon';
import { EditDelayControl } from './EditDelayControl/EditDelayControl';
import { DisplayDelay } from './DisplayDelay/DisplayDelay';

export function DelayControl ({ delay, updateDelay }) {

	const [show, setShow] = useState(false);
	const [tempDelay, setTempDelay] = useState(delay);

	const updateTempDelay = (newTempDelay) => {
		setTempDelay({
			...tempDelay,
			...newTempDelay
		});
	};

	const saveChanges = () => {
		updateDelay(tempDelay);
		setShow(false);
	};

	const cancelChanges = () => {
		setTempDelay(delay);
		setShow(false);
	};

	return (
		<div className={ 'delay' }>
			<Dashicon icon={ 'clock' }/>
			<span className={ 'delay-text' } onClick={ () => setShow(true) }>
                <DisplayDelay delay={ delay }/>
            </span>
			<EditDelayControl
				show={ show }
				delay={ tempDelay }
				updateDelay={ updateTempDelay }
				save={ saveChanges }
				cancel={ cancelChanges }/>
		</div>
	);

}