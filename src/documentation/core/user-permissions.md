## Understanding user permissions.
WordPress internally creates a canUser() method on their core data store. This method is more or less analagous to the `current_user_can()` API in WordPress for PHP. It is only _somewhat_ analagous, as it operates slightly differently, and the differences are important.

Where WordPress stores user capabilities in a serialized meta data array, and the PHP function in WordPress checks that metadata for the logged in user against whatever capability is being checked for - `canUser` operates against your REST API. That means you can pass 2-3 arguments to it - a REST verb, like create, or read, then an entity type, like post or media, and finally, a specific entity ID.

What this means for anyone using this functionality is that you can't do what _may_ feel natural, e.g. `canUser( 'manage_options' )`. Rather, you'd have to do something modeled to an API permissions callback - that might be something more like `canUser( 'update', 'settings' )`.

Naturally, WordPress core makes this tied implicitly to core WordPress APIs, at the core namespace, so we had to create our own function that operates in a similar manner.

## Using the Groundhogg `canUser` function
We've implemented the `canUser` [resolver](https://github.com/tobeyadr/Groundhogg/blob/react-rebuild/src/data/core/resolvers.js#L27-L69) and [selector](https://github.com/tobeyadr/Groundhogg/blob/react-rebuild/src/data/core/selectors.js#L129-L132) in our core data store. This abstraction level should _generally_ be ignored, and instead, we recommend using the utility function.