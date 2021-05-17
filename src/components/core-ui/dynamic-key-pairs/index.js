
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
import AddWithBorder from "../../../components/svg/AddWithBorder/";
import Trash from "../../../components/svg/Trash/";
import  { createTheme }   from "../../../theme";

const STEP_TYPE = "send_email";

const theme = createTheme({});

export const DynamicKeyPairs = withStyles((theme) => ({
  root: {
  },
}))(({ classes, children, ...rest }) => {
  console.log(rest)

  const addRow = (e) => {

    keyPairSection.push(keyPairRow)
    console.log('asdfasdf', keyPairSection)
    setKeyPairSection(keyPairSection)
  }
  const deleteRow = (e) => {

  }

  const handleChange = () => {

  }

  const keyPairRow = <div className={classes.inputRow}>
    <input
      className={classes.inputText}
      placeholder={""}
      onChange={handleChange}
    />
    <input
      className={classes.inputText}
      placeholder={""}
      onChange={handleChange}
    />
    <div className={`${classes.customHeaderBtn} ${classes.addButton}`} onClick={()=>{addRow()}}> <AddWithBorder /></div>
    <div className={`${classes.customHeaderBtn} ${classes.trashButton}`} onClick={deleteRow}> <Trash /></div>
  </div>
  const [keyPairSection, setKeyPairSection] = React.useState([keyPairRow]);


  return (
    <div>
      {keyPairSection}
    </div>
  );
});


DynamicKeyPairs.propTypes = {
  /**
   * React components that build this form
   */
  children: PropTypes.node.isRequired,
};

DynamicKeyPairs.defaultProps = {
  // on: false,
};
