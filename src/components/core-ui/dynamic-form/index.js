
/**
 * WordPress dependencies
 */
// none so far

/**
 * External dependencies
 */
import React from 'react';
import { Switch } from "@material-ui/core";
import { withStyles } from '@material-ui/core/styles';
import PropTypes from 'prop-types';
import { makeStyles } from "@material-ui/core/styles";
/**
 * Internal dependencies
 */
import  { createTheme }   from "../../../theme";

const STEP_TYPE = "send_email";

const theme = createTheme({});

export const DynamicForm = withStyles((theme) => ({
  root: {
  },
}))(({ classes, children, hanldeFormChange, ...rest }) => {
  // console.log(rest, children[0])



  return (
    <>{
      children.map((row)=>{
        // row.props.onChange = handleChange()
        return row;
      })
    }</>
  );
});


DynamicForm.propTypes = {
  /**
   * React components that build this form
   */
  children: PropTypes.node.isRequired,
};

DynamicForm.defaultProps = {
  // on: false,
};
