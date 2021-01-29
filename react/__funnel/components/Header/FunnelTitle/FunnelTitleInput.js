import React, { Component } from 'react';
import axios from 'axios';
import { Spinner } from 'react-bootstrap';
import { TitleInput } from '../../TitleInput';

export class FunnelTitleInput extends Component {

	constructor (props) {
		super(props);

		this.state = {
			isLoading: false,
			title: ghEditor.funnel.title,
			error: false,
		};

		this.changeTitle = this.changeTitle.bind(this);
		this.updateTitle = this.updateTitle.bind(this);
	}

	changeTitle (newTitle) {
		this.setState({ title: newTitle });
	}

	updateTitle (newTitle) {
		axios.patch(groundhogg_endpoints.funnels, {
			funnel_id: ghEditor.funnel.id,
			args: {
				title: newTitle,
			},
		}).then(result => this.setState({
			title: result.data.funnel.title,
			isLoading: false,
		})).catch(error => this.setState({
			error,
			isLoading: false,
		}));
	}

	render () {

		if (this.state.isLoading) {
			return (
				<Spinner animation="border" variant="secondary"/>
			);
		}
		else if (this.state.title === undefined) {
			return <div>Oops!</div>;
		}

		return (
			<TitleInput title={ this.state.title } className="funnel-title"
			            onBlur={ this.updateTitle }
			            onChange={ this.changeTitle }/>
		);
	}

}

