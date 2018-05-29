{% include "partials/facebook.volt" %}
{% if linkrowsct > 0 %}
<style>
@media screen and (max-width: 800px){
    #grid[data-columns]::before {
        content: '3 .column.size-1of1';
    }
}

@media screen and (min-width: 801px) and (max-width: 1200px) {
	#grid[data-columns]::before {
		content: '2 .column.size-1of2';
	}
}
@media screen and (min-width: 1201px) {
	#grid[data-columns]::before {
		content: '3 .column.size-1of3';
	}
}

.column { float:left; }
.size-1of1 { width: 100%; }
.size-1of2 { width: 50%; }
.size-1of3 { width: 33.333%; }

</style>

<div id="grid" data-columns>
 
    {% for link in linkrows %}
    <?php
        $url = $link->url;
       
        $ulen = strlen($url);
        if ($ulen > 55)
        {
            $urlhint = substr($url,0,25) . '...' . substr($url,$ulen-27);
        }
        else {
            $urlhint = $url;
        }
    ?>
    <div class='mitem' id='lnk{{link.id}}'>
        <div class="title-area">
        <span class="link-title">
            {% if url == '/' %}
            <a onclick='linkload({{link.id}});' target='_blank'>{{ link.title }}</a>
            {% else %}
            <a  href='{{ url  }}' target='_blank'>{{ link.title }}</a>
            {% endif %}
        </span>
        <span class='pull-right link-sitename' >{{ link.sitename }}</span>
        </div>
        <div class="link-summary">{{ link.summary }}</div>
        <?php if ($link->urltype == 'Blog') { ?>
            <a href = '{{url}}' target='_blank' title="{{ urlhint }}">&nbsp;.. read more</a>
        <?php } ?>
        
    </div>
    
    {% endfor %} 
</div>

{% if isMobile == false %}

{% endif %}
{% endif %}

<script type="text/javascript" src="/assets/node_modules/salvattore/dist/salvattore.min.js"></script>



