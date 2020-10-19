/**
 * External dependencies
 */
import { mapValues, pick } from 'lodash';
import { useDispatch, useSelect } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

/**
 * Retrieve and decode the user's Groundhogg meta values.
 *
 * @param {Object} user WP User object.
 * @return {Object} User's Groundhogg preferences.
 */
const getGroundhoggMeta = ( user ) => {
	const ghMeta = user.groundhogg_meta || {};

	const userData = mapValues( ghMeta, ( data ) => {
		if ( ! data || data.length === 0 ) {
			return '';
		}
		return JSON.parse( data );
	} );

	return userData;
};

/**
 * Custom react hook for retrieving the current user's Groundhogg preferences.
 *
 * This is a wrapper around @wordpress/core-data's getCurrentUser() and saveUser().
 */
export const useUserPreferences = () => {
	// Get our dispatch methods now - this can't happen inside the callback below.
	const dispatch = useDispatch( STORE_NAME );
	const {
		addEntities,
		receiveCurrentUser,
		saveEntityRecord
	} = dispatch;
	let { saveUser } = dispatch;

	const { isRequesting, userPreferences, updateUserPreferences } = useSelect(
		( select ) => {
			const {
				getCurrentUser,
				getEntity,
				getEntityRecord,
				getLastEntitySaveError,
				hasStartedResolution,
				hasFinishedResolution,
			} = select( STORE_NAME );

			// Use getCurrentUser() to get Groundhogg meta values.
			const user = getCurrentUser();
			const userData = getGroundhoggMeta( user );

			const updateUserPrefs = async ( userPrefs ) => {

				// Whitelist our meta fields.
				const userDataFields = applyFilters( 'groundhogg_user_meta', [
					'preferred_test_email',
					'gh_free_extension_discount',
					'wpgh_user_public_key',
					'wpgh_user_secret_key',
					'dismissed_wp_pointers',
					'gh_free_extension_checkout_link',
					'block_editor_mode',
				] );

				// Prep valid fields for update.
				const metaData = mapValues(
					pick( userPrefs, userDataFields ),
					JSON.stringify
				);

				if ( Object.keys( metaData ).length === 0 ) {
					return {
						error: new Error(
							'No valid groundhogg_meta keys were provided for update.'
						),
						updatedUser: undefined,
					};
				}

				// Optimistically propagate new groundhogg_meta to the store for instant update.
				receiveCurrentUser( {
					...user,
					groundhogg_meta: {
						...user.groundhogg_meta,
						...metaData,
					},
				} );

				// Use saveUser() to update Groundhogg meta values.
				const updatedUser = await saveUser( {
					id: user.id,
					groundhogg_meta: metaData,
				} );

				if ( undefined === updatedUser ) {
					// Return the encountered error to the caller.
					const error = getLastEntitySaveError(
						'root',
						'user',
						user.id
					);

					return {
						error,
						updatedUser,
					};
				}

				// Decode the Groundhogg meta after save.
				const updatedUserResponse = {
					...updatedUser,
					groundhogg_meta: getGroundhoggMeta( updatedUser ),
				};

				return {
					updatedUser: updatedUserResponse,
				};
			};

			return {
				isRequesting:
					hasStartedResolution( 'getCurrentUser' ) &&
					! hasFinishedResolution( 'getCurrentUser' ),
				userPreferences: userData,
				updateUserPreferences: updateUserPrefs,
			};
		}
	);

	return {
		isRequesting,
		...userPreferences,
		updateUserPreferences,
	};
};
