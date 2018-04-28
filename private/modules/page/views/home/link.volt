<div class='sitem' style='width:100%;' id='lnk{{link.id}}'>
    <span class='link-sitename' ><a onclick='homeload();'>Show All</a></span>
    <div class="title-area">
    <span class="link-title">
             {% if link.url == '/' %}
             <p>{{ link.title }}</p>
            {% else %}
            <a  href='{{link.url}}' target='_blank'>{{ link.title }}</a>
            {% endif %}
    </span>
        
    </div>
    <div class="link-summary">{{ link.summary }}</div>
</div>
