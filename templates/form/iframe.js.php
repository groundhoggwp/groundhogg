<?php
/**
 * Responsive Form Iframe JS Template
 *
 * @package     Templates
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0.20
 */

use function Groundhogg\managed_page_url;status_header( 200 );
nocache_headers();

header( "Content-Type: application/javascript" );

$step = new \Groundhogg\Step( get_query_var( 'slug' ) );

if ( ! $step->exists() ){
  wp_die();
}

$form = new \Groundhogg\Form\Form( [
	'id' => $step->get_id(),
] );

?>
/**
 * Responsive Form Iframe JS Template
 *
 * @package     Templates
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0.20
 */

(function () {
  if (window.ghFormClient) {
    return
  }

  window.ghFormClient = {

    forms: [],

    addForm (form) {
      let script

      if (document.currentScript) {
        script = document.currentScript
      }

      this.forms.push({
        ...form,
        script
      })

    },

    loadForms () {

      this.forms.forEach((form, i) => {

        const {
          src = '',
          script,
          name
        } = form

        let id = `gh-form-${i}`
        let iframeHTML = `<iframe id="${id}" class="gh-form-iframe" name="${name}" src="${src}" allowtransparency="" frameborder="0" scrolling="no" style="overflow:hidden; border:none; width:100%;">`
        let container = document.createElement('div')
        container.classList.add('gh-form-iframe-container')
        container.innerHTML = iframeHTML
        script.parentNode.insertBefore(container, script)

        let frame = document.getElementById(id)

        frame.addEventListener('load', () => {
          frame.contentWindow.postMessage({ action: 'getFrameSize', id }, '*')
        })

      })

    },

    handleResize () {

      this.forms.forEach((form, i) => {
        let id = `gh-form-${i}`
        let iframe = document.getElementById(id)
        iframe.contentWindow.postMessage({ action: 'getFrameSize', id }, '*')
      })
    }

  }

  function addEvent (event, callback) {
    if (!window.addEventListener) { // This listener will not be valid in < IE9
      window.attachEvent('on' + event, callback)
    } else { // For all other browsers other than < IE9
      window.addEventListener(event, callback, false)
    }
  }

  function resizeAllFrames () {
    window.ghFormClient.handleResize()
  }

  function loadForms () {
    window.ghFormClient.loadForms()
  }

  addEvent('message', receiveMessage)
  addEvent('resize', resizeAllFrames)
  addEvent('load', loadForms)

  function receiveMessage (event) {
    var data = event.data
    resizeForm(data)
  }

  function resizeForm (data) {

    if (data.height) {
      var f = document.getElementById(data.id)
      if (f) {
        f.style.height = data.height + 'px'
        f.style.width = data.width + 'px'
      }
    }
  }

})()

ghFormClient.addForm(<?php echo wp_json_encode( [
	'src'  => $form->get_hosted_url(),
	'name' => $form->step->get_title(),
] ); ?> )
