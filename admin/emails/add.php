<?php ?>
<div id="app" class="templates-picker"></div>
<script>
  (function ($) {
    $(() => {
      Groundhogg.EmailTemplatePicker({
        selector: '#app',
        onSelect: (email) => {
          window.location.href = email.admin
        }
      }).mount()
    })
  })(jQuery)
</script>