// "Fill out a form" on a contact: pick one of the site's internal forms, fill it in, and submit it for them.
//
// The server renders the form to HTML (pre-filled for the contact) and takes the submission at
// POST forms/{id}/admin, the same as the desktop contact card. Fields, validation messages and what happens on
// submit (tags, flows, notifications) are all the form's own settings.

import { useEffect, useRef, useState } from 'preact/hooks'
import { Button, Card, Empty, ErrorMessage, Icon, Input, List, ListItem, SkeletonList } from '../ui/index.jsx'
import { formEntries, identitySelector, isUnsafeAttribute, UNSAFE_TAGS, withContact } from '../core/forms.js'
import { contactName, itemOf, useDebounced, useFetch } from '../util.js'

/**
 * The contact action handler.
 */
export function openForm ( { contact, ctx } ) {
  ctx.sheet( FormSheet, { title: ctx.i18n.__( 'Fill out a form' ), contact } )
}

/**
 * The inside of a rendered form, with anything that could run code taken out. It is the site's own form,
 * built by the server and escaped there, but the app shouldn't run something just because it was in the markup.
 *
 * @param {string} html
 * @returns {string}
 */
function formInnerHtml ( html ) {
  const doc = new DOMParser().parseFromString( html, 'text/html' )
  const form = doc.querySelector( 'form' )

  if ( ! form ) {
    return ''
  }

  form.querySelectorAll( UNSAFE_TAGS.join( ',' ) ).forEach( el => el.remove() )

  form.querySelectorAll( '*' ).forEach( el => {
    [ ...el.attributes ].forEach( attr => {
      if ( isUnsafeAttribute( attr.name, attr.value ) ) {
        el.removeAttribute( attr.name )
      }
    } )
  } )

  return form.innerHTML
}

function FormSheet ( { ctx, close, contact } ) {
  const [ picked, setPicked ] = useState( null )

  return picked
    ? <FillForm ctx={ ctx } close={ close } contact={ contact } form={ picked } back={ () => setPicked( null ) }/>
    : <PickForm ctx={ ctx } contact={ contact } onPick={ setPicked }/>
}

function PickForm ( { ctx, contact, onPick } ) {
  const { __ } = ctx.i18n
  const [ query, setQuery ] = useState( '' )
  const search = useDebounced( query.trim() )

  // passing the contact is what pre-fills each form with what we already know about them
  const { data, error, loading } = useFetch( () => ctx.api.get( 'forms', {
    active: true,
    contact: contact.ID,
    search: search || undefined,
  } ), [ search ] )

  const forms = formEntries( data && data.items )

  return (
    <>
      <div style="margin-bottom:12px">
        <Input type="search" placeholder={ __( 'Search forms' ) } aria-label={ __( 'Search forms' ) } value={ query } onInput={ e => setQuery( e.currentTarget.value ) } enterkeyhint="search" autocomplete="off"/>
      </div>
      <ErrorMessage error={ error }/>
      { loading && ! forms.length ? <SkeletonList rows={ 4 }/> : null }
      { ! loading && ! error && ! forms.length ? <Empty>{ search ? __( 'No forms found.' ) : __( 'There are no forms to fill out.' ) }</Empty> : null }
      { forms.length ? (
        <Card><List>
          { forms.map( f => <ListItem leading={ <Icon name="form"/> } title={ f.name } onClick={ () => onPick( f ) } trailing={ <Icon name="chevron"/> } key={ f.id }/> ) }
        </List></Card>
      ) : null }
    </>
  )
}

function FillForm ( { ctx, close, contact, form, back } ) {
  const { __ } = ctx.i18n
  const ref = useRef( null )
  const [ sending, setSending ] = useState( false )
  const [ error, setError ] = useState( null )
  const [ locked, setLocked ] = useState( false )

  // set the markup once. Preact must not re-render it, the person's answers live in the fields
  useEffect( () => {
    if ( ref.current ) {
      ref.current.innerHTML = formInnerHtml( form.html )

      // the email says which contact the server saves this to, see IDENTITY_FIELDS
      const identity = [ ...ref.current.querySelectorAll( identitySelector() ) ]
      identity.forEach( el => el.setAttribute( 'readonly', '' ) )
      setLocked( identity.length > 0 )
    }
  }, [ form.id ] )

  const submit = async e => {
    e.preventDefault()
    setError( null )

    // read the answers before the fields are disabled, a disabled field isn't part of the form data
    const data = withContact( new FormData( ref.current ), contact.ID )
    setSending( true )

    try {
      const res = await ctx.api.postForm( `forms/${ form.id }/admin`, data )
      const item = res.contact && res.contact.ID ? res.contact : itemOf( res )

      ctx.toast( __( 'Form submitted' ) )
      ctx.emit( 'contact:changed', { id: contact.ID, item } )
      close()
    } catch ( err ) {
      setSending( false )
      setError( err )
    }
  }

  return (
    <>
      <div style="margin:-6px 0 10px"><Button icon="back" onClick={ back } disabled={ sending }>{ __( 'Forms' ) }</Button></div>
      <h3 class="ghm-form-title">{ form.name }</h3>
      <p class="ghm-item-meta" style="margin-bottom:14px">{ __( 'For' ) } { contactName( contact ) }</p>
      { locked ? <p class="ghm-item-meta" style="margin-bottom:12px">{ __( 'The email can\'t be changed in a form. Use Edit on the contact to change it.' ) }</p> : null }
      <ErrorMessage error={ error }/>
      <form class="ghm-form" ref={ ref } onSubmit={ submit } aria-busy={ sending } style={ sending ? 'opacity:.6;pointer-events:none' : undefined }/>
    </>
  )
}
