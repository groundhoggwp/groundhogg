import { NAMESPACE } from '../constants';

/**
 * Default state of the reducer
 *
 * @type {{isDeleting: boolean, totalItems: number, item: {}, updatingErrors: {}, deletingErrors: {}, requestingErrors: {}, updatedItem: {}, creatingErrors: {}, createdItems: [], isCreating: boolean, isRequesting: boolean, isUpdating: boolean, createdItem: {}, items: [], updatedItems: []}}
 */
export const INITIAL_STATE = {
  isCreating: false,
  isRequesting: false,
  isUpdating: false,
  isDeleting: false,
  totalItems: 0,
  items: [],
  cache: [],
  useCache: true,
  createdItems: [],
  updatedItems: [],
  item: {},
  createdItem: {},
  updatedItem: {},
  creatingErrors: {},
  requestingErrors: {},
  updatingErrors: {},
  deletingErrors: {},
}

/**
 * Internal dependencies
 *
 * @todo: Note: this API endpoint does not currently exist.
 */
export const STORE_NAME = `${NAMESPACE}/`;
