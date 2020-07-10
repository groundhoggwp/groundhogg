import React from 'react';
import { Dashicon } from '../../../Dashicon/Dashicon';
import Button from 'react-bootstrap/Button';
import { showAddStepForm } from '../../../Editor/Editor';

export class AddStep extends React.Component {

	constructor (props) {
		super(props);

		this.handleOnClick = this.handleOnClick.bind(this);
	}

	handleOnClick(e){
		console.debug( e );
		showAddStepForm( this.props.group )
	}

	render () {
		return (
			<Button
				variant="outline-secondary"
			    size="sm"
				onClick={this.handleOnClick}
			>
				<Dashicon icon={ 'plus' }/>
				{ this.props.group === 'action'
					? ' Add Action'
					: ' Add Benchmark' }
			</Button>
		);
	}

}