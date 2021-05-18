/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { INITIAL_STATE as BASE_OBJECT_INITIAL_STATE } from 'data/base-object/constants'

const INITIAL_STATE = {
	...BASE_OBJECT_INITIAL_STATE,
	edges: []
}

const funnelReducer = (
	state = INITIAL_STATE,
	{
		type,
		item,
		funnelId,
		edges,
		step,
	}
) => {
	switch ( type ) {

		case TYPES.UPDATE_EDGES:

			const updateEdges = (item, edges) => ({
				...item,
				meta: {
					...item.meta,
					edited: {
						...item.meta.edited,
						edges
					}
				}
			})

			state = {
				...state,
				item: state.item.ID === funnelId ? updateEdges( state.item, edges ) : state.item,
				items: state.items.map( _item => _item.ID === funnelId ? updateEdges( _item, edges ) : _item )
			}

			break;

		// Create
		case TYPES.CREATE_STEP:

			const addStep = ( item, step ) => ({
				...item,
				meta: {
					...item.meta,
					edited: {
						...item.meta.edited,
						steps: [
							...item.meta.edited.steps,
							step
						]
					}
				}
			})

			state = {
				...state,
				item: state.item.ID === funnelId ? addStep( state.item, step ) : state.item,
				items: state.items.map( _item => _item.ID === funnelId ? addStep( _item, step ) : _item )
			}

			break

		// Update
		case TYPES.UPDATE_STEP:

			const updateStep = ( item, step ) => ({
				...item,
				meta: {
					...item.meta,
					edited: {
						...item.meta.edited,
						steps: item.meta.edited.steps.map( _step => _step.ID === step.ID ? step : _step )
					}
				}
			})

			state = {
				...state,
				item: state.item.ID === funnelId? updateStep( state.item, step ) : state.item,
				items: state.items.map( _item => _item.ID === funnelId ? updateStep( _item, step ) : _item ),
			}
			break

		// Delete
		case TYPES.DELETE_STEP:

			const deleteStep = ( item, step ) => ({
				...item,
				meta: {
					...item.meta,
					edited: {
						...item.meta.edited,
						steps: item.meta.edited.steps.filter( _step => _step.ID !== step.ID )
					}
				}
			})

			state = {
				...state,
				item: state.item.ID === funnelId? deleteStep( state.item, step ) : state.item,
				items: state.items.map( _item => _item.ID === funnelId ? deleteStep( _item, step ) : _item ),
			}
			break
	}

	return state;
};

export default funnelReducer;
