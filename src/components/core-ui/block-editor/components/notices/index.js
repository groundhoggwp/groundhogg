/**
 * WordPress dependencies
 */

// TODO: Potentially use our own snackbar implementation

import { useSelect, useDispatch } from "@wordpress/data";
import { SnackbarList } from "@wordpress/components";

export default function () {
  const notices = useSelect(
    (select) =>
      select("core/notices")
        .getNotices()
        .filter((notice) => notice.type === "snackbar"),
    []
  );
  const { removeNotice } = useDispatch("core/notices");
  return (
    <SnackbarList
      className="edit-email-editor-notices"
      notices={notices}
      onRemove={removeNotice}
    />
  );
}
