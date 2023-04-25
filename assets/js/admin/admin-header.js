(($) => {

  const { icons } = Groundhogg.element

  const header = () => {
    //language=HTML
    return `<div class="gh-header admin-header">
        <div class="logo">
            ${icons.groundhogg}
        </div>
        <div class="display-flex align-right">
            <button class="gh-button secondary icon text">
                <span class="dashicons dashicons-plus-alt2"></span>
            </button>
            <button class="gh-button secondary icon text">
                <span class="dashicons dashicons-bell"></span>
            </button>
        </div>
    </div>`
  }

  $('#wpbody-content').prepend( header() );

})(jQuery)
