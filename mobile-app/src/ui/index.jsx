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
export const ListItem = ( { title, subtitle, meta, leading, trailing, control, href, onClick, disabled } ) => {
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

  if ( disabled ) {
    row = <div class="ghm-item is-disabled" aria-disabled="true">{ body }</div>
  } else if ( href ) {
    row = <a class="ghm-item" href={ href }>{ body }</a>
  } else if ( onClick ) {
    row = <button type="button" class="ghm-item" onClick={ onClick }>{ body }</button>
  } else {
    row = <div class="ghm-item" style="cursor:default">{ body }</div>
  }

  return control ? <li class="ghm-item-wrap"><div class="ghm-control">{ control }</div>{ row }</li> : <li>{ row }</li>
}

/**
 * A small heading inside a sheet, for a group within a list ("Recent", "All").
 */
export const Subhead = ( { children } ) => <h3 class="ghm-subhead">{ children }</h3>

export const Empty = ( { children } ) => <div class="ghm-empty">{ children }</div>
/**
 * A placeholder block. Sizes are px numbers or CSS lengths.
 */
export const Skeleton = ( { w = '100%', h = 14, round } ) => (
  <span class={ `ghm-skel ${ round ? 'round' : '' }` } aria-hidden="true" style={ { width: typeof w === 'number' ? w + 'px' : w, height: typeof h === 'number' ? h + 'px' : h } }/>
)

// varied widths, so a column of placeholders reads as content and not as a stack of identical bars
const TITLE_WIDTHS = [ '58%', '46%', '66%', '52%', '40%', '62%' ]
const SUB_WIDTHS = [ '34%', '42%', '28%', '38%', '30%', '36%' ]

/**
 * Placeholder rows while a list loads. `variant` picks what leads each row: an avatar, a checkbox, or nothing.
 */
export const SkeletonList = ( { rows = 3, variant, label = 'Loading' } ) => (
  <div class="ghm-skel-list" role="status" aria-busy="true">
    <span class="ghm-sr">{ label }</span>
    { Array.from( { length: rows }, ( _, i ) => (
      <div class="ghm-item ghm-skel-row" key={ i }>
        { variant === 'avatar' ? <Skeleton round w={ 40 } h={ 40 }/> : null }
        { variant === 'check' ? <Skeleton round w={ 26 } h={ 26 }/> : null }
        <div class="ghm-item-body">
          <Skeleton w={ TITLE_WIDTHS[ i % TITLE_WIDTHS.length ] } h={ 14 }/>
          <Skeleton w={ SUB_WIDTHS[ i % SUB_WIDTHS.length ] } h={ 12 }/>
        </div>
      </div>
    ) ) }
  </div>
)

/**
 * The generic loading state. Features can use it without choosing a shape.
 */
export const Loading = ( props ) => <SkeletonList { ...props }/>
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

/**
 * @param {{options: {value: string, label: string}[], placeholder?: string}} props
 */
export const Select = ( { options, placeholder, ...props } ) => (
  <select class="ghm-select" { ...props }>
    { placeholder !== undefined ? <option value="">{ placeholder }</option> : null }
    { options.map( o => <option value={ o.value } key={ o.value }>{ o.label }</option> ) }
  </select>
)

/**
 * A removable label, for tags.
 */
export const Chip = ( { children, onRemove, removeLabel = 'Remove' } ) => (
  <span class="ghm-chip">
    { children }
    { onRemove ? <button type="button" class="ghm-chip-x" aria-label={ `${ removeLabel } ${ typeof children === 'string' ? children : '' }`.trim() } onClick={ onRemove }><Icon name="close" size={ 14 }/></button> : null }
  </span>
)
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

/**
 * The main button of a sheet. It sticks to the bottom of the sheet, so it is on screen however long the content
 * above it is, and a first-time user never has to look for it (or scroll for it past an open keyboard).
 */
export const SheetFooter = ( { children, ...props } ) => <div class="ghm-sheet-footer" { ...props }>{ children }</div>

export const Toast = ( { message, type } ) => message ? <div class={ `ghm-toast ${ type || '' }` } role="status">{ message }</div> : null
