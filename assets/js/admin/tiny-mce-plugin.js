(function () {

  tinymce.PluginManager.add('Groundhogg', function (ed, url) {
    console.log('here')

    ed.addButton('groundhoggreplacementbtn', {
      title: 'Emojis',
      icon: false,
      onclick() {
        alert( 'here' )
      },
      text: 'Emoji'
    })

    ed.addButton('groundhoggemojibtn', {
      title: 'Emojis',
      cmd: 'GroundhoggEmojiBtnCmd',
      text: 'Replacement',
      icon: false,
    })

    ed.addCommand('GroundhoggReplacementBtnCmd', function () {

    })

    ed.addCommand('GroundhoggEmojiBtnCmd', function () {

    })
  })
})()