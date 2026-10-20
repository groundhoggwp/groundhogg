// Editing a contact from the mobile record: the core fields, and the tags. Both save with one
// PATCH contacts/{id}, which takes { data, meta } and add_tags / remove_tags, and answers with the contact as it
// is now, so the record shows the result without fetching it again.

import { useState } from 'preact/hooks'
import { Button, Chip, Empty, ErrorMessage, Field, Icon, Input, List, ListItem, Select, SkeletonList } from '../ui/index.jsx'
import { changes, initialValues, isEmpty } from '../core/fields.js'
import { countryOptions } from '../core/countries.js'
import { fromApi, hasTag, isUnchanged, resultingTags, tagPayload } from '../core/tags.js'
import { itemOf, useDebounced, useFetch } from '../util.js'

/**
 * Save changes and tell the screen under the sheet.
 */
async function save ( ctx, contact, body ) {
  const res = await ctx.api.patch( `contacts/${ contact.ID }`, body )
  ctx.emit( 'contact:changed', { id: contact.ID, item: itemOf( res ) } )
}

export function EditContactSheet ( { ctx, close, contact } ) {
  const { __ } = ctx.i18n
  const [ values, setValues ] = useState( () => initialValues( contact ) )
  const [ saving, setSaving ] = useState( false )

  const set = key => e => setValues( v => ( { ...v, [ key ]: e.currentTarget.value } ) )

  const submit = async e => {
    e.preventDefault()

    const diff = changes( contact, values )

    // nothing changed, so there is nothing to ask the server
    if ( isEmpty( diff ) ) {
      close()
      return
    }

    setSaving( true )

    try {
      await save( ctx, contact, diff )
      ctx.toast( __( 'Contact updated' ) )
      close()
    } catch ( err ) {
      setSaving( false )
      ctx.toast( err.message, 'error' )
    }
  }

  const field = ( key, label, props = {} ) => (
    <Field label={ label }><Input value={ values[ key ] } onInput={ set( key ) } { ...props }/></Field>
  )

  return (
    <form onSubmit={ submit }>
      <div class="ghm-form-group">
        <h3>{ __( 'Name' ) }</h3>
        <div class="ghm-form-row">
          { field( 'first_name', __( 'First name' ), { autocomplete: 'given-name' } ) }
          { field( 'last_name', __( 'Last name' ), { autocomplete: 'family-name' } ) }
        </div>
      </div>

      <div class="ghm-form-group">
        <h3>{ __( 'Contact' ) }</h3>
        { field( 'email', __( 'Email' ), { type: 'email', required: true, autocomplete: 'email', inputmode: 'email' } ) }
        { field( 'mobile_phone', __( 'Mobile phone' ), { type: 'tel', autocomplete: 'tel', inputmode: 'tel' } ) }
        { field( 'primary_phone', __( 'Phone' ), { type: 'tel', autocomplete: 'tel', inputmode: 'tel' } ) }
      </div>

      <div class="ghm-form-group">
        <h3>{ __( 'Work' ) }</h3>
        { field( 'company_name', __( 'Company' ), { autocomplete: 'organization' } ) }
        { field( 'job_title', __( 'Job title' ), { autocomplete: 'organization-title' } ) }
      </div>

      <div class="ghm-form-group">
        <h3>{ __( 'Address' ) }</h3>
        { field( 'street_address_1', __( 'Street address' ), { autocomplete: 'address-line1' } ) }
        { field( 'street_address_2', __( 'Address line 2' ), { autocomplete: 'address-line2' } ) }
        <div class="ghm-form-row">
          { field( 'city', __( 'City' ), { autocomplete: 'address-level2' } ) }
          { field( 'region', __( 'State / Region' ), { autocomplete: 'address-level1' } ) }
        </div>
        <div class="ghm-form-row">
          { field( 'postal_zip', __( 'Postal code' ), { autocomplete: 'postal-code' } ) }
          <Field label={ __( 'Country' ) }>
            <Select value={ values.country } onChange={ set( 'country' ) } options={ countryOptions( ctx.locale, values.country ) } placeholder="" autocomplete="country"/>
          </Field>
        </div>
      </div>

      <Button variant="primary" block type="submit" disabled={ saving }>{ saving ? __( 'Saving…' ) : __( 'Save' ) }</Button>
    </form>
  )
}

/**
 * Add and remove tags. Changes are staged and sent together on Save, so one can be undone before it goes.
 * A tag that doesn't exist yet can be made by typing its name, and is created when the contact is saved.
 */
export function EditTagsSheet ( { ctx, close, contact } ) {
  const { __, sprintf } = ctx.i18n
  const current = fromApi( contact.tags )

  const [ added, setAdded ] = useState( [] )
  const [ removed, setRemoved ] = useState( [] )
  const [ query, setQuery ] = useState( '' )
  const [ saving, setSaving ] = useState( false )
  const search = useDebounced( query.trim() )

  const { data, error, loading } = useFetch( () => ctx.api.get( 'tags', {
    search: search || undefined,
    orderby: 'tag_name',
    order: 'ASC',
    limit: 20,
  } ), [ search ] )

  const now = resultingTags( current, added, removed )
  const payload = tagPayload( current, added, removed )

  // what to offer: matching tags the contact doesn't have (or isn't about to have)
  const options = fromApi( data && data.items ).filter( t => ! hasTag( now, t ) )
  const typed = query.trim()
  const canCreate = typed && ! fromApi( data && data.items ).some( t => t.name.toLowerCase() === typed.toLowerCase() ) && ! hasTag( now, { name: typed } )

  const add = tag => {
    // putting back something that was about to be removed just cancels the removal
    if ( tag.id && removed.includes( tag.id ) ) {
      setRemoved( r => r.filter( id => id !== tag.id ) )
    } else {
      setAdded( a => [ ...a, tag ] )
    }

    setQuery( '' )
  }

  const remove = tag => {
    if ( tag.id && current.some( t => t.id === tag.id ) ) {
      setRemoved( r => [ ...r, tag.id ] )
    } else {
      setAdded( a => a.filter( t => t.name.toLowerCase() !== tag.name.toLowerCase() ) )
    }
  }

  const submit = async () => {
    if ( isUnchanged( payload ) ) {
      close()
      return
    }

    setSaving( true )

    try {
      await save( ctx, contact, payload )
      ctx.toast( __( 'Tags updated' ) )
      close()
    } catch ( err ) {
      setSaving( false )
      ctx.toast( err.message, 'error' )
    }
  }

  return (
    <>
      <div class="ghm-chips" aria-live="polite">
        { now.length ? now.map( t => (
          <Chip onRemove={ () => remove( t ) } removeLabel={ __( 'Remove' ) } key={ t.id || t.name }>{ t.name }</Chip>
        ) ) : <span class="ghm-item-meta">{ __( 'No tags yet.' ) }</span> }
      </div>

      <Field label={ __( 'Add a tag' ) }>
        <Input type="search" value={ query } onInput={ e => setQuery( e.currentTarget.value ) } placeholder={ __( 'Search or type a new tag' ) } enterkeyhint="search" autocomplete="off"/>
      </Field>

      <ErrorMessage error={ error }/>
      { loading && ! options.length ? <SkeletonList rows={ 3 }/> : null }

      <List>
        { canCreate ? (
          <ListItem leading={ <Icon name="plus"/> } title={ sprintf( __( 'Create "%s"' ), typed ) } onClick={ () => add( { name: typed } ) }/>
        ) : null }
        { options.map( t => <ListItem leading={ <Icon name="plus"/> } title={ t.name } onClick={ () => add( t ) } key={ t.id }/> ) }
      </List>

      { ! loading && ! error && ! options.length && ! canCreate ? <Empty>{ typed ? __( 'No other tags match.' ) : __( 'No more tags to add.' ) }</Empty> : null }

      <div style="margin-top:14px">
        <Button variant="primary" block onClick={ submit } disabled={ saving }>{ saving ? __( 'Saving…' ) : __( 'Save tags' ) }</Button>
      </div>
    </>
  )
}
