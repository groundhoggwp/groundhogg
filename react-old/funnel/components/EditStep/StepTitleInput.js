import React, { Component } from 'react';
import axios from 'axios';
import { Spinner } from 'react-bootstrap';
import { TitleInput } from '../TitleInput';
import { reloadEditor } from '../Editor/Editor';

export class StepTitleInput extends Component {

	constructor (props) {
		super(props);

		this.state = {
			isLoading: false,
			title: props.title,
		};

		this.changeTitle = this.changeTitle.bind(this);
		this.updateTitle = this.updateTitle.bind(this);
	}

	changeTitle (newTitle) {
		this.setState({ title: newTitle });
	}

	updateTitle (newTitle) {
		axios.patch(groundhogg_endpoints.steps, {
			step_id: this.props.stepId,
			args: {
				step_title: newTitle,
			},
		}).then(result => {
				this.setState({
					title: result.data.step.data.step_title,
				});
				reloadEditor();
			},
		).catch(error => this.setState({
			error,
		}));
	}

	render () {

		return (
			<TitleInput title={ this.state.title } className="step-title"
			            onBlur={ this.updateTitle }
			            onChange={ this.changeTitle }/>
		);
	}

}