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
import ArrowDown from "components/svg/ArrowDown/";

import { createTheme } from '../../../../../theme';
const theme = createTheme({});

export default function ({
  contents,
  title,
  fontSize,
  width,
  margin,
}) {
  const [open, setOpen] = useState(false);

  const useStyles = makeStyles((theme) => ({
    root: {
      fontSize,
      width: width,
      margin,
      display: "block",
      height: open ? "auto" : parseInt(fontSize.replace("px", "")) + 25 + "px",
      overflowY: open ? "visible" : "hidden",
      cursor: "pointer",
      borderRadius: "7px",
      background: "#fff",

      "& svg": {
        float: "right",
        margin: "8px 25px",
      },
    },
    title: {
      fontWeight: "600",
      background: "#E7EEFB",
      padding: "10px 0 13px 25px",
    },
  }));

  const classes = useStyles();

  const toggleroot = () => {
    console.log("select step");
    setOpen(open ? false : true);
  };

  return (
    <div className={classes.root}>
      <div className={classes.title} onClick={toggleroot}>
        {title} <ArrowDown />
      </div>
      <br />
      {contents}
    </div>
  );
}
