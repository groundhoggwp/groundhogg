/**
 * External dependencies
 */
import { find } from 'lodash';
/**
 * WordPress dependencies
 */

import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

import variations from './variations';
import { ChainIcon } from './icons';
/**
 * Retrieves the social service's icon component.
 *
 * @param {string} name key for a social service (lowercase slug)
 *
 * @return {WPComponent} Icon component for social service.
 */

export var getIconBySite = function getIconBySite(name) {
  var variation = find(variations, {
    name: name
  });
  return variation ? variation.icon : ChainIcon;
};
/**
 * Retrieves the display name for the social service.
 *
 * @param {string} name key for a social service (lowercase slug)
 *
 * @return {string} Display name for social service
 */

export var getNameBySite = function getNameBySite(name) {
  var variation = find(variations, {
    name: name
  });
  return variation ? variation.title : __('Social Icon');
};
//# sourceMappingURL=social-list.js.map