<?php

?>
<div id="email-app"></div>
<script>
  (function($){

    $(function(){

      Groundhogg.EmailEditor({
        selector: '#email-app',
        email: GroundhoggEmail,
        onChange: ( email ) => {
          // console.log( email )
        }
      }).mount()

    })

  })(jQuery)
</script>
