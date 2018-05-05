<?php echo $this->getContent(); ?>

{{ javascript_include("js/jquery/jquery.form.js") }}

<div class="container">
<div id="up_div" style="width:400px;display:none;">
    <div class="up_bar"></div >
    <div class="up_percent">0%</div >          
</div>
<form id='upfile' action="/gallery/upload" method="post" enctype="multipart/form-data">
    
    
    <input type="hidden" name="galleryid" value="{{ gallery.id }}" />
    <p><span style="font-size:1.0em;"><b>Upload image or file</b></span></p>
    <table class='table table-condensed'>
        <tr>
            <td class='leftCell'><input type="submit" value="upload" class='btn-danger'></td>
            <td class='leftCell'><label>File</label></td>
            <td class='leftCell'><input type="file" name="files[]" multiple></td>
        </tr>
    </table>
    <div id="up_status">{% include "gallery/file.volt" %}</div>
</form>
    
</div>

<script type="text/javascript">
    (function() {
        var bar = $('.up_bar');
        var percent = $('.up_percent');
        var status = $('#up_status');

        $('#upfile').ajaxForm({
            beforeSend: function() {
                $('#up_div').show();
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
                $('#up_div').hide();
            }
        });
        
    })();
</script>