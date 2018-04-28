{% include "partials/facebook.volt" %}
{% if linkrowsct > 0 %}
<style>
.column *{ padding: 5px; }
</style>
<div id="mygrid">
 
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
<script type="text/javascript">
    
var $container;
var minit = false;

function calcColSize(){
   if (!minit) return;
   var mincol = 531;
   var w = $('#content').width();


   var columns = Math.floor(w / mincol);
   if (columns == 0)
       return;
   var wcol = Math.floor(w/(columns)) + (columns+1)*(columns+1) - (columns*8) - 5;

   if (w > mincol)
   {
        $(".mitem").each(function(index, element){  
            $(element).outerWidth(wcol+1);
        });
        $container.masonry( 'option', { columnWidth: wcol });
        //$('#msize').text('  size ' + columns + ' ' + wcol + ' ' + w);
   }
}

function readyFn() {
    // important class mgrid does not identify actual div
    $container = $('.mgrid');
    $container.imagesLoaded( function() {
        $container.masonry({
         itemSelector: '.mitem',
         columnWidth: 531,
         initLayout: false
        });
    });
    minit = true;
    calcColSize();
}
//window.onload = readyFn;
$(document).ready()
{
    readyFn();
    
}
$(window).resize(calcColSize);

</script>
{% endif %}
{% endif %}



