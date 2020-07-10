import React from 'react';
import { Dropdown } from 'react-bootstrap';
import SplitButton from 'react-bootstrap/SplitButton';
import { FadeIn } from '../../Animations/Animations';
import { Dashicon } from '../../Dashicon/Dashicon';

import './component.scss';

export const StepControls = (props) => {
	return (
		<div className={ 'step-controls' }>
			<FadeIn>
				<SplitButton
					id={ 'step-controls' }
					variant={ 'secondary' }
					title={ 'Edit' }
					size={ 'sm' }
					onSelect={ props.handleSelect }
				>
					<Dropdown.Item eventKey="1"><Dashicon
						icon={ 'edit' }/> { 'Edit' }</Dropdown.Item>
					<Dropdown.Item eventKey="2"><Dashicon
						icon={ 'admin-page' }/> { 'Duplicate' }</Dropdown.Item>
					<Dropdown.Divider/>
					<Dropdown.Item eventKey="3" className={ 'text-danger' }>
						<Dashicon icon={ 'trash' }/> { 'Delete' }
					</Dropdown.Item>
					<Dropdown.Divider/>
					<Dropdown.Item eventKey="4"><Dashicon
						icon={ 'plus' }/> { 'Insert Action Below' }
					</Dropdown.Item>
					<Dropdown.Item eventKey="5"><Dashicon
						icon={ 'flag' }/> { 'Insert Benchmark Below' }
					</Dropdown.Item>
				</SplitButton>
			</FadeIn>
		</div>
	);
};