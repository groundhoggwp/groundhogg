import React from 'react';
import { Col, Row } from 'react-bootstrap';
import { basicControls } from './basicControls';

export function StepControl ({ control, update, value }) {

	if (typeof basicControls[control.type] === 'undefined') {
		return <div>Control is undefined...</div>;
	}

	const contr0l = React.createElement(basicControls[control.type], {
		id: control.id,
		options: control.options,
		update: update,
		value: value
	});

	return (
		<Row className={'step-setting-control'}>
			<Col sm={ 4 }><label htmlFor={control.id}>{ control.label }</label></Col>
			<Col sm={ 8 }>
				{ contr0l }
				{ control.description && <p className={'description'}>{ control.description }</p> }
			</Col>
		</Row>
	);

}