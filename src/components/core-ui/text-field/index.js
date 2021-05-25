
/**
 * WordPress dependencies
 */
// none so far

/**
 * External dependencies
 */
import { Switch } from "@material-ui/core";
import { withStyles } from '@material-ui/core/styles';
import PropTypes from 'prop-types';
import { makeStyles } from "@material-ui/core/styles";
/**
 * Internal dependencies
 */
import  { createTheme }   from "../../../theme";

const theme = createTheme({});

export const Textfield = withStyles((theme) => ({
  root: {
    fontSize: "12px",
    width: "calc(100% - 10px)",
    padding: "6px 0px 6px 17px",
    marginTop: "10px",
    border: "1.2px solid rgba(16, 38, 64, 0.15)",
    borderRadius: "3px",
    "&:focus": {
      outline: "none",
      border: "1.2px solid rgba(16, 38, 64, 0.15)",
      boxShadow: "none",
    },
  },
}))(({ classes, id, className, onChange, placeholder, value, ...rest }) => {

  return (
    <input
      className={`${classes.root} ${className}`}
      placeholder={placeholder}
      value={value}
      onChange={onChange}
    />
  );
});


Textfield.propTypes = {
  /**
   * is the on or off
   */
  on: PropTypes.bool
};

Textfield.defaultProps = {
  on: false,
};
