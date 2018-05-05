{{ content() }}


<?php
    $blogs = $this->view->blogs;
    $catclean = $this->view->catclean;
  
    if (count($blogs) > 0)
    {
?>
<script type="text/javascript">
$(function () {
            $("#first_link").click();
        });
</script>  

<div id="list" class="artcat col-xs-3 col-md-3 ubuntu">
    <h2>{{view.cattitle}}</h2>
    <?php foreach( $blogs as $blog) { ?>
        <div class="row">
            <p><a id="f{{blog.id}}" href="#" get="{{blog.id}}" onclick="fetch(this);return false;">{{blog.title}}</a></p>
        </div>
    
    
     <?php } ?>
</div>
<?php
    } else {
?>
<div id="list" class="artcat col-xs-3 col-md-3 ubuntu">
    <h2>{{view.cattitle}}</h2>
</div>
<?php } ?>
<div class="container artcat col-xs-9 col-md-9">
    {% if isAdmin %}
    <a id="edit_link" href="#" target=_blank"">Edit This</a>
    {% endif %}
<div id="article">
</div>
</div>
<div class="hidden">
   
    <a id="first_link" href="#" get="{{firstId}}" onclick="fetch(this);return false;">#First#</a>
</div>

<script type="text/javascript">
    
var selectedId;
function fetch(aref)
{
    var gid = $(aref).attr('get');
    var link = "/cat/fetch/" + gid;
    if (selectedId)
    {
        if (selectedId == gid)
            return;
    }
    $.get(link,function(data){
            $('#article').html(data);
    });
    if (selectedId)
    {
        $('#f'+selectedId).css('background-color','white');
        $(aref).css('background-color','palegoldenrod');
    }
    else {
        $('#f'+gid).css('background-color','palegoldenrod');
    }
    selectedId = gid;
    var editlink = $('#edit_link');
    if (editlink)
    {
        editlink.attr('href','/admin/blog/edit/' + gid);
    }
}
</script>