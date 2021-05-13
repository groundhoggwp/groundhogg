
/**
 * WordPress dependencies
 */
// none so far

/**
 * External dependencies
 */
import { withStyles } from '@material-ui/core/styles';
import PropTypes from 'prop-types';
import { makeStyles } from "@material-ui/core/styles";
/**
 * Internal dependencies
 */
import  { createTheme }   from "../../../theme";

const theme = createTheme({});

export const TextField = withStyles((theme) => ({
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

}))(({ classes, text, placeholder, onChange, ...props }) => {
  console.log(props)

  return (
    <input
      className={classes.root}
      value={text}
      placeholder={placeholder}
      onChange={onChange}
      fullWidth
    />
  );
});


TextField.propTypes = {
  /**
   * Input text
   */
  text: PropTypes.string
};

TextField.defaultProps = {
  text: '',
};
