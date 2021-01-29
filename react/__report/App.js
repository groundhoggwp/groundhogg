import React from 'react';
import axios from 'axios';

axios.defaults.headers.common['X-WP-Nonce'] = groundhogg_nonces._wprest;

import './master.scss';

function App() {
	return (
		<div className="Groundhogg">
			<h1>Hellom World from the app  </h1>
		</div>
	);
}

export default App;

/**
 * Root event function
 *
 * @param hook
 * @param args
 */
export function dispatchEvent( hook, args ){
	const event = new CustomEvent(hook, { detail: args } );
	document.dispatchEvent(event);
}

/**
 * Root event function
 *
 * @param hook
 * @param callback
 */
export function listenForEvent( hook, callback ){
	document.addEventListener(hook, callback );
}

export function parseArgs (given, defaults) {

	// remove null or empty values from given
	Object.keys(given).forEach((key) => (given[key] == null || given[key] === '') && delete given[key]);

	return {
		...defaults,
		...given,
	};
}

export function uniqId (prefix = '') {
	return prefix + Math.random().toString(36).substring(2, 15) +
		Math.random().toString(36).substring(2, 15);
}

export function objEquals (obj1, obj2) {
	return JSON.stringify(obj1) === JSON.stringify(obj2);
}

// First, checks if it isn't implemented yet.
if (!String.prototype.format) {
	String.prototype.format = function() {
		var args = arguments;
		return this.replace(/{(\d+)}/g, function(match, number) {
			return typeof args[number] != 'undefined'
				? args[number]
				: match
				;
		});
	};
}
