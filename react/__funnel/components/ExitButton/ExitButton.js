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

		this.props.onExit();
	}

	render () {
		return (
			<div className="groundhogg-exit-button">
				{
					!this.state.exiting &&
					<button className="exit-link" onClick={this.handleClick}>
						<span className="dashicons dashicons-no"></span>
					</button> }
				{
					this.state.exiting &&
					<Spinner animation="border" variant="secondary"/>
				}
			</div>
		);
	}

}