import { useEffect, useState } from '@wordpress/element'
import { dispatch, useDispatch, useSelect } from '@wordpress/data'
import { CORE_STORE_NAME } from '../data';
import { useRouteMatch } from 'react-router-dom';
import { DateTime } from 'luxon';
import { objEquals } from 'utils/core'

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

export const useDebounce = (value, delay) => {
  // State and setters for debounced value
  const [debouncedValue, setDebouncedValue] = useState(value)

  useEffect(
    () => {
      // Update debounced value after delay
      const handler = setTimeout(() => {
        setDebouncedValue(value)
      }, delay)

      // Cancel the timeout if value changes (also on delay change or unmount)
      // This is how we prevent debounced value from updating if value is
      // changed ... .. within the delay period. Timeout gets cleared and
      // restarted.
      return () => {
        clearTimeout(handler)
      }
    },
    [value, delay], // Only re-call effect if value or delay changes
  )

  return debouncedValue
}

/**
 * Allows easy manipulation of state without having to update right away.
 *
 * @param origState
 * @returns {{tempState: object, setTempState: setTempState }}
 */
export const useTempState = ( origState ) => {

  const [tempState, setTempState] = useState({
    ...origState
  })

  /**
   * Reset the state to the original state
   */
  const resetTempState = () => {
    setTempState( { ...origState } );
  }

  return {
    tempState,
    setTempState,
    resetTempState,
  }
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

/**
 * Returns whether the current user can perform the given action on the given
 * REST resource.
 *
 * Calling this may trigger an OPTIONS request to the REST API via the
 * `canUser()` resolver.
 *
 * @param {string}   action           Action to check. One of: 'create', 'read', 'update', 'delete'.
 * @param {string=}  resource         REST resource to check, e.g. 'funnels' or 'emails'.
 * @param {string=}  id               Optional ID of the rest resource to check.
 *
 * @return {boolean|undefined} Whether or not the user can perform the action,
 *                             or `undefined` if the OPTIONS request is still being made.
 */
export const canUser = ( action, id, resource ) => {

  const { path } = useRouteMatch();

  let _resource = path.split( '/' )[1];
  resource      = resource || _resource;

  const { canUser } = useSelect( (select) => {
    return {
      canUser: id ?
        select( CORE_STORE_NAME ).canUser( action, resource, id ) :
        select( CORE_STORE_NAME ).canUser( action, resource )
    }
  }, [] );

  return canUser;
}

/**

 * Returns Luxon returns in a predictable manner
 *
 * @param {string=}  type What type, usually associated to DB or widget requirements
  *
 * @return {DateIOSString|Luxon Object} Returns various Luxon outputs, strings, objects etc.
 */
export const getLuxonDate = (type) => {
  switch (type) {
    case 'last_updated':
      return `${DateTime.local()} ${DateTime.local().toISOTime()}`;
      break;
    case 'date_created':
      return `${DateTime.local()} ${DateTime.local().toISOTime()}`;
      break;
    case 'today':
      return DateTime.local().startOf('day').toISODate();
      break;
    case 'one_year_back':
      return  DateTime.local().minus({ years: 1 }).startOf('day').toISODate();
      break;
    case 'one_month_back':
      return  DateTime.fromISO(date).minus({ months: 1 }).toISODate();
      break;
    case 'one_month_forward':
      return  DateTime.fromISO(date).plus({ months: 1 }).toISODate();
      break;
    default:
      console.log(`Nothing matched in luxon.`);
  }
}
/**

 * Returns Luxon returns in a predictable manner
 *
 * @param {string=}  type What type, usually associated to DB or widget requirements
  *
 * @return {DateIOSString|Luxon Object} Returns various Luxon outputs, strings, objects etc.
 */
export const matchEmailRegex = (testEmail) => {
  if(testEmail.match(/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/)){
    return true;
  }

  return false;  
}
