
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
import { Textfield } from "../text-field/";
import  { createTheme }   from "../../../theme";


const theme = createTheme({});

export const KeyPairRow = withStyles((theme) => ({
  label: {
    fontSize: '18px',
    fontWeight: '700'
  },
  inputField: {
    display: 'inline-block',
    width: 'calc(50% - 100px)',
    margin: '0 20px 0 20px'
  },
  addButton:{
    display: 'inline-block',
    '& svg': {
    width: '20px',
    height: '20px',
    fill: theme.palette.secondary.main
    }
  },
  trashButton:{
    display: 'inline-block',
    marginLeft: '20px',
    '& svg': {
    width: '20px',
    height: '20px',
    stroke: '#102640'
    }
  }
}))(({ classes, id, addRow, deleteRow, handleFormChange, ...rest }) => {

  return (
    <div className={classes.root}>

      <Textfield
        id={`key-${id}`}
        className={classes.inputField}
        placeholder={""}
        value={""}
        onChange={handleFormChange}
      />
      <Textfield
        id={`value-${id}`}
        className={classes.inputField}
        placeholder={""}
        value={""}
        onChange={handleFormChange}
      />
      <div className={`${classes.addButton}`} onClick={addRow}><AddWithBorder /></div>
      <div className={`${classes.trashButton}`} onClick={deleteRow}><Trash /></div>
    </div>
  );
});

export const DynamicKeyPairs = withStyles((theme) => ({
  label: {
    fontSize: '18px',
    fontWeight: '700'
  },
}))(({ classes, label, dataset, handleFormChange, ...rest }) => {
  console.log(label, dataset, rest)

  // {
  //   id: "1"
  // }
  const addRow = () =>{
    handleFormChange('key-pair-add')
  }
  const deleteRow = () =>{
    handleFormChange('key-pair-delete')
  }
  return (
    <>
      <div className={classes.label}>{label}</div>
      {dataset.map((data)=>{
        console.log(data)
        return <KeyPairRow id={data.id} addRow={addRow} deleteRow={deleteRow} handleFormChange={handleFormChange}/>
      })}
    </>
  );
});


DynamicKeyPairs.propTypes = {
  /**
   * React components that build this form
   */
  label: PropTypes.node.isRequired,
  dataset: PropTypes.node.isRequired,
};

DynamicKeyPairs.defaultProps = {
  label: 'default',
  dataset: [
    {
      'id' : '1',
      'label' : 'Dynamic Key Pairs',
    }
  ],
};
