import React from 'react';
import PropTypes from 'prop-types';
// import './styles.css';

/**
 * Primary UI component for user interaction
 */
export const Logo = ({ size, label, ...props }) => {
  // const mode = primary ? 'storybook-button--primary' : 'storybook-button--secondary';
  return (
    <img
      alt="Logo"
      src="https://www.groundhogg.io/wp-content/uploads/2019/05/Groundhogg_logox300-with-tm-260x45.png"
      {...props}
    />
  );
};

Logo.propTypes = {
  /**
   * How large should the button be?
   */
  size: PropTypes.oneOf(['small', 'medium', 'large']),
  /**
   * Button contents
   */
  label: PropTypes.string.isRequired,

};

Logo.defaultProps = {
  primary: false,
  size: 'medium'
};
