import React, { useEffect, useState } from 'react'

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