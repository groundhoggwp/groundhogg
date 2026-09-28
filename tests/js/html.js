/**
 * Helpers for comparing markup in Node, without a DOM
 */

const VOID = new Set(['input', 'img', 'br', 'hr', 'meta', 'link'])

const escAttr = value => String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;')

const attributeName = name => {
  if (name === 'className') {
    return 'class'
  }

  // MakeEl turns dataFooBar into data-foo-bar
  if (/^data[A-Z]/.test(name)) {
    return 'data-' + name.slice(4).replace(/[A-Z]/g, c => '-' + c.toLowerCase()).replace(/^-/, '')
  }

  return name
}

const styleString = style => typeof style === 'string'
  ? style
  : Object.entries(style).map(([key, value]) => `${ key.replace(/[A-Z]/g, c => '-' + c.toLowerCase()) }: ${ value }`).join('; ')

/**
 * Builds HTML strings with MakeEl's signature, for drawing the canvas in Node
 *
 * @param tag string
 * @param attributes Object
 * @param children
 * @return string
 */
const h = (tag, attributes = {}, children = null) => {

  const attrs = Object.entries(attributes ?? {}).
    filter(([, value]) => value !== false && value !== null && value !== undefined).
    map(([name, value]) => `${ attributeName(name) }="${ escAttr(name === 'style' ? styleString(value) : value) }"`).
    join(' ')

  const open = `<${ tag }${ attrs ? ' ' + attrs : '' }>`

  if (VOID.has(tag)) {
    return open
  }

  const inner = ( Array.isArray(children) ? children : [children] ).flat(Infinity).filter(child => child || child === 0).join('')

  return `${ open }${ inner }</${ tag }>`
}

/**
 * Markup as a list of tokens that ignores formatting: whitespace, attribute order, class order, empty attributes,
 * comments, and self-closing slashes
 *
 * @param html string
 * @return string[]
 */
const tokens = html => {

  const out = []
  const re = /<!--[\s\S]*?-->|<[!?][^>]*>|<\/\s*([a-zA-Z][\w:-]*)\s*>|<([a-zA-Z][\w:-]*)((?:\s+[^\s=>/]+(?:\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+))?)*)\s*\/?>|([^<]+)/g

  let match

  while (( match = re.exec(html) )) {

    const [whole, closing, opening, attrs, text] = match

    if (closing) {
      if (!VOID.has(closing.toLowerCase())) {
        out.push(`</${ closing.toLowerCase() }>`)
      }
      continue
    }

    if (opening) {

      const parsed = []
      const attrRe = /([^\s=>/]+)(?:\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s>]+)))?/g
      let attr

      while (( attr = attrRe.exec(attrs) )) {
        let [, name, dq, sq, bare] = attr
        let value = dq ?? sq ?? bare ?? ''
        name = name.toLowerCase()

        if (name === 'class') {
          value = [...new Set(value.split(/\s+/).filter(Boolean))].sort().join(' ')
        }

        if (name === 'style') {
          value = value.replace(/\s+/g, '').replace(/;$/, '')
        }

        if (value === '') {
          continue
        }

        parsed.push(`${ name }="${ value }"`)
      }

      out.push(`<${ [opening.toLowerCase(), ...parsed.sort()].join(' ') }>`)
      continue
    }

    if (text !== undefined) {
      const trimmed = text.replace(/\s+/g, ' ').trim()
      if (trimmed) {
        out.push(trimmed)
      }
      continue
    }

    // comments, doctypes, and <?xml ?> are ignored
  }

  return out
}

module.exports = {
  h,
  tokens,
}
