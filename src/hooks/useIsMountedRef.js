import {useRef, useEffect } from 'react';

const useIsMountedRef = () => {
  const isMounted = useRef(true);

  useEffect(() => () => {
    isMounted.current = false;
  }, []);

  return isMounted;
};

export default useIsMountedRef;
