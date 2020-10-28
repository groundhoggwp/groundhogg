import { useEffect, useState } from '@wordpress/element'
import { dispatch, useSelect } from '@wordpress/data'
import { CORE_STORE_NAME } from '../data';
import { useRouteMatch } from 'react-router-dom'

export const useShift = (onShift) => {
  useEffect(() => {
    const handleShift = (event) => {
      if (event.keyCode === 16) {
        onShift()
      }
    }
    window.addEventListener('keydown', handleShift)

    return () => {
      window.removeEventListener('keydown', handleShift)
    }
  }, [])
}

// Hook
export const useKeyPress = (targetKey, onKeyDown, onKeyUp) => {
  // State for keeping track of whether key is pressed
  const [keyPressed, setKeyPressed] = useState(false);

  // If pressed key is our target key then set to true
  function downHandler({ keyCode }) {
    if (keyCode === targetKey) {
      setKeyPressed(true);
      onKeyDown()
    }
  }

  // If released key is our target key then set to false
  const upHandler = ({ keyCode }) => {
    if (keyCode === targetKey) {
      setKeyPressed(false);
      onKeyUp()
    }
  };

  // Add event listeners
  useEffect(() => {
    window.addEventListener('keydown', downHandler);
    window.addEventListener('keyup', upHandler);
    // Remove event listeners on cleanup
    return () => {
      window.removeEventListener('keydown', downHandler);
      window.removeEventListener('keyup', upHandler);
    };
  }, []); // Empty array ensures that effect is only run on mount and unmount

  return keyPressed;
}

/**
 * Adds Snackbar Notifications
 *
 * @param {string} message Message to show
 * @param {string} type Type of notification: successs, info, warning, error (ordered by severity, defaults to success)
 */
export const addNotification = ( { message, type } ) => {
  dispatch( CORE_STORE_NAME ).showSnackbar( message, type );
}

export const canUser = ( action, id, resource ) => {

  const { path } = useRouteMatch();

  let _resource = path.split( '/' )[1];
  resource      = resource || _resource;

  const { canUser } = useSelect( (select) => {
    return {
      canUser: id ? select( CORE_STORE_NAME ).canUser( action, resource, id ) : select( CORE_STORE_NAME ).canUser( action, resource )
    }
  }, [] );

  return canUser;
}