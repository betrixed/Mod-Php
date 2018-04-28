<?php echo '<!--', __FILE__, ' -->'; ?>

{% if isMobile %}
<div class='sitelogo' style="text-align:center;">
 <a href="/"><img class="imageframe" src="{{myLogo}}" /></a>
</div>
{% include 'partials/nav.volt' %}
{{ flash.output() }}
{{ content() }}
{{ sideCacheHtml }}
{% else %}
<div class="container-fluid">
    <div class="row">
        <div class="fixedLeft">
            <div class='sitelogo' style="text-align:center;">
             <a href="/"><img class="imageframe" src="{{myLogo}}" /></a>
            </div>
            {{ sideCacheHtml }}
        </div>
        <div id="content" class="varRight">
            {% include 'partials/nav.volt' %}
            {{ flash.output() }}
            {{ content() }}
        </div>
    </div>
</div>
{% endif %}
<hr>
