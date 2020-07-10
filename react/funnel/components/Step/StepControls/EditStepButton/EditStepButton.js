import React from 'react';
import { Dashicon } from '../../../Dashicon/Dashicon';

import './component.scss';

export class EditStepButton extends React.Component {

	render () {
		return (
			<button className={'edit-step'}>
				<Dashicon icon={'admin-generic'}/>
			</button>
		)
	}
}