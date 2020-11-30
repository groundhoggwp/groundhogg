export function filePasteHandler(files) {
  return files.map(function (url) {
    return "<img src=\"".concat(url, "\">");
  }).join('');
}
//# sourceMappingURL=file-paste-handler.native.js.map