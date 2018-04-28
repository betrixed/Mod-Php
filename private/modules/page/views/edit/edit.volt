
<?php 
    echo $this->getContent(); 
    $myview = $this->view;
    $id = $myview->id;
    $blog = $myview->blog;
    $metatags = $myview->metatags;
    $events = $myview->events;
    $categoryList = $myview->categoryList;
    $cat_values = $myview->cat_values;
    $cat_blogid = $myview->cat_blogid;
    $events = $myview->events;
?>

<link href="/assets/css/jquery.datetimepicker.css" rel="stylesheet">

{{ javascript_include("assets/js/tinymce/tinymce.min.js") }}
{{ javascript_include("assets/js/jquery.datetimepicker.js") }}
<!-- {{ javascript_include("assets/js/jquery.form.js") }} -->


<div class="center scaffold">
<table class="table table-striped table-condensed" style='width:600px;'>
    <tr>        
        <td><?php echo $this->tag->linkTo(array("blog/comment/" . $id, "Internal Comment", 'target' => '_blank')) ?></td>
        <td><?php echo $this->tag->linkTo(array("review/form/" . $id, "Request Review", 'target' => '_blank')) ?></td>
        <td><?php echo $this->tag->linkTo(array("links/blog/" . $id, "Generate Link", 'target' => '_blank')) ?></td>
        <td><?php echo $this->tag->linkTo(array("blog/email/" . $id, "Email Content", 'target' => '_blank')) ?></td>
    <tr>

</table>
    
    
    
<ul class="nav nav-tabs">
    <li class="active"><a href="#A" data-toggle="tab">Content</a></li>
    <li><a href="#B" data-toggle="tab">Uploads</a></li>
    <li><a href="#C" data-toggle="tab">Events</a></li>
    <li><a href="#D" data-toggle="tab">Category</a></li>
</ul>
    
<div class="tabbable">
    <div class="tab-content">
    <div class="tab-pane active container" id="A" style="max-width:800px">
                
<?php echo $this->tag->form(array("blog/edit/" . $id, 'id' => 'myform')); ?>



<?php echo $this->tag->hiddenField(array("id", "value" => $id)) ?>

<table class="table table-striped">
    <tr>
        <td align="right">
            <label for="title">Title</label>
        </td>
        <td align="left">
            <?php echo $this->tag->textField(array("title", "size" => 50)) ?>
        </td>
    </tr>
    <tr>
        <td align="right">
            <label for="title_clean">Unique URL</label>
        </td>
        <td align="left">
            <?php echo $this->tag->textField(array("title_clean", "size" => 50)) ?>
        </td>
    </tr>
    <tr>
        <td class="rightCell">
            {{ submit_button('Save' , 'class':'btn btn-default') }}
        </td>
        <td class="leftCell">
            {{ 'Updated ' ~ blog.date_updated ~ ' &nbsp;'  }}
        </td>
    </tr>
</table>

<label class="col-lg-1" for="article">Article Text</label> {{ text_area('article') }}
<table class='table table-condensed' style='width:600px;'>


    <tr>
        <td class='rightCell'>
            <label for="author_id">Author</label>
        </td>
        <td class='leftCell'>
            <?php echo $this->tag->textField(array("author_id", "type" => "number")) ?>
        </td>
    </tr>
    <tr>
        <td class='rightCell'>
            <label for="date_published">Published on</label>
        </td>
        <td class='leftCell'>
            <?php echo $this->tag->textField(array("date_published", "size" => 30)) ?>
        </td>
    </tr>
        <tr>
            <td class='rightCell'><label for="enabled">Enabled</label></td>
            <td class='leftCell'><?php echo $this->tag->checkField(array("enabled", "value" => $blog->enabled)); ?></td>
        </tr>
        <tr>
            <td class='rightCell'><label for="featured">Featured</label></td>
            <td class='leftCell'><?php echo $this->tag->checkField(array("featured", "value" => $blog->featured)); ?></td>
        </tr>
        <tr>
            <td class='rightCell'><label for="comments">Comments</label></td>
            <td class='leftCell'><?php echo $this->tag->checkField(array("comments", "value" => $blog->comments)); ?></td>
        </tr>
</table>
        
    <table class='table table-striped'>
        <thead>
            <tr ><th class='centerCell' colspan='2'>Metatags (for search engines)</th></tr>
        </thead>
        <tbody>
            <?php
            foreach ($metatags as $meta) {
                // generate a name using a prefix.
                $label = $meta->meta_name;
                $name = 'metatag-' . $meta->id;
                $value = $meta->content;
                $setup = array($name, 'value' => $meta->content, 'size' => 60, 'cols' => 60, 'maxlength' => $meta->data_limit);

                if ($meta->data_limit <= 80) {
                    $input = $this->tag->textField($setup);
                } else {
                    $input = $this->tag->textArea($setup);
                }
                ?>
                <tr>
                    <td class='rightCell'><label for='{{ name }}'> {{ label }}</label>
                    <td class='leftCell'>{{ input }}</td>
                </tr> 
<?php } ?>

        </tbody>
    </table>
</form>
</div>
<div class="tab-pane" id="B">
<div class="progress" style="width:400px">
    <div class="up_bar"></div >
    <div class="up_percent">0%</div >          
</div>
<form id='upfile' action="/blog/upload" method="post" enctype="multipart/form-data">
    
    <div id="up_status">{% include "blog/upload.volt" %}</div>
    <input type="hidden" name="blogid" value="{{ id }}" />
    <p><span style="font-size:1.0em;"><b>Upload image or file</b></span></p>
    <table class='table table-condensed'>
        <tr>
            <td class='rightCell'><label>File</label></td>
            <td class='leftCell'><input type="file" name="files[]" multiple></td>
        </tr>
        <tr>
            <td class='rightCell'><label>Destination</label></td>
            <td class='leftCell'>
                <select name='up_dest'>
                    <option value='image'>image</option>
                    <option value='file'>file</option>
                </select></td>
        <tr/>
        <tr><td class='leftCell'><input type="submit" value="upload" class='btn-danger'></td></tr>
    </table>
</form>

</div><!-- fileupload -->

<div class='tab-pane' id='C' >
<div id="event_status" >{% include "blog/event.volt" %}</div>
<form id='eventForm' action="/blog/event" method="post">
     <input type="hidden" name="event_id" value="" />
    <input type="hidden" name="event_blogid" value="{{ id}}" />
    <table class='table' style='width:600px;'> 
        <thead>
            <tr><th colspan="2">Add Event</th></tr>
        </thead>
        <tr>
            <td class='rightCell'><label>From date</label></td>
            <td class='leftCell'><input type="text" name="fromDate" id='fromDate'></td>
            <td class='rightCell'><label>To date</label></td>
            <td class='leftCell'><input type="toDate" name="toDate" id='toDate'></td>
            <td class='rightCell'><label>Enabled</label></td>
            <td class='leftCell'><select name='event_enabled'>
                    <option value='Y'>Yes</option>
                    <option value='N'>No</option>
                </select></td>
        <tr/>
        <tr><td class='rightCell'><input type="submit" class='btn-danger' value='Add Event'></input></td></tr>
    </table>
</form>
</div>
<div class="tab-pane" id="D">
<div id="category_status" class='container'>{% include "blog/category.volt" %}</div>
<hr />


</div>
    </div></div></div>

<script type="text/javascript">
$('#fromDate').datetimepicker({
	formatTime:'H:i',
	formatDate:'d.m.Y'
});
$('#toDate').datetimepicker({
	formatTime:'H:i',
	formatDate:'d.m.Y'
});
(function() {
    var status = $('#event_status');

    $('#eventForm').ajaxForm({
        complete: function(xhr) {
            status.html(xhr.responseText);
            status.style.display = 'block';
            document.refresh; 
        }
    });

})();
</script>
<script type="text/javascript">
    function del_file(fileid,bid)
    {
        var status = $('#up_status');
        var delInfo = {
            id : fileid,
            blogid : bid
        };
        $.ajax({
            type: "POST",
            url: "/blog/deleteFile",
            dataType: "json",
            complete: function(xhr) {
                status.html(xhr.responseText);
                status.style.display = 'block';
                document.refresh;
            },
            data : delInfo
        });
    }
</script>

<script type="text/javascript">
    (function() {

        var bar = $('.up_bar');
        var percent = $('.up_percent');
        var status = $('#up_status');

        $('#upfile').ajaxForm({
            beforeSend: function() {
                status.empty();
                var percentVal = '0%';
                bar.width(percentVal)
                percent.html(percentVal);
            },
            uploadProgress: function(event, position, total, percentComplete) {
                var percentVal = percentComplete + '%';
                bar.width(percentVal)
                percent.html(percentVal);
            },
            success: function() {
                var percentVal = '100%';
                bar.width(percentVal)
                percent.html(percentVal);
            },
            complete: function(xhr) {
                status.html(xhr.responseText);
                status.style.display = 'block';
                document.refresh;
            }
        });
        
    })();
</script>
<script type="text/javascript">
    
    tinymce.init({
        selector: "#article",
        relative_urls: false,
        remove_script_host : true,
        document_base_url: "",
        height: 400,
        fontsize_formats: "8pt 9pt 10pt 11pt 12pt 18pt 24pt 36pt",
        theme: "modern",
        content_css: "/css/elyxir.css",
        plugins: [
            "autosave advlist autolink lists link image charmap print preview hr anchor pagebreak",
            "searchreplace wordcount visualblocks visualchars code fullscreen",
            "insertdatetime media nonbreaking save table contextmenu directionality",
            "emoticons template paste textcolor colorpicker textpattern"
        ],
        toolbar1: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image",
        toolbar2: "print preview media fullscreen | forecolor backcolor emoticons | fontselect fontsizeselect ",
        image_advtab: true,
    });
    
    

</script>
