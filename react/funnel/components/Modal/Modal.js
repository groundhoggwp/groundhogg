import React from 'react';
import Modal from 'react-bootstrap/Modal';
import Button from 'react-bootstrap/Button';

import './component.scss';

export function GroundhoggModal(props) {
	return (
		<Modal
			{...props}
			size="lg"
			aria-labelledby="contained-modal-title-vcenter"
			bsPrefix={'groundhogg modal'}
			centered
		>
			<Modal.Header closeButton>
				<Modal.Title id="contained-modal-title-vcenter">
					{props.heading}
				</Modal.Title>
			</Modal.Header>
			<Modal.Body className={'no-padding'}>
				{props.children}
			</Modal.Body>
			<Modal.Footer>
				<Button onClick={props.onSave}>{props.closeText}</Button>
			</Modal.Footer>
		</Modal>
	);
}