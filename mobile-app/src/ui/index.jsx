// The shared UI kit, handed to features as ctx.ui so every add-on looks like the same app.

import { useEffect, useRef } from 'preact/hooks'
import { Icon } from './icons.jsx'

export { Icon }

export const Button = ( { variant, block, icon, children, class: cls = '', ...props } ) => (
  <button type="button" class={ `ghm-btn ${ variant || '' } ${ block ? 'block' : '' } ${ cls }` } { ...props }>
    { icon ? <Icon name={ icon }/> : null }{ children }
  </button>
)

export const Card = ( { title, action, children, ...props } ) => (
  <section class="ghm-card" { ...props }>
    { title ? <h3 class="ghm-card-title"><span>{ title }</span>{ action }</h3> : null }
    { children }
  </section>
)

export const List = ( { children } ) => <ul class="ghm-list">{ children }</ul>

/**
 * A row. With an href or onClick it is tappable. `control` is an interactive element (a checkbox) that sits
 * beside the tappable part rather than inside it, since a button can't live inside a link.
 */
export const ListItem = ( { title, subtitle, meta, leading, trailing, control, href, onClick } ) => {
  const body = (
    <>
      { leading }
      <div class="ghm-item-body">
        <div class="ghm-item-title">{ title }</div>
        { subtitle ? <div class="ghm-item-sub">{ subtitle }</div> : null }
      </div>
      { meta ? <div class="ghm-item-meta">{ meta }</div> : null }
      { trailing }
    </>
  )

  let row

  if ( href ) {
    row = <a class="ghm-item" href={ href }>{ body }</a>
  } else if ( onClick ) {
    row = <button type="button" class="ghm-item" onClick={ onClick }>{ body }</button>
  } else {
    row = <div class="ghm-item" style="cursor:default">{ body }</div>
  }

  return control ? <li class="ghm-item-wrap"><div class="ghm-control">{ control }</div>{ row }</li> : <li>{ row }</li>
}

export const Empty = ( { children } ) => <div class="ghm-empty">{ children }</div>
export const Loading = ( { children = '…' } ) => <div class="ghm-loading" role="status">{ children }</div>
export const ErrorMessage = ( { error } ) => error ? <div class="ghm-error" role="alert">{ error.message || String( error ) }</div> : null

export const Pill = ( { color, children } ) => <span class={ `ghm-pill ${ color || '' }` }>{ children }</span>

export const Avatar = ( { name = '', src, large } ) => {
  const initials = name.split( /\s+/ ).filter( Boolean ).slice( 0, 2 ).map( w => w[ 0 ].toUpperCase() ).join( '' )

  return (
    <div class={ `ghm-avatar ${ large ? 'lg' : '' }` }>
      { initials }
      { src ? <img src={ src } alt="" loading="lazy" onError={ e => e.currentTarget.remove() }/> : null }
    </div>
  )
}

export const Field = ( { label, children } ) => (
  <label class="ghm-field">{ label ? <span>{ label }</span> : null }{ children }</label>
)

export const Input = props => <input class="ghm-input" { ...props }/>
export const Textarea = props => <textarea class="ghm-textarea" { ...props }/>

export const Segmented = ( { options, value, onChange } ) => (
  <div class="ghm-seg" role="tablist">
    { options.map( o => (
      <button type="button" role="tab" aria-selected={ o.value === value } class={ o.value === value ? 'is-active' : '' } onClick={ () => onChange( o.value ) }>
        { o.label }
      </button>
    ) ) }
  </div>
)

/**
 * A bottom sheet. Closes on the scrim and on Escape.
 */
export const Sheet = ( { title, onClose, children } ) => {
  const ref = useRef( null )

  useEffect( () => {
    const onKey = e => e.key === 'Escape' && onClose()
    document.addEventListener( 'keydown', onKey )
    // move focus in, so a keyboard or screen reader user isn't left behind the scrim
    ref.current && ref.current.focus()

    return () => document.removeEventListener( 'keydown', onKey )
  }, [] )

  return (
    <>
      <div class="ghm-scrim" onClick={ onClose }/>
      <div class="ghm-sheet" role="dialog" aria-modal="true" aria-label={ title } tabIndex={ -1 } ref={ ref }>
        <div class="ghm-sheet-grab"/>
        { title ? <h2>{ title }</h2> : null }
        { children }
      </div>
    </>
  )
}

export const Toast = ( { message, type } ) => message ? <div class={ `ghm-toast ${ type || '' }` } role="status">{ message }</div> : null
