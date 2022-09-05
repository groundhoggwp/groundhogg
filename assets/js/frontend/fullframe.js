(function () {

  const isSameOrigin = (destination) => {
    const origin = new URL(window.location)
    destination = new URL(destination)

    return origin.protocol === destination.protocol && origin.host === destination.host
  }

  function addEvent (event, callback) {
    if (!window.addEventListener) { // This listener will not be valid in < IE9
      window.attachEvent('on' + event, callback)
    } else { // For all other browsers other than < IE9
      window.addEventListener(event, callback, false)
    }
  }

  function initAllFrames () {

    document.querySelectorAll('iframe:not(.gh-from-iframe)').forEach((frame, i) => {

      let src = frame.src || frame.dataset.src

      // Check if the URL contains the GH managed page
      if (src && src.match(/\/gh\//) && isSameOrigin(src)) {
        frame.id = `gh-frame-${i}`
        frame.classList.add('gh')
        frame.style.height = frame.contentWindow.document.body.offsetHeight + 'px'
        postFrameMessage( frame )
      }
    })
  }

  function postFrameMessage ( frame ) {
    frame.contentWindow.postMessage({ action: 'getFrameSize', id: frame.id }, '*')
  }

  function resizeAllFrames () {
    // inited frames will have the gh class
    document.querySelectorAll('iframe.gh').forEach((frame, i) => {
      postFrameMessage( frame )
    })
  }

  function receiveMessage (event) {
    // console.log( event.data );
    resizeFrame(event.data)
  }

  function resizeFrame (data) {
    if (data.height) {
      let frame = document.getElementById(data.id)
      if (frame) {
        frame.style.height = data.height + 'px'
        frame.style.width = data.width + 'px'
      }
    }
  }

  addEvent('load', initAllFrames)
  addEvent('message', receiveMessage)
  addEvent('resize', resizeAllFrames)

  window.fullFrame = initAllFrames

})()
