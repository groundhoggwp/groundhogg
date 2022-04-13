<?php
/**
 * Dynamic script which can be included on a third party site to enable page Tracking
 *
 * @copyright Groundhogg Inc
 * @since 2.6
 */

define( 'SHORTINIT', true );

if ( ! defined( 'ABSPATH' ) ) {
	/** Set up WordPress environment */
//	require_once( __DIR__ . '/wp-load.php' );
	require_once( "C:\Users\adria\Local Sites\groundhogg\app\public" . '/wp-load.php' );
}


status_header( 200 );
nocache_headers();

header( "Content-Type: application/javascript" );

// generate some kind of secret

?>
( () => {

  const COOKIE = 'gh-page-visits'
  const CID = 'gh-cid'

  /**
   * Post data
   *
   * @param url
   * @param data
   * @param opts
   * @returns {Promise<any>}
   */
  function apiPost (url = '', data = {}, opts = {}) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
      ...opts,
    }).then(r => r.json())
  }

  const DURATION = {
    HOUR: 60 * 60 * 1000,
    MINUTE: 60 * 1000,
    DAY: 24 * 60 * 60 * 1000,
  }

  /**
   * Set a cookie
   *
   * @param cname
   * @param cvalue
   * @param duration
   */
  const setCookie = (cname, cvalue, duration) => {
    var d = new Date()
    d.setTime(d.getTime() + ( duration ))
    var expires = 'expires=' + d.toUTCString()
    document.cookie = cname + '=' + cvalue + ';' + expires + ';path=/'
  }

  /**
   * Retrieve a cookie
   *
   * @param cname name of the cookie
   * @param _default default value
   * @returns {string|null}
   */
  const getCookie = (cname, _default = null) => {
    var name = cname + '='
    var ca = document.cookie.split(';')
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i]
      while (c.charAt(0) == ' ') {
        c = c.substring(1)
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length)
      }
    }
    return _default
  }

  const defaultPageTracking = {
    contact_id: 0,
    lead_source: '',
    pages: [],
    pagesAndTimes: [],
  }

  /**
   * Fetch recently visited pages
   * @return {any}
   */
  const getVisitedPages = () => {
    return JSON.parse(getCookie(COOKIE, JSON.stringify(defaultPageTracking)))
  }

  /**
   * Remember the page visit in the cookie
   */
  const rememberPageVisit = () => {

    const url = new URL(window.location.href)

    const pagesVisited = getVisitedPages()

    pagesVisited.pages.push(url.pathname)
    pagesVisited.pagesAndTimes.push({
      page: url.href,
      time: Date.now() / 1000,
    })

    setCookie(COOKIE, JSON.stringify(pagesVisited), DURATION.HOUR)
  }

  const pageView = () => {

    const url = new URL(window.location.href)

    // Don't run if we recently tracked this page visit
    if (getVisitedPages().pages.includes(url.pathname)) {
      return
    }

    rememberPageVisit()
  }

  const maybeSetCIDCookie = () => {

    let url = new URL( window.location.href )
    let cid = url.searchParams.get ('cid')
    if ( ! cid ){
      return
    }

    cid = parseInt( cid, 16 )

    setCookie( CID, cid, DURATION.DAY * 30 )

    // Remove the cid from the URL
    url.searchParams.delete('cid')

    // Update the URL
    history.pushState({}, '', url)
  }

  window.addEventListener('load', () => {

  })

})()
