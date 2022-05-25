<?php ?>
<div id="app" class="templates-picker"></div>
<script>
  (function ($) {
    $(() => {
      Groundhogg.EmailTemplatePicker({
        selector: '#app',
        onSelect: (email) => {
          Groundhogg.element.loadingModal()
          window.location.href = email.admin
        }
      }).mount()
    })
  })(jQuery)
</script>
