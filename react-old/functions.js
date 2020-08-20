export function parseArgs (given, defaults) {

  // remove null or empty values from given
  Object.keys(given).
    forEach((key) => ( given[key] == null || given[key] === '' ) &&
      delete given[key])

  return {
    ...defaults,
    ...given,
  }
}

export function uniqId (prefix = '') {
  return prefix + Math.random().toString(36).substring(2, 15) +
    Math.random().toString(36).substring(2, 15)
}

export function objEquals (obj1, obj2) {
  return JSON.stringify(obj1) === JSON.stringify(obj2)
}

// First, checks if it isn't implemented yet.
if (!String.prototype.format) {
  String.prototype.format = function () {
    var args = arguments
    return this.replace(/{(\d+)}/g, function (match, number) {
      return typeof args[number] != 'undefined'
        ? args[number]
        : match

    })
  }
}

/**
 * Build a simple get request
 *
 * @param uri
 * @param params
 * @returns {string}
 */
export function getRequest (uri, params) {

  const esc = encodeURIComponent
  const query = Object.keys(params).
    map(k => esc(k) + '=' + esc(params[k])).
    join('&')

  let join = uri.match(/\?/gi) ? '&' : '?'

  return uri + join + query
}

/**
 * Check whether a url is valid or not
 * modified to allow for replacement characters
 *
 * @param value
 * @returns {boolean}
 */
export function isValidUrl (value) {
  var pattern = /^(https?:\/\/)?((([a-z\d]([a-z\d-]*[a-z\d])*)\.)+[a-z]{2,}|((\d{1,3}\.){3}\d{1,3})|(localhost))(\:\d+)?(\/[-a-z\d%_.~+]*)*(\?[;&a-z\d%_.~+=\-{}\[\]]*)?(\#[-a-z\d_{}]*)?$/i
  return !!pattern.test(value)
}

/**
 * Root event function
 *
 * @param hook
 * @param args
 */
export function dispatchEvent (hook, args) {
  const event = new CustomEvent(hook, { detail: args })
  document.dispatchEvent(event)
}

/**
 * Root event function
 *
 * @param hook
 * @param callback
 */
export function listenForEvent (hook, callback) {
  document.addEventListener(hook, callback)
}

/**
 * Disable body scrolling
 */
export function disableBodyScrolling () {
  jQuery(function ($) {
    $('body').addClass('disable-scrolling')
  })
}

/**
 * Enable body scrolling
 */
export function enableBodyScrolling () {
  jQuery(function ($) {
    $('body').removeClass('disable-scrolling')
  })
}

export function number_format ( number ) {
  return new Intl.NumberFormat().format(number)
}