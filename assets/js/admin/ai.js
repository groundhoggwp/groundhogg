(() => {

  const BUTTON_TEXTS = [
    "Corralling groundhogs",
    "Checking for shadows",
    "Reciting french poetry",
    "Snoozing alarm clocks",
    "Feeding Phil",
    "Avoiding puddles",
    "Delivering weather forecast",
    "Predicting six more weeks of code",
    "Consulting groundhogs",
    "Learning jazz piano",
    "Sculpting ice sculptures",
    "Performing heimlich maneuvers",
    "Changing flat tires",
    "Throwing snowballs",
    "Ordering flapjacks",
    "Trying again",
  ]

  const AiGeneratingText = index => BUTTON_TEXTS[ ((index % BUTTON_TEXTS.length) + BUTTON_TEXTS.length) % BUTTON_TEXTS.length]

  /**
   * Poll our AI endpoint for the response to our prompt
   *
   * @param job_id
   * @returns {Response}
   */
  const pollAiResponse = job_id => new Promise((res, rej) => {
    let interval = setInterval(async () => {
      let jobRes

      try {
        jobRes = await fetch( AI_ENDPOINT + `?job_id=${job_id}` ).then( res => res.json() )

      } catch (err) {
        clearInterval( interval )
        rej( err )
      }

      if ( jobRes.status === 'done' ){
        clearInterval( interval )
        res( jobRes.content )
      }

      if ( jobRes.status === 'error' ){
        clearInterval( interval )
        rej( new Error( jobRes.error ) )
      }

    }, 5000 )
  })

  /**
   * Send a request to Groundhogg's AI using a prompt
   *
   * @param prompt
   * @returns {Promise<any>}
   */
  const promptRequest = async (prompt) => {

    let jobRes = await Groundhogg.api.ajax({
      action: 'generate_job_with_prompt',
      prompt,
    })

    if ( jobRes.error ){
      throw new Error( jobRes.error )
    }

    let jobId = jobRes.job_id

    let content = await pollAiResponse(jobId)

    return JSON.parse(content)
  }

  /**
   * AI on the site's own key, through WordPress's AI client and the keys in Settings › Connectors, see
   * Groundhogg\AI\AI. What it can do comes from the server as Groundhogg.ai.site, on the pages that load this file.
   * The browser asks for a job by name, never with a prompt of its own.
   */
  const { __ } = wp.i18n

  const site = () => ( Groundhogg.ai && Groundhogg.ai.site ) || {}

  // whether the current user can have the site's AI make this kind of thing right now: text or image
  const can = ( kind = 'text' ) => Boolean( site()[ kind ] ) && !site().limitReached

  /**
   * @param {string} job   a job's name, like alt-text
   * @param {Object} input what the job takes
   * @return {Promise<Object>} what the job made, and the tokens it used. Rejects with the reason it couldn't.
   */
  const run = ( job, input = {} ) => Groundhogg.api.post( `${ Groundhogg.api.routes.v4.root }/ai/${ job }`, input )

  const sparkle = `<svg class="gh-ai-icon" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 2l1.9 5.6L17.5 9.5l-5.6 1.9L10 17l-1.9-5.6L2.5 9.5l5.6-1.9L10 2zm8 10l.95 2.55L21.5 15.5l-2.55.95L18 19l-.95-2.55-2.55-.95 2.55-.95L18 12zM5 16l.7 1.8 1.8.7-1.8.7L5 21l-.7-1.8-1.8-.7 1.8-.7L5 16z"/></svg>`

  const showError = message => {
    if ( Groundhogg.element && Groundhogg.element.dialog ) {
      Groundhogg.element.dialog( { type: 'error', message } )
      return
    }

    window.alert( message )
  }

  /**
   * A button with a sparkle that has the site's AI do a job, and hands back what it made
   *
   * @param {Object}          props
   * @param {string}          props.job       the job's name
   * @param {Object|Function} props.input     what the job takes, or a function that returns it when clicked
   * @param {Function}        props.onDone    gets what the job made
   * @param {string}          props.label
   * @param {string}          props.working   the label while it works
   * @param {string}          props.className
   */
  const AiButton = ( {
    job,
    input = {},
    onDone = () => {},
    label = __( 'Write with AI', 'groundhogg' ),
    working = __( 'Writing…', 'groundhogg' ),
    className = 'gh-button secondary small',
    ...rest
  } ) => MakeEl.Button( {
    type     : 'button',
    title    : label,
    ...rest,
    className: `${ className } gh-ai-button`,
    onClick  : async e => {

      const button = e.currentTarget
      const text = button.querySelector( '.gh-ai-label' )

      if ( button.disabled ) {
        return
      }

      button.disabled = true
      button.classList.add( 'gh-ai-working' )
      text.textContent = working

      try {
        onDone( await run( job, typeof input === 'function' ? input() : input ) )
      }
      catch ( err ) {
        showError( err.message )
      }
      finally {
        // the button may have been drawn again by now
        button.disabled = false
        button.classList.remove( 'gh-ai-working' )
        text.textContent = label
      }
    },
  }, [ sparkle, MakeEl.Span( { className: 'gh-ai-label' }, label ) ] )

  Groundhogg.ai = {
    ...Groundhogg.ai,
    AiGeneratingText,
    request: promptRequest,
    poll: async job_id => {
      return JSON.parse( await pollAiResponse(job_id) )
    },
    can,
    run,
    AiButton,
    sparkle,
  }

})()
