<?php echo $this->getContent(); ?>

<div class="container">
    <p><span style='font-size:1.0em;'>Gallery: <b>{{ gallery.name }}</b></span></p>

<div id='image_status'>{% include "gallery/file.volt" %}</div>   
</div>

