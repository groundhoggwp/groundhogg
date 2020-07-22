import React from 'react';
import { Dashicon } from '../../Dashicon/Dashicon';
import { EditDelayControl } from './EditDelayControl/EditDelayControl';
import { DisplayDelay } from './DisplayDelay/DisplayDelay';
import axios from 'axios';

export class DelayControl extends React.Component {

	constructor (props) {
		super(props);

		this.state = {
			editing: false,
			delay: props.step.delay,
		};

		this.handleClick = this.handleClick.bind(this);
		this.cancelEditing = this.cancelEditing.bind(this);
		this.doneEditing = this.doneEditing.bind(this);
	}

	handleClick (e) {
		this.setState({
			editing: true,
		});
	}

	cancelEditing () {
		this.setState({
			editing: false,
		});
	}

	doneEditing (delay) {
		axios.patch(groundhogg_endpoints.steps, {
			step_id: this.props.step.ID,
			delay: delay,
		}).then(result => this.setState({
			delay: result.data.step.delay,
			editing: false,
		})).catch(error => this.setState({
			error: error,
			editing: false,
		}));
	}

	render () {
		const delay = this.state.delay;

		return (
			<div className={ 'delay' }>
				<Dashicon icon={ 'clock' }/>
				<span className={ 'delay-text' } onClick={ this.handleClick }>
                    <DisplayDelay delay={ delay }/>
                </span>
				<EditDelayControl
					show={ this.state.editing }
					delay={ delay }
					save={ this.doneEditing }
					cancel={ this.cancelEditing }/>
			</div>
		);
	}

}