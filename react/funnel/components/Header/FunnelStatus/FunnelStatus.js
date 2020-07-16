import React, { Component } from 'react';
import axios from 'axios';
import ButtonGroup from 'react-bootstrap/ButtonGroup';
import Button from 'react-bootstrap/Button';

import './component.scss';

export class FunnelStatus extends Component {

	constructor (props) {
		super(props);

		this.state = {
			status: ghEditor.funnel.status,
			error: false,
		};

		this.setStateActive = this.setStateActive.bind(this);
		this.setStateInactive = this.setStateInactive.bind(this);
	}

	setStateActive () {
		this.updateStatus('active');
	}

	setStateInactive () {
		this.updateStatus('inactive');
	}

	updateStatus (newStatus) {
		axios.patch(groundhogg_endpoints.funnels, {
			funnel_id: ghEditor.funnel.id,
			args: {
				status: newStatus,
			},
		}).then(result => this.setState({
			status: result.data.funnel.status,
		})).catch(error => this.setState({
			error,
		}));
	}

	render () {

		return (
			<div className={ 'funnel-status' }>
				<ButtonGroup>
					<Button
						onClick={ this.setStateActive }
						variant={ this.state.status === 'active'
							? 'primary'
							: 'outline-primary' }
					>
						{ 'Active' }
					</Button>
					<Button
						onClick={ this.setStateInactive }
						variant={ this.state.status === 'active'
							? 'outline-secondary'
							: 'secondary' }
					>
						{ 'Inactive' }
					</Button>
				</ButtonGroup>
			</div>

		);
	}

}