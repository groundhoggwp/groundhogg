/**
 * External dependencies
 */
import showdown from 'showdown'; // Reuse the same showdown converter.

var converter = new showdown.Converter({
  noHeaderId: true,
  tables: true,
  literalMidWordUnderscores: true,
  omitExtraWLInCodeBlocks: true,
  simpleLineBreaks: true,
  strikethrough: true
});
/**
 * Corrects the Slack Markdown variant of the code block.
 * If uncorrected, it will be converted to inline code.
 *
 * @see https://get.slack.help/hc/en-us/articles/202288908-how-can-i-add-formatting-to-my-messages-#code-blocks
 *
 * @param {string} text The potential Markdown text to correct.
 *
 * @return {string} The corrected Markdown.
 */

function slackMarkdownVariantCorrector(text) {
  return text.replace(/((?:^|\n)```)([^\n`]+)(```(?:$|\n))/, function (match, p1, p2, p3) {
    return "".concat(p1, "\n").concat(p2, "\n").concat(p3);
  });
}
/**
 * Converts a piece of text into HTML based on any Markdown present.
 * Also decodes any encoded HTML.
 *
 * @param {string} text The plain text to convert.
 *
 * @return {string} HTML.
 */


export default function markdownConverter(text) {
  return converter.makeHtml(slackMarkdownVariantCorrector(text));
}
//# sourceMappingURL=markdown-converter.js.map