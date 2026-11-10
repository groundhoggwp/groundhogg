/**
 * "Build with AI" on the add screens of emails and flows.
 *
 * A card among the templates opens a popup where someone describes what they want, then copies a prompt that has
 * their AI assistant build it over MCP. The prompt comes from the server, see Groundhogg\Mcp\Build_With_AI, with
 * {{goal}} standing in for the description.
 *
 * The email editor draws the card itself, with Groundhogg.buildWithAI.Card(). On the flow add screen it goes first in
 * the grid of templates marked with data-build-with-ai.
 */
( () => {

  if ( typeof GroundhoggBuildWithAI === 'undefined' ) {
    return
  }

  const { __ } = wp.i18n

  const {
    Div,
    Span,
    Button,
    Textarea,
    Label,
    Pg,
    An,
    H2,
    ModalWithHeader,
  } = MakeEl

  const {
    type,
    prompt,
    examples = [],
    connected,
    setupUrl,
  } = GroundhoggBuildWithAI

  const text = {
    email: {
      title      : __( 'Build an email with AI', 'groundhogg' ),
      description: __( 'Describe the email you want and your AI assistant writes it, ready for you to review and edit.', 'groundhogg' ),
      placeholder: __( 'A welcome email for new newsletter subscribers that links to our three most popular blog posts.', 'groundhogg' ),
      after      : __( 'When it\'s done, you\'ll find the email in your emails list as a draft.', 'groundhogg' ),
    },
    flow : {
      title      : __( 'Build a flow with AI', 'groundhogg' ),
      description: __( 'Describe the automation you want and your AI assistant builds the flow, ready for you to review and edit.', 'groundhogg' ),
      placeholder: __( 'When someone fills out the contact form, send a thank you email, wait two days, then follow up if they haven\'t booked a call.', 'groundhogg' ),
      after      : __( 'When it\'s done, you\'ll find the flow in your flows list. It stays inactive until you turn it on.', 'groundhogg' ),
    },
  }[type]

  const sparkles = `<svg class="build-with-ai-icon" width="48" height="48" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 2l1.9 5.6L17.5 9.5l-5.6 1.9L10 17l-1.9-5.6L2.5 9.5l5.6-1.9L10 2zm8 10l.95 2.55L21.5 15.5l-2.55.95L18 19l-.95-2.55-2.55-.95 2.55-.95L18 12zM5 16l.7 1.8 1.8.7-1.8.7L5 21l-.7-1.8-1.8-.7 1.8-.7L5 16z"/></svg>`

  // kept between openings, so closing the popup by accident doesn't lose what was typed
  let goal = ''

  const fullPrompt = () => prompt.split( '{{goal}}' ).join( goal.trim() || __( 'Ask me what I want before you start.', 'groundhogg' ) )

  const setGoal = value => {
    goal = value
    document.getElementById( 'build-with-ai-prompt' ).value = fullPrompt()
  }

  // start from an example instead of a blank box, then change it as they like
  const Examples = () => examples.length ? Div( {
    className: 'build-with-ai-examples',
  }, [
    Span( { className: 'build-with-ai-examples-label' }, __( 'Start from an example:', 'groundhogg' ) ),
    ...examples.map( example => Button( {
      type     : 'button',
      className: 'gh-button secondary small',
      title    : example.text,
      onClick  : () => {
        const field = document.getElementById( 'build-with-ai-goal' )
        field.value = example.text
        setGoal( example.text )
        field.focus()
      },
    }, example.label ) ),
  ] ) : null

  // older browsers, a page that isn't on https, and a clipboard the browser won't let us write to
  const copyBySelecting = value => {
    const field = document.createElement( 'textarea' )
    field.value = value
    document.body.appendChild( field )
    field.select()
    document.execCommand( 'copy' )
    field.remove()
  }

  const copy = value => {

    if ( navigator.clipboard && navigator.clipboard.writeText ) {
      return navigator.clipboard.writeText( value ).catch( () => copyBySelecting( value ) )
    }

    copyBySelecting( value )

    return Promise.resolve()
  }

  const open = () => ModalWithHeader( {
    header   : text.title,
    width    : '640px',
    className: 'build-with-ai-modal',
    onOpen   : () => document.getElementById( 'build-with-ai-goal' ).focus(),
  }, () => Div( {
    className: 'build-with-ai display-flex column gap-10',
  }, [
    connected ? null : Div( {
      className: 'build-with-ai-connect',
    }, [
      Span( {}, __( 'Your AI assistant needs to be connected to this site to build this. Already connected one? Carry on.', 'groundhogg' ) ),
      An( {
        className: 'gh-button secondary small',
        href     : setupUrl,
        target   : '_blank',
        rel      : 'noopener',
      }, __( 'Connect an assistant', 'groundhogg' ) ),
    ] ),
    Label( { for: 'build-with-ai-goal' }, __( '1. Describe what you want', 'groundhogg' ) ),
    Examples(),
    Textarea( {
      id         : 'build-with-ai-goal',
      className  : 'full-width',
      rows       : 4,
      placeholder: text.placeholder,
      value      : goal,
      onInput    : e => setGoal( e.target.value ),
    } ),
    Label( { for: 'build-with-ai-prompt' }, __( '2. Copy this prompt into your AI assistant', 'groundhogg' ) ),
    Textarea( {
      id       : 'build-with-ai-prompt',
      className: 'full-width code',
      rows     : 10,
      readonly : true,
      value    : fullPrompt(),
      onFocus  : e => e.target.select(),
    } ),
    Div( {
      className: 'display-flex gap-10 align-center space-between',
    }, [
      Pg( { className: 'description no-margin' }, __( 'Works with any assistant that supports MCP, like ChatGPT, Claude, Cursor or Gemini.', 'groundhogg' ) ),
      Button( {
        id       : 'build-with-ai-copy',
        className: 'gh-button primary',
        onClick  : e => {
          const button = e.currentTarget
          copy( fullPrompt() ).then( () => {
            button.textContent = __( 'Copied!', 'groundhogg' )
            setTimeout( () => {
              button.textContent = __( 'Copy prompt', 'groundhogg' )
            }, 1800 )
          } )
        },
      }, __( 'Copy prompt', 'groundhogg' ) ),
    ] ),
    Pg( { className: 'no-margin' }, text.after ),
  ] ) )

  /**
   * The card that opens the popup, drawn like one of the templates it sits with.
   *
   * @param {string} className extra classes, e.g. the grid column it spans
   */
  const Card = ( className = '' ) => Div( {
    id       : 'build-with-ai-card',
    className: `gh-panel build-with-ai-card ${ className }`,
    role     : 'button',
    tabindex : 0,
    onClick  : open,
    onKeydown: e => {
      if ( e.key === 'Enter' || e.key === ' ' ) {
        e.preventDefault()
        open()
      }
    },
  }, [
    sparkles,
    H2( {}, text.title ),
    Pg( {}, text.description ),
    Span( { className: 'gh-button primary' }, __( 'Get started', 'groundhogg' ) ),
  ] )

  Groundhogg.buildWithAI = {
    Card,
    open,
  }

  const grid = document.querySelector( '[data-build-with-ai]' )

  if ( grid ) {
    grid.prepend( Card() )
  }

} )()
