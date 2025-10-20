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

  Groundhogg.ai = {
    ...Groundhogg.ai,
    AiGeneratingText,
    request: promptRequest,
    poll: async job_id => {
      return JSON.parse( await pollAiResponse(job_id) )
    }
  }

})()
