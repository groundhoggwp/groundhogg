/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { Fragment, useState, useEffect, useRef } from "@wordpress/element";

/**
 * External dependencies
 */
import { makeStyles } from "@material-ui/core/styles";
import { Button } from "@material-ui/core";

/**
 * Internal dependencies
 */
import CheckMark from "components/svg/CheckMark/";
import Error from "components/svg/Error/";

import { createTheme } from "../../../../../theme";
const theme = createTheme({});

export default function ({ text }) {
  const useStyles = makeStyles((theme) => ({
    root: {
      position: "absolute",
      left: "25px",
      bottom: "25px",
      color: "#fff",
      zIndex: "1",
      background: theme.palette.secondary.main,
      fontSize: "18px",
      textTransform: "none",
      borderRadius: "7px",
      justifySelf: "end",
      opacity: "0%",
      transition: "opacity 500ms ease",
      "&:hover": {
        background: theme.palette.secondary.main,
      },
      "& svg": {
        fill: "#fff",
        marginLeft: "145px",
      },
    },
    text: {
      display: "inline-block",
      minWidth: "128px",
    },
  }));

  const classes = useStyles();
  const noticeRef = useRef(null);

  useEffect(() => {
    if (text.length > 0) {
      noticeRef.current.style.opacity = "100%";

      setTimeout(() => {
        noticeRef.current.style.opacity = "0%";
      }, 1000);
    } else {
      noticeRef.current.style.opacity = "0%";
    }
  }, [text]);

  console.log(text);

  return (
    <Button className={`${classes.root}`} ref={noticeRef}>
      <span className={classes.text}>{text}</span>
      <Error />
    </Button>
  );
}