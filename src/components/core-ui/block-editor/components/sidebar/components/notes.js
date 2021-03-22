/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { Fragment, useState } from "@wordpress/element";
import { PinnedItems } from "@wordpress/interface";
import { Inserter } from "@wordpress/block-editor";

/**
 * External dependencies
 */
import { Button, Card, TextField } from "@material-ui/core";
import { makeStyles } from "@material-ui/core/styles";
import ArrowBackIosIcon from "@material-ui/icons/ArrowBackIos";
import ReplayIcon from "@material-ui/icons/Replay";
/**
 * Internal dependencies
 */

export default function ({ notes, handleChangeNotes}) {
  const useStyles = makeStyles((theme) => ({
    root: {
      marginTop: "20px",
    },
    title: {
      color: "#102640",
      fontSize: "12px",
      fontWeight: "500",
      margin: "12.5px 0px 12.5px 20.5px",
    },
    textarea: {
      outline: "none",
      border: "none",
      width: "calc(100% - 41px)",
      margin: "0px 20.5px 23px 20.5px",
    },
  }));

  const classes = useStyles();

  return (
    <Card className={classes.root}>
      <div className={classes.title}>Notes</div>
      <textarea
        notes={notes}
        onChange={handleChangeNotes}
        className={classes.textarea}
        placeholder="Click here to add a custom note..."
      />
    </Card>
  );
}
