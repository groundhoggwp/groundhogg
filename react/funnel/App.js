import React from 'react';
import axios from 'axios';
import { Header } from './components/Header/Header';
import {Editor} from './components/Editor/Editor';

axios.defaults.headers.common['X-WP-Nonce'] = groundhogg_nonces._wprest;

import './style.css';

function App() {
	return (
		<div className="Groundhogg">
			<Header/>
			<Editor/>
		</div>
	);
}

export default App;
