<?php echo '<!--', __FILE__, ' -->'; ?>
<div class="container-fluid">
    <div class="row">
        <div class="fixedLeft">
            <div class='sitelogo' style="text-align:center;">
            <a href="/"><img class="imageframe" src="{{myLogo}}" /></a>
            </div>
            {% include 'partials/side_links.volt' %}
        </div>
        <div id="content" class="varRight">
            {% include 'partials/nav.volt' %}
            {{ flash.output() }}
            {{ content() }}
        </div>
    </div>
</div>
<hr>

<footer class='footer'>

        <p class="text-muted">P-can &copy; Michael Rynn | 
            <?php
            echo " Response time " . sprintf('%.2f ms', (microtime(TRUE) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
            echo " | Memory " . sprintf('%.2f MiB', memory_get_peak_usage() / 1024 / 1024);
            ?>
            &nbsp; <!-- {{ myDir }} -->
        </p>

</footer>
