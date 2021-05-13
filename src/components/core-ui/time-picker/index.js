
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

export const DatePicker = withStyles((theme) => ({
  root: {
    fontSize: "12px",
    marginTop: "5px",
    padding: "0px 74px 0px 7px",
    border: "1.5px solid rgba(16, 38, 64, 0.1)",
    borderRadius: "2px"
  },
}))(({ classes, ...props }) => {
  // console.log(props, options)
  // value={text}
  // placeholder={placeholder}
  // onChange={onChange}
        // fullWidth
  return (
    <input
      className={classes.root}

    />
  );
});


TimePicker.propTypes = {
  /**
   * is the on or off
   */
  text: PropTypes.string
};

TimePicker.defaultProps = {
  text: '',
};
