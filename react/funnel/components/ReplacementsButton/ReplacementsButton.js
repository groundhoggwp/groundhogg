import React, { useState } from 'react';
import { Dashicon } from '../Dashicon/Dashicon';
import { Dropdown, FormControl } from 'react-bootstrap';

import './component.scss';

function insertAtCursor(myFieldId, myValue) {

	const myField = document.getElementById(myFieldId);

	//IE support
	let sel;

	if (document.selection) {
		myField.focus();
		sel = document.selection.createRange();
		sel.text = myValue;
	}
	//MOZILLA and others
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		myField.value = myField.value.substring(0, startPos)
			+ myValue
			+ myField.value.substring(endPos, myField.value.length);
	} else {
		myField.value += myValue;
	}

	return myField.value;
}

export function ReplacementsButton({insertTargetId, onInsert}) {

	const [value, setValue] = useState('');

	const codes = ghEditor.replacements;

	const onSelect = (key,e) => {
		// console.debug(key,e);

		let newVal = insertAtCursor(insertTargetId,'{' + key + '}');

		if ( typeof onInsert === 'function' ){
			onInsert(newVal);
		}
	};

	return (
		<Dropdown
			onSelect={onSelect}
		>
			<Dropdown.Toggle variant="outline-primary"
			                 className={ 'replacements' }>
				<Dashicon icon={ 'admin-users' }/> { 'Replacements' }
			</Dropdown.Toggle>
			<Dropdown.Menu className={'replacements'}>
				<FormControl
					autoFocus
					className="mx-3 my-2 w-auto"
					placeholder={ 'Type to filter...' }
					onChange={ (e) => setValue(e.target.value) }
					value={ value }
				/>
				{ codes.filter(code => code.code.includes(value) ||
					code.name.includes(value)).
					map(code => <Dropdown.Item
						key={ code.code }
						eventKey={ code.code }>
						{ code.name }
					</Dropdown.Item>) }
			</Dropdown.Menu>
		</Dropdown>
	);

}