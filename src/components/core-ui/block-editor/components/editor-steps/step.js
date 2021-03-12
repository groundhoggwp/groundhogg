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

export default function Step({ icon, title, type, active, color, selectStep }) {
  const useStyles = makeStyles((theme) => ({
    root: {
      position: "relative",
      width: "265px",
      height: "56px",
      border: active
        ? `2px solid ${color}`
        : `1px solid rgba(16, 38, 64, 0.15);`,
      margin: "5px 0 5px 0",
      cursor: "pointer",
    },
    icon: {
      display: "inline-block",
      position: "absolute",
      top: "22px",
      left: "17px",
    },
    title: {
      display: "",
      fontSize: "12px",
      marginTop: "13px",
      marginLeft: "50px",
    },
    type: {
      color,
      marginLeft: "50px",
      fontSize: "7px",
      textTransform: "capitalize",
    },
  }));

  const classes = useStyles();

  return (
    <Card className={classes.root} selectStep={() => {}}>
      <div className={classes.icon}>{icon}</div>
      <div className={classes.title}>{title}</div>
      <div className={classes.type}>{type}</div>
    </Card>
  );
}
