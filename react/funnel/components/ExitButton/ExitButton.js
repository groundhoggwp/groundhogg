import React from 'react';
import { Spinner } from 'react-bootstrap';
import './component.scss';

export class ExitButton extends React.Component {

	constructor (props){
		super(props);

		this.state = {
			exiting: false
		};

		this.handleClick = this.handleClick.bind(this);
	}

	handleClick (e) {
		this.setState({
			exiting: true,
		});
	}

	render () {
		return (
			<div className="groundhogg-exit-button">
				{
					!this.state.exiting &&
					<a href={ ghEditor.exit } className="exit-link" onClick={this.handleClick}>
						<span className="dashicons dashicons-no"></span>
					</a> }
				{
					this.state.exiting &&
					<Spinner animation="border" variant="secondary"/>
				}
			</div>
		);
	}

}