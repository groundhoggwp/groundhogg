import React from 'react';
import PropTypes from 'prop-types';
// import './styles.css';

/**
 * Primary UI component for user interaction
 */
export const BeaverIcon = ({ size, label, ...props }) => {
  // const mode = primary ? 'storybook-button--primary' : 'storybook-button--secondary';
  return (
    <img
      alt="BeaverIcon"
      src="https://scontent-bos3-1.xx.fbcdn.net/v/t1.6435-1/cp0/p80x80/46468565_737188263296852_9169433243391361024_n.png?_nc_cat=111&ccb=1-3&_nc_sid=dbb9e7&_nc_ohc=GNqTQ_B4TS0AX9eBWIU&_nc_ht=scontent-bos3-1.xx&tp=30&oh=a299601adffce25bc46a92feb1dc5f6d&oe=60B7B6BF"
      {...props}
    />
  );
};

BeaverIcon.propTypes = {
  /**
   * How large should the button be?
   */
  size: PropTypes.oneOf(['small', 'medium', 'large']),
  /**
   * Button contents
   */
  // label: PropTypes.string.isRequired,

};

BeaverIcon.defaultProps = {
  // primary: false,
  size: 'medium'
};
