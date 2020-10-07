/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { apiFetch } from '@wordpress/data-controls'

export default (endpoint) => ( {

	endpoint,

	receiveItems( items ) {
		return {
			type: TYPES.RECEIVE_ITEMS,
			items,
		};
	},

	receiveItem( item ) {
		return {
			type: TYPES.RECEIVE_ITEM,
			item,
		};
	},

	* createItems( items ) {
		yield this.setIsCreatingItems( true )

		try{
			const result = yield apiFetch({
				method: 'POST',
				path: `${endpoint}`,
				data: items
			})

			yield this.setIsCreatingItems( false )
			yield {
				type: TYPES.CREATE_ITEMS,
				items: result.items,
			}
		} catch (e) {
			yield this.setCreatingError( e )
		}
	},

	* createItem( item ) {
		yield this.setIsCreatingItems( true )

		try{
			const result = yield apiFetch({
				method: 'POST',
				path: `${endpoint}`,
				data: item
			})

			yield this.setIsCreatingItems( false )
			yield {
				type: TYPES.CREATE_ITEM,
				item: result.item,
			}
		} catch (e) {
			yield this.setCreatingError( e )
		}
	},

	* updateItems( items ) {
		yield this.setIsUpdatingItems( true )

		try{
			const response = yield apiFetch({
				method: 'PATCH',
				path: `${endpoint}`,
				data: items
			})

			yield this.setIsUpdatingItems( false )
			yield {
				type: TYPES.UPDATE_ITEMS,
				items: response.items,
			}
		} catch (e) {
			yield this.setUpdatingError( e )
		}
	},

	* updateItem( itemId, data ) {

		yield this.setIsUpdatingItems( true )

		try{
			const response = yield apiFetch({
				method: 'PATCH',
				path: `${endpoint}/${itemId}`,
				data: data
			})

			yield this.setIsUpdatingItems( false )
			yield {
				type: TYPES.UPDATE_ITEM,
				item: response.item
			}
		} catch (e) {
			yield this.setUpdatingError( e )
		}
	},

	* deleteItems( itemIds ) {
		yield this.setIsDeletingItems( true )

		try{
			yield apiFetch({
				method: 'DELETE',
				path: `${endpoint}`,
				data: itemIds
			})

			yield this.setIsDeletingItems( false )
			yield {
				type: TYPES.DELETE_ITEMS,
				itemIds,
			}
		} catch (e) {
			yield this.setDeletingError( e )
		}
	},

	* deleteItem( itemId ) {
		yield this.setIsDeletingItems( true )

		try{
			yield apiFetch({
				path: `${endpoint}/${itemId}`,
				method: 'DELETE'
			})

			yield this.setIsDeletingItems( false )
			yield {
				type: TYPES.DELETE_ITEM,
				itemId,
			}
		} catch (e) {
			yield this.setDeletingError( e )
		}
	},

	setIsCreatingItems( isCreating ) {
		return {
			type: TYPES.SET_IS_CREATING,
			isCreating,
		};
	},

	setCreatingError( error ) {
		return {
			type: TYPES.SET_CREATING_ERROR,
			error
		};
	},

	setIsRequestingItems( isRequesting ) {
		return {
			type: TYPES.SET_IS_REQUESTING,
			isRequesting,
		};
	},

	setRequestingError( error ) {
		return {
			type: TYPES.SET_REQUESTING_ERROR,
			error
		};
	},

	setIsUpdatingItems( isUpdating ) {
		return {
			type: TYPES.SET_IS_UPDATING,
			isUpdating,
		};
	},

	setUpdatingError( error ) {
		return {
			type: TYPES.SET_UPDATING_ERROR,
			error,
		};
	},

	setIsDeletingItems( isDeleting ) {
		return {
			type: TYPES.SET_IS_DELETING,
			isDeleting,
		};
	},

	setDeletingError( error ) {
		return {
			type: TYPES.SET_DELETING_ERROR,
			error,
		};
	},

} )
