Data Stores
=======

When creating a custom add-on for Groundhogg, it's very likely you'll want to build a your own REST API Endpoint as well as your own deeply integrated UX within Groundhogg. One of the key concepts within Groundhogg's new React-based UX is data stores. Within WordPress, Gutenberg, and now Groundhogg, data stores are utilized to provide coherent management of the application state, access server-side data, and keep data concerns separated while still remaining easily accesible amongst themselves.

## Extending Data Store

There are some core concepts that it's helpful to understand when building out a custom data store in Groundhogg. If you are already familiar with React's Redux package, or even custom data stores within Gutenberg, much of this will feel familiar to you. The remainder of this documentation assumes you are _not_ already familiar with these concepts.

There are five key concepts within data stores that are vital to understand. These aren't the _only_ concepts worth understanding, but we consider them to be the most vital. It would be impossible to dive as deeply into the concept of data stores within WordPress as we could here, so for further reading, we unequivocally recommend [this excellent resource](https://unfoldingneurons.com/series/practical-overview-of-wp-data).

* Reducers - The only _required_ parameter for registering a store, your reducer function describes the _shape_ of your state and *how it changes* in response to actions dispatched to your store.
* Actions - An action object is essentially instructions for how the reducer should make changes to the state in the store.
* Selectors - Selectors provide an interface to access state from the registered data store. They are basically your window into retrieving what slice of state you need from the store.
* Resolvers - Resolvers allow you to retreive data from the server. Names of resolver functions must map to the names of selector functions. When a selector function is accessed from your store, the resolver function is mapped internally to it, creating a clear connection between the server, the state, and the application.
* Controls - A control (or control function) defines the execution flow behavior associated with a specific action type. Your control function should map directly to your control action type.

If you've read the concepts above and feel sufficiently lost and unsure where to begin, we understand! A simple example, modified from [core documentation](https://developer.wordpress.org/block-editor/packages/packages-data/), may be helpful.

```js
const { apiFetch } = wp.dataControls; // import { apiFetch } from '@wordpress/data-controls'
const { registerStore } = wp.data; // import { registerStore } from '@wordpress/data'

// Defined here so we can access in our resolvers. In actual use, you may likely end up exporting these actions in their own file, and importing them in your resolver file. See our core implementation.
const actions = {
    setPrice( item, price ) {
        return {
            type: 'SET_PRICE',
            item,
            price,
        };
    },

    fetchFromAPI( path ) {
        return {
            type: 'FETCH_FROM_API',
            path,
        };
    },
};

registerStore( 'sample-shop', {

	/**
	 * Notice the reducer is recieving the default state and action defined above passed to it.
	 */
    reducer( {
		prices: {},
		discountPercent: 0,
	}, action ) {
        switch ( action.type ) {
			/**
			 * Based on the action type, we return a new object containing a new object.
			 *
			 * This object is the state returned, with whatever modifications should be made based on the
			 * resolved data we've selected. Note the references to item and price, defined in the actions.
			 */
            case 'SET_PRICE':
                return {
                    ...state,
                    prices: {
                        ...state.prices,
                        [ action.item ]: action.price,
                    },
                };
        }

        return state;
    },

    actions,

	/**
	 * Notice that the selectors automatically receives the state as the first parameter. Additionally, note that
	 * it the name maps identically to the resolver generator function of the same name. Note the expectation of the
	 * shape of the price value in the state - we defined that in the reducer above.
	 *
	 * Additional arguments can be arbitrarily added as needed. These selectors are intended to be used when selecting
	 * this store for usage within a component.
	 */
    selectors: {
        getPrice( state, item ) {
            const { prices, discountPercent } = state;
            const price = prices[ item ];

            return price * ( 1 - ( 0.01 * discountPercent ) );
        },
	},

	/**
	 * Note that this control function maps directly to the action type defined in the action object.
	 */
    controls: {
        FETCH_FROM_API( action ) {
            return apiFetch( { path: action.path } );
        },
    },

	/**
	 * Note again that resolvers and selectors map directly to one another.
	 */
    resolvers: {
        * getPrice( item ) {
            const path = '/wp/v2/prices/' + item;
            const price = yield actions.fetchFromAPI( path );
            return actions.setPrice( item, price );
        },
    },
} );
```

### Implementing a simple data store


In your own custom implementations, it will likely make more sense to separate your concerns into their own files, like [we do in core](ref). Additionally, you'll very likely be enqueueing this JavaScript file and registering your own REST API endpoints in your plugin.