/**
 * WordPress dependencies
 */
import "@wordpress/format-library";
import { __ } from "@wordpress/i18n";
import { useSelect, useDispatch } from "@wordpress/data";
import { useEffect, useState, useMemo } from "@wordpress/element";
import { serialize, parse } from "@wordpress/blocks";
import { uploadMedia } from "@wordpress/media-utils";
import {
  BlockEditorKeyboardShortcuts,
  BlockEditorProvider,
  BlockList,
  BlockInspector,
  WritingFlow,
  ObserveTyping,
  Typewriter,
  CopyHandler,
  BlockSelectionClearer,
  MultiSelectScrollIntoView,
} from "@wordpress/block-editor";

import { VisualEditorGlobalKeyboardShortcuts } from "@wordpress/editor";

import { Popover } from "@wordpress/components";

/**
 * External dependencies
 */
import Card from "@material-ui/core/Card";
import Paper from "@material-ui/core/Paper";
import Grid from "@material-ui/core/Grid";
import TextField from "@material-ui/core/TextField";
import { makeStyles } from "@material-ui/core/styles";

/**
 * Internal dependencies
 */
import Sidebar from "../sidebar";

//TODO Implement block persistence with email data store.
//TODO Potentially use our own alerts data store (core).

function BlockEditor({
  settings: _settings,
  subject,
  handleSubjectChange,
  preHeader,
  handlePreHeaderChange,
  viewType,
  handleUpdateBlocks,
  blocks,
}) {
  const useStyles = makeStyles((theme) => ({
    subjectHeader: {
      padding: "20px",
      marginBottom: "10px",
    },
    subjectInputs: {
      width: "100%",
      padding: "",
      marginBottom: "10px",
    },
    emailContent: {
      display: "block",
      width: viewType === "desktop" ? "600px" : "320px",
      marginLeft: "auto",
      marginRight: "auto",
    },
  }));

  const { createInfoNotice } = useDispatch("core/notices");
  const classes = useStyles();
  const canUserCreateMedia = useSelect((select) => {
    const _canUserCreateMedia = select("core").canUser("create", "media");
    return _canUserCreateMedia || _canUserCreateMedia !== false;
  }, []);

  const settings = useMemo(() => {
    if (!canUserCreateMedia) {
      return _settings;
    }
    return {
      ..._settings,
      mediaUpload({ onError, ...rest }) {
        uploadMedia({
          wpAllowedMimeTypes: _settings.allowedMimeTypes,
          onError: ({ message }) => onError(message),
          ...rest,
        });
      },
    };
  }, [canUserCreateMedia, _settings]);

  if (!settings.hasOwnProperty("__experimentalBlockPatterns")) {
    settings.__experimentalBlockPatterns = [];
  }

  return (
    <div className="groundhogg-block-editor">
      <BlockEditorProvider
        value={blocks}
        settings={settings}
        onInput={handleUpdateBlocks}
        onChange={handleUpdateBlocks}
      >
        <div className="groundhogg-block-editor__email-container">
          <Card className={classes.subjectHeader}>
            <form noValidate autoComplete="off">
              <TextField
                className={classes.subjectInputs}
                onChange={handleSubjectChange}
                label={"Subject"}
                value={subject}
              />
              <TextField
                className={classes.subjectInputs}
                onChange={handlePreHeaderChange}
                label={"Pre Header"}
                value={preHeader}
                placeholder={__(
                  "Pre Header Text: Used to summarize the content of the email."
                )}
              />
            </form>
          </Card>
          <Paper>
            <div
              className={
                classes.emailContent + " groundhogg-email-editor__email-content"
              }
            >
              <BlockSelectionClearer className={classes}>
                <VisualEditorGlobalKeyboardShortcuts />
                <MultiSelectScrollIntoView />
                {/* Add Block Button */}
                <BlockEditorKeyboardShortcuts.Register />
                <Popover.Slot name="block-toolbar" />
                <Typewriter>
                  <CopyHandler>
                    <WritingFlow>
                      <ObserveTyping>
                        {/* Rendered blocks */}
                        <BlockList />
                      </ObserveTyping>
                    </WritingFlow>
                  </CopyHandler>
                </Typewriter>
              </BlockSelectionClearer>
            </div>
          </Paper>
        </div>
        <Sidebar.InspectorFill>
          <Popover name="block-toolbar" />
          <BlockInspector />
        </Sidebar.InspectorFill>
      </BlockEditorProvider>
    </div>
  );
}

export default BlockEditor;
