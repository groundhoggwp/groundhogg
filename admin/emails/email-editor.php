<?php

?>
<div id="email-editor"></div>
<script>
  (function($){

    $(function(){

      Groundhogg.EmailEditor({
        selector: '#email-editor',
        email: GroundhoggEmail,
        onChange: ( email ) => {
          // console.log( email )
        }
      }).mount()

    })

  })(jQuery)
</script>
