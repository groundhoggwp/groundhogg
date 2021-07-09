<div id="app" class="templates-picker"></div>
<script>
  (function ($) {
    $(() => {
      Groundhogg.FunnelTemplatePicker({
        selector: '#app',
        onSelect: (funnel) => {
          window.location.href = funnel.admin
        }
      }).mount()
    })
  })(jQuery)
</script>