import React, { useState } from 'react';
import { Col, Row } from 'react-bootstrap';
import { basicControls } from './basicControls';

export function StepControl ({ control, update, value }) {

	const [show,setShow] = useState(true);

	let contr0l;

	if (typeof basicControls[control.type] === 'undefined') {
		contr0l = <div>Control is undefined...</div>;
	} else {
		contr0l = React.createElement(basicControls[control.type], {
			id: control.id,
			options: control.options,
			update: update,
			value: value
		});

	}


	return (
		<Row className={'step-setting-control'}>
			<Col sm={ 4 }>
				<label htmlFor={control.id}>{ control.label }</label>
				{ control.description && <p className={'description'}>{ control.description }</p> }
			</Col>
			<Col sm={ 8 }>
				{ contr0l }
			</Col>
		</Row>
	);

}