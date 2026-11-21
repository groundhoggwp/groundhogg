/**
 * Adds UTM parameters to links to groundhogg.io when they are clicked in wp-admin.
 *
 * The scheme is described in docs/utm-links.md. utm_url() in includes/functions.php is the PHP side.
 */
( () => {

  const config = window.GroundhoggUTM

  if ( !config || !config.params ) {
    return
  }

  const SOURCE = 'groundhogg-plugin'

  // utm_source values from our older schemes, still in add-on plugin headers. Mirrors UTM_LEGACY_SOURCES in PHP.
  const LEGACY_SOURCES = ['wp-plugins', 'plugin', 'admin-menu']

  const isGroundhoggIo = host => host === 'groundhogg.io' || host.endsWith('.groundhogg.io')

  const slug = value => String(value ?? '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').substring(0, 60)

  const CORE_SCREENS = {
    'plugins.php'       : 'plugins',
    'plugin-install.php': 'plugin-install',
    'update-core.php'   : 'update-core',
    'index.php'         : 'wp-dashboard',
    'profile.php'       : 'profile',
    'users.php'         : 'users',
  }

  /**
   * The campaign for the current screen, mirrors utm_campaign_for_screen() in PHP.
   *
   * Uses the current location so screens that change their URL without a reload stay accurate.
   */
  const screenCampaign = () => {
    const query = new URLSearchParams(window.location.search)
    const page = query.get('page') ?? ''

    if ( page === 'groundhogg' || page.startsWith('gh_') ) {
      let parts = [page === 'groundhogg' ? 'dashboard' : page.replace(/^gh_/, '')]
      for ( let key of ['tab', 'action'] ) {
        if ( query.get(key) ) {
          parts.push(query.get(key))
        }
      }
      return slug(parts.join('-'))
    }

    let file = window.location.pathname.split('/').pop() || 'index.php'

    return CORE_SCREENS[file] ?? 'other-screen'
  }

  /**
   * Add UTM parameters to a groundhogg.io URL.
   *
   * @param {string} href
   * @param {Object} [args]
   * @param {string} [args.content] the placement, e.g. 'upgrade-button'
   * @param {string} [args.campaign] defaults to the current screen
   * @param {string} [args.medium] defaults to 'wp-admin'
   * @returns {string}
   */
  const utmUrl = (href, { content = '', campaign = '', medium = '' } = {}) => {

    let url

    try {
      url = new URL(href, window.location.href)
    }
    catch ( e ) {
      return href
    }

    if ( !['http:', 'https:'].includes(url.protocol) || !isGroundhoggIo(url.hostname) ) {
      return href
    }

    let source = url.searchParams.get('utm_source')
    let changed = false

    if ( LEGACY_SOURCES.includes(source) ) {
      // the old campaign named the placement, e.g. the plugin-uri of an add-on's header
      content = content || url.searchParams.get('utm_campaign') || ''
      for ( let key of [...url.searchParams.keys()].filter(key => key.startsWith('utm_')) ) {
        url.searchParams.delete(key)
      }
      changed = true
    }
    // someone else's scheme, such as the affiliate links
    else if ( source && source !== SOURCE ) {
      return href
    }

    let params = {
      ...config.params,
      utm_campaign: slug(campaign) || config.params.utm_campaign || screenCampaign(),
    }

    if ( medium ) {
      params.utm_medium = slug(medium)
    }

    if ( content ) {
      params.utm_content = slug(content)
    }

    for ( let [key, value] of Object.entries(params) ) {
      if ( value && !url.searchParams.has(key) ) {
        url.searchParams.set(key, value)
        changed = true
      }
    }

    return changed ? url.toString() : href
  }

  const closestData = (el, key) => el.closest(`[data-utm-${ key }]`)?.getAttribute(`data-utm-${ key }`) ?? ''

  /**
   * Links being written rather than followed: an editor would save the tags into the content.
   * The email editor's canvas, anything contenteditable, and whatever opts out with data-utm-skip.
   */
  const SKIP = '[contenteditable]:not([contenteditable="false"]), #builder-content, [data-utm-skip]'

  const tagAnchor = link => {

    let href = link.getAttribute('href')

    // cheap check before parsing
    if ( !href || !href.includes('groundhogg.io') || link.closest(SKIP) ) {
      return
    }

    let tagged = utmUrl(href, {
      content : closestData(link, 'content'),
      campaign: closestData(link, 'campaign'),
      medium  : closestData(link, 'medium'),
    })

    if ( tagged !== href ) {
      link.setAttribute('href', tagged)
    }
  }

  const SELECTOR = 'a[href*="groundhogg.io"]'

  const tagWithin = node => {
    if ( node.nodeType !== Node.ELEMENT_NODE ) {
      return
    }

    if ( node.matches(SELECTOR) ) {
      tagAnchor(node)
    }

    node.querySelectorAll(SELECTOR).forEach(tagAnchor)
  }

  // Tag links as they appear, so the tags are there on hover and when copied
  tagWithin(document.documentElement)

  new MutationObserver(records => {
    for ( let record of records ) {
      if ( record.type === 'attributes' ) {
        tagWithin(record.target)
        continue
      }
      record.addedNodes.forEach(tagWithin)
    }
  }).observe(document.documentElement, {
    childList      : true,
    subtree        : true,
    attributes     : true,
    attributeFilter: ['href'],
  })

  // Links the observer can't see, such as in a shadow root, are tagged when they're used.
  // mousedown and contextmenu run before "open in new tab" and "copy link", click covers the keyboard
  const tagLink = e => {
    let link = e.composedPath().find(el => el instanceof HTMLAnchorElement && el.hasAttribute('href'))

    if ( link ) {
      tagAnchor(link)
    }
  }

  for ( let event of ['mousedown', 'click', 'auxclick', 'contextmenu'] ) {
    document.addEventListener(event, tagLink, true)
  }

  config.url = utmUrl

} )()
