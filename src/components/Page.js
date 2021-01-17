import React, {
  forwardRef,
  useEffect,
  useCallback
} from 'react';
import { Helmet } from 'react-helmet';
import { useLocation } from 'react-router-dom';
import PropTypes from 'prop-types';
import track from 'src/utils/analytics';

const Page = forwardRef(({
  children,
  title = '',
  ...rest
}, ref) => {
  const location = useLocation();

  const sendPageViewEvent = useCallback(() => {
    track.pageview({
      page_path: location.pathname
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    sendPageViewEvent();
  }, [sendPageViewEvent]);

  return (
    <div
      ref={ref}
      {...rest}
    >
      <Helmet>
        <title>{title}</title>
      </Helmet>
      {children}
    </div>
  );
});

Page.propTypes = {
  children: PropTypes.node.isRequired,
  title: PropTypes.string
};

export default Page;
