<?php echo '<!--', __FILE__, ' -->'; ?>    
<div class="container-fluid">
    <header class="header">
   {% include 'partials/nav.volt' %}
    {{ flash.output() }}
    </header>
    <section class="section">
        

        {{ content() }}

    </section>
        </div>
