import React, { Component } from 'react';

import CreatableSelect from 'react-select/creatable';

const components = {
	DropdownIndicator: null,
};

const createOption = (label) => ({
	label: label,
	value: label,
});

export default class CreatableInputOnly extends React.Component {
	state = {
		inputValue: '',
		value: [],
	};
	handleChange = (value, actionMeta) => {
		console.group('Value Changed');
		console.log(value);
		console.log(`action: ${actionMeta.action}`);
		console.groupEnd();
		this.setState({ value });
	};
	handleInputChange = (inputValue) => {
		this.setState({ inputValue });
	};
	handleKeyDown = (event) => {
		const { inputValue, value } = this.state;
		if (!inputValue) return;
		switch (event.key) {
			case 'Enter':
			case 'Tab':
				console.group('Value Added');
				console.log(value);
				console.groupEnd();
				this.setState({
					inputValue: '',
					value: [...value, createOption(inputValue)],
				});
				event.preventDefault();
		}
	};
	render() {
		const { inputValue, value } = this.state;
		return (
			<CreatableSelect
				components={components}
				inputValue={inputValue}
				isClearable
				isMulti
				menuIsOpen={false}
				onChange={this.handleChange}
				onInputChange={this.handleInputChange}
				onKeyDown={this.handleKeyDown}
				placeholder="Type something and press enter..."
				value={value}
			/>
		);
	}
}