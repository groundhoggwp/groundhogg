/**
 * Clears the a11y-speak-region elements and hides the explanatory text.
 */
export default function clear() {
  var regions = document.getElementsByClassName('a11y-speak-region');
  var introText = document.getElementById('a11y-speak-intro-text');

  for (var i = 0; i < regions.length; i++) {
    regions[i].textContent = '';
  } // Make sure the explanatory text is hidden from assistive technologies.


  if (introText) {
    introText.setAttribute('hidden', 'hidden');
  }
}
//# sourceMappingURL=clear.js.map