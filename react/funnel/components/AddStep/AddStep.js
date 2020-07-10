import React from 'react';
import { AddStepControl } from './AddStepControl/AddStepControl';
import Spinner from 'react-bootstrap/Spinner';

import './component.scss';
import { Navbar } from 'react-bootstrap';
import { ExitButton } from '../ExitButton/ExitButton';
import { FadeIn, SlideInRight } from '../Animations/Animations';

export class AddStep extends React.Component {

	constructor (props) {
		super(props);

		this.state = {
			steps: [],
			isLoading: false,
		};
	}

	componentDidMount () {
		this.setState({
			steps: this.props.group === 'actions'
				? ghEditor.groups.actions
				: ghEditor.groups.benchmarks,
		});
	}

	render () {

		if (!this.state.steps.length) {
			return <Spinner animation={ 'border' }/>;
		}

		return (
			<div className={ 'add-new-step-container' }>
				<div className={ 'add-new-step-overlay' }></div>
				<div className={ 'add-new-step-inner-container' }>
					<SlideInRight>
						<div className={ 'add-new-step-inner' }
						>
							<Navbar bg="white" expand="sm" fixed="top">
								<Navbar.Brand>
									{ 'Add Step' }
								</Navbar.Brand>
								<Navbar.Toggle
									aria-controls="basic-navbar-nav"/>
								<ExitButton/>
							</Navbar>
							<div className={ 'add-new-step-choices' }>
								{ this.state.steps.map(
									step => <AddStepControl step={ step }/>) }
							</div>
						</div>
					</SlideInRight>
				</div>
			</div>
		);
	}
};