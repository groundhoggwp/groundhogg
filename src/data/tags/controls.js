/**
 * External dependencies
 */
import { controls as dataControls } from '@wordpress/data-controls';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';

let tagNames = [];
const fetches = {};

export const batchFetch = ( tagName ) => {
	return {
		type: 'BATCH_FETCH',
		tagName,
	};
};

export const controls = {
	...dataControls,
	BATCH_FETCH( { tagName } ) {
		tagNames.push( tagName );

		return new Promise( ( resolve ) => {
			setTimeout( function () {
				const names = tagNames.join( ',' );
				if ( fetches[ names ] ) {
					return fetches[ names ].then( ( result ) => {
						resolve( result[ tagName ] );
					} );
				}

				const url = NAMESPACE + '/tags'; // Could limit this to names.
				fetches[ names ] = apiFetch( { path: url } );
				fetches[ names ].then( ( result ) => resolve( result ) );

				// Clear tag names after all resolved;
				setTimeout( () => {
					tagNames = [];
					// Delete the fetch after to allow wp data to handle cache invalidation.
					delete fetches[ names ];
				}, 1 );
			}, 1 );
		} );
	},
};