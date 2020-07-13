import React from 'react';
import axios from 'axios';
import { Header } from './components/Header/Header';
import {Editor} from './components/Editor/Editor';

axios.defaults.headers.common['X-WP-Nonce'] = groundhogg_nonces._wprest;

import './master.scss';

function App() {
	return (
		<div className="Groundhogg">
			<Header/>
			<Editor/>
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