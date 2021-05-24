
/**
 * WordPress dependencies
 */
import { useEffect, useState, useMemo, useRef } from "@wordpress/element";

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


const theme = createTheme({});

export const KeyPairRow = withStyles((theme) => ({
  label: {
    fontSize: '18px',
    fontWeight: '700'
  },
}))(({ classes, id, addRow, deleteRow, handleFormChange, ...rest }) => {

  return (
    <div className={classes.root}>
      <input
        id={`key-${id}`}
        className={classes.inputText}
        placeholder={""}
        onChange={handleFormChange}
      />
      <input
        id={`value-${id}`}
        className={classes.inputText}
        placeholder={""}
        onChange={handleFormChange}
      />
      <div className={`${classes.customHeaderBtn} ${classes.addButton}`} onClick={addRow}>add <AddWithBorder /></div>
      <div className={`${classes.customHeaderBtn} ${classes.trashButton}`} onClick={deleteRow}> delete<Trash /></div>
    </div>
  );
});

export const DynamicKeyPairs = withStyles((theme) => ({
  label: {
    fontSize: '18px',
    fontWeight: '700'
  },
}))(({ classes, label, rowData, ...rest }) => {
  console.log(label, rowData, rest)
  const [keyPairSection, setKeyPairSection] = useState([
    {
      id: "1"
    }
  ]);

  const addRow = (e) => {
    keyPairSection.push(<KeyPairRow addRow={addRow} deleteRow={deleteRow} handleFormChange={handleFormChange}/>)
    setKeyPairSection(keyPairSection)
  }
  const deleteRow = (e) => {
    keyPairSection.pop()

    setKeyPairSection(keyPairSection)
  }

  const handleFormChange = () => {

  }

  return (
    <>
      <div className={classes.label}>{label}</div>
      {rowData.map((row)=>{
        console.log(row)
        return <KeyPairRow id={row.id} addRow={addRow} deleteRow={deleteRow} handleFormChange={handleFormChange}/>
      })}
    </>
  );
});


DynamicKeyPairs.propTypes = {
  /**
   * React components that build this form
   */
  label: PropTypes.node.isRequired,
  rowData: PropTypes.node.isRequired,
};

DynamicKeyPairs.defaultProps = {
  label: 'default',
  rowData: [
    {
      'id' : '1',
      'label' : 'Dynamic Key Pairs',
    }
  ],
};
