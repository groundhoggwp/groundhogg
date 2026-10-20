( function () {

  const { commands = [] } = window.GroundhoggCommands

  const store = wp.data.dispatch('core/commands')

  /**
   * Things a command can do instead of going to a url, keyed by the name given as the command's action.
   * Each receives the args of the command.
   */
  const actions = {

    set_admin_theme: async ({ theme, nonce, stylesheet }) => {

      const response = await fetch(window.ajaxurl, {
        method     : 'POST',
        credentials: 'same-origin',
        body       : new URLSearchParams({ action: 'gh_set_admin_theme', _wpnonce: nonce, theme }),
      })

      const { success } = await response.json()

      if (!success) {
        return
      }

      // themes only apply to Groundhogg pages
      if (!document.body.classList.contains('groundhogg-admin-page')) {
        return
      }

      // the stylesheet is only loaded for users who already have a theme
      if (!document.getElementById('groundhogg-admin-themes-css')) {
        const link = document.createElement('link')
        link.id = 'groundhogg-admin-themes-css'
        link.rel = 'stylesheet'
        link.href = stylesheet
        document.head.appendChild(link)
      }

      document.body.classList.remove(...[...document.body.classList].filter(c => c.startsWith('gh-theme-')))

      if (theme !== 'default') {
        document.body.classList.add(`gh-theme-${ theme }`)
      }
    },
  }

  window.GroundhoggCommands.actions = actions

  commands.forEach(({ name, label, url, action, args, category, keywords }) => store.registerCommand({
    name,
    label,
    searchLabel: label,
    category,
    keywords,
    callback: ({ close }) => {
      close()

      if (url) {
        window.location.href = url
        return
      }

      if (actions.hasOwnProperty(action)) {
        actions[action](args)
      }
    },
  }))

} )()
