(function ($){

  const {
    loadingModal,
    andList,
    bold,
    specialChars
  } = Groundhogg.element

  const { EmailPreview } = Groundhogg.components

  const { formatDate } = Groundhogg.formatting

  const {
    Modal,
    ModalFrame,
    Div,
    Table,
    Tr,
    Td,
    makeEl
  } = MakeEl

  const { email_log: LogsStore } = Groundhogg.stores

  const { __, sprintf, _x } = wp.i18n

  var $doc = $(document);

  const EmailLogModal = ( logItem ) => {

    const {
      date_sent,
      content,
      from_address,
      from_name,
      from_avatar,
      recipients,
      subject,
      headers,
      status,
      error_code,
      error_message,
    } = logItem.data

    Modal({}, ({close}) => Div({
      className: 'email-log-modal'
    }, [

      makeEl( 'p', {
        className: status === 'failed' ? 'gh-text danger' : ''
      }, sprintf( status === 'failed' ? __('Failed to send to %s on %s', 'groundhogg' ) : __( 'Sent to %s on %s', 'groundhogg' ), andList( recipients.map( bold ) ), bold( formatDate( date_sent ) ) ) ),

      status === 'failed' ? makeEl( 'pre', {
        className: 'pill danger'
      }, error_message ) : null,

      Div({
        className: 'gh-panel'
      },
        EmailPreview({
          subject,
          content,
          from_avatar,
          from_name,
          from_email: from_address,
        })
      ),
      `<h2>${_x('Headers', 'email log headers', 'groundhogg' )}</h2>`,
      Div({
        className: 'email-log-item-headers'
      }, [
        Table({},
          headers.map( ([key, value]) => Tr({}, [ Td({}, makeEl( 'pre', {}, key ) ), Td({},  makeEl( 'pre', {}, specialChars( value ) ) ) ] ))
        )
      ])
    ]))
  }

  $doc.on('click', 'a.view-email-log', async  function (e) {

    e.preventDefault();

    let { close } = loadingModal()

    let logId = parseInt( $(e.currentTarget).data( 'log-id' ) )

    const logItem = await LogsStore.maybeFetchItem( logId )

    EmailLogModal( logItem )

    close()

  });

  Groundhogg.components.EmailLogModal = EmailLogModal

})(jQuery)
