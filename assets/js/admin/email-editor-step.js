(function ($) {

  const {
    slot,
    fill,
    slotsDemounted,
    slotsMounted,
    registerStepType,
    updateCurrentStepMeta,
    stepTitle,
    renderStepEdit,
    getCurrentStep,
    getCurrentStepMeta,
    getSteps
  } = Groundhogg.funnelEditor.functions

  const { select, input, tinymceElement, specialChars } = Groundhogg.element

  fill('beforeStepNotes.send_email', {
    render ({ data, meta }) {

      const { email_id } = meta

      if (!email_id) {
        return ''
      }

      const ownerOptions = {}
      Groundhogg.filters.owners.forEach(owner => {
        ownerOptions[owner.ID] = `${owner.data.display_name} (${owner.data.user_email})`
      })

      //language=html
      return `
		  <div id="email-options" class="panel">
			  <div class="email-actions row">
				  <button class="gh-button secondary send-test-email">Send test email</button>
				  <button class="gh-button icon preview-mobile">
					  <svg viewBox="0 0 13 19" fill="none" xmlns="http://www.w3.org/2000/svg">
						  <path
							  d="M8.963 15.776a.75.75 0 000-1.5v1.5zm-5.196-1.5a.75.75 0 100 1.5v-1.5zM8.963 1.487v.75-.75zm-5.196 0v-.75.75zM1.458 15.026h.75-.75zm0-11.077h-.75.75zm7.505 13.539v-.75.75zm-5.196 0v.75-.75zm7.505-2.462h.75-.75zm0-11.077h-.75.75zM8.962.737H3.768v1.5h5.196v-1.5zM2.209 15.026V3.949h-1.5v11.077h1.5zm6.755 1.712H3.767v1.5h5.196v-1.5zm3.059-1.712V3.949h-1.5v11.077h1.5zm-3.06 3.212c1.735 0 3.06-1.484 3.06-3.212h-1.5c0 .991-.743 1.712-1.56 1.712v1.5zm0-16c.817 0 1.56.72 1.56 1.711h1.5c0-1.728-1.325-3.212-3.06-3.212v1.5zM3.768.737C2.033.737.708 2.22.708 3.948h1.5c0-.991.742-1.712 1.56-1.712v-1.5zM.708 15.025c0 1.728 1.325 3.212 3.06 3.212v-1.5c-.818 0-1.56-.72-1.56-1.712h-1.5zm3.06.75h5.195v-1.5H3.767v1.5z"
							  fill="#0075FF"/>
					  </svg>
				  </button>
				  <button class="gh-button icon preview-desktop">
					  <svg viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
						  <path
							  d="M15.49 13.795v-.75.75zm-12.7 0v.75-.75zM16.645 2.718h.75-.75zm0 9.846h-.75.75zM1.635 2.718h-.75.75zm0 9.846h.75-.75zM15.49 1.487v.75-.75zm-12.7 0v-.75.75zm13.855 9.981a.75.75 0 000-1.5v1.5zm-15.01-1.5a.75.75 0 100 1.5v-1.5zm10.969 8.27a.75.75 0 000-1.5v1.5zm-6.928-1.5a.75.75 0 000 1.5v-1.5zm.405.75a.75.75 0 001.5 0h-1.5zm1.5-3.693a.75.75 0 00-1.5 0h1.5zm3.118 3.693a.75.75 0 101.5 0h-1.5zm1.5-3.693a.75.75 0 00-1.5 0h1.5zm3.29-.75H2.79v1.5h12.7v-1.5zm.405-10.327v9.846h1.5V2.718h-1.5zm-15.009 0v9.846h1.5V2.718h-1.5zM15.49.738H2.79v1.5h12.7v-1.5zm1.905 1.98c0-1.048-.809-1.98-1.905-1.98v1.5c.179 0 .405.169.405.48h1.5zm-15.01 0c0-.311.226-.48.405-.48v-1.5C1.694.738.885 1.67.885 2.718h1.5zm.405 10.327c-.18 0-.405-.17-.405-.48h-1.5c0 1.048.809 1.98 1.905 1.98v-1.5zm12.7 1.5c1.096 0 1.905-.932 1.905-1.98h-1.5c0 .31-.226.48-.405.48v1.5zm1.155-4.577H1.635v1.5h15.01v-1.5zm-4.041 6.77H5.676v1.5h6.928v-1.5zm-5.023.75v-3.693h-1.5v3.693h1.5zm4.618 0v-3.693h-1.5v3.693h1.5z"
							  fill="#0075FF"/>
					  </svg>
				  </button>
			  </div>
			  <div class="row">
				  <label class="row-label">Send this email from...</label>
				  ${select({
					  id: 'from-user',
					  name: 'form_user'
				  }, ownerOptions)}
			  </div>
			  <div class="row">
				  <label class="row-label">Replies are sent to...</label>
				  ${input({
					  id: 'reply-to',
					  name: 'reply_to'
				  })}
			  </div>
			  <div id="alignment-and-message-type" class="row">
				  <div class="col">
					  <label class="row-label">Alignment</label>
					  <div id="alignment">
						  <button class="gh-button icon align-left">
							  <svg viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
								  <path d="M1.593 13.166h5.67m-5.67-4.123h11.34M1.592.796h11.34M1.592 4.919h5.67"
								        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
								        stroke-linejoin="round"/>
							  </svg>
						  </button>
						  <button class="gh-button icon align-center">
							  <svg viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
								  <path opacity=".6"
								        d="M12.347 9.003H1.007M12.347.756H1.007m8.763 12.37H4.1m5.67-8.247H4.1"
								        stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
								        stroke-linejoin="round"/>
							  </svg>
						  </button>
					  </div>
				  </div>
				  <div class="row">
					  <label class="row-label">Message Type</label>
					  ${select({
						  id: 'message-type',
						  name: 'message_type'
					  }, {
						  marketing: 'Marketing',
						  transactional: 'Transactional'
					  })}
				  </div>
			  </div>
		  </div>`
    }
  })

  fill('afterStepSettings.send_email', {
    render ({ data, meta }) {

      const { email_id } = meta

      if (!email_id) {
        return ''
      }

      const headers = {
        'x-header': 'x-value'
      }

      const keyValueRow = (key, value, index) => {
        //language=html
        return `
			<div class="key-value-row">
				<input data-key="${index}" class="header-key" type="text" value="${specialChars(key)}"
				       placeholder="key"/>
				<div class="input-wrap input-with-replacements">
					<input data-key="${index}" type="text" value="${specialChars(value)}"
					       class="regular-text header-value"
					       placeholder="value">
					<button class="replacements-picker-start" title="insert replacement"><span
						class="dashicons dashicons-admin-users"></span></button>
				</div>
				<button class="remove" data-key="${index}"><span class="dashicons dashicons-no-alt"></span></button>
			</div>`
      }

      const headerRows = []

      for (const header in headers) {
        if (headers.hasOwnProperty(header)) {
          headerRows.push({
            key: header,
            value: headers[header]
          })
        }
      }

      //language=html
      return `
		  <div id="email-editor" class="panel">
			  <div class="row">
				  <div class="inline-label subject-wrap">
					  <label>Subject:</label>
					  ${input({
						  name: 'subject',
						  placeholder: 'Subject line...'
					  })}
				  </div>
				  <div class="inline-label subject-wrap">
					  <label>Preview:</label>
					  ${input({
						  name: 'preview',
						  placeholder: 'Preview text...'
					  })}
				  </div>
			  </div>
			  <div class="row" id="email-text-editor">
				  <textarea id="email-content" name="content"></textarea>
			  </div>
		  </div>
		  <div class="panel" id="advanced-email-options">
			  <div class="row">
				  <label class="row-label">Additional email headers...</label>
				  <div class="params">
					  ${headerRows.map((kv, i) => keyValueRow(kv.key, kv.value, i)).join('')}
					  <div class="button-end">
						  <button class="add-param"><span class="dashicons dashicons-plus-alt2"></span></button>
					  </div>
				  </div>
			  </div>
		  </div>`
    },
    onMount (step, updateStepMeta, updateStep) {
      tinymceElement('email-content', {}, (content) => {
        console.log(content)
      })
    },
    onDemount () {
      wp.editor.remove(
        'email-content'
      )
    }
  })

})(jQuery)