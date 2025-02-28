( () => {

  const {
    ModalFrame,
  } = MakeEl

  const vidLibrary = {
    'intro': 'NNtvowSKOQY',
  }

  window.addEventListener('load', () => {

    let params = new URLSearchParams(window.location.search)
    let vidId = params.get('ghvid')
    if (vidLibrary.hasOwnProperty(vidId)) {
      ModalFrame({}, [
        `<iframe width="960" height="540" src="https://www.youtube.com/embed/${ vidLibrary[vidId] }" title="Groundhogg tutorial video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>`,
      ])
    }
  })

} )()
