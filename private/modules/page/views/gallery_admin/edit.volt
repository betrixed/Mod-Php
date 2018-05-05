<?php echo $this->getContent(); ?>


<div class="container-fluid">
    
    <p><span style='font-size:1.0em;'>Gallery: <b>{{ gallery.name }}</b></span></p>
    <span class="pull-right">{{ link_to( myController ~ 'scan/' ~ gallery.name, "Scan Folder", 'class':'btn btn-warning') }}</span>
    <div id="up_div" style="width:600px;display:none">
        <div id="#up_bar"></div >
        <div id="#up_percent">0%</div >          
    </div>
    <form id='upfile' action="{{myController ~ 'upload'}}" method="post" enctype="multipart/form-data">
        <input type="hidden" id="galleryid" name="galleryid" value="{{ gallery.id }}" />
        <div style='outline: 1px dotted orange;width:50em;'>
        <table class='table table-condensed table-borderless' >
            <tr>
                <td><p><span style="font-size:1.0em;"><b>Upload image or file</b></span></p></td>
                <td><input type="file" name="files[]" multiple="multple"/></td>
                <td><input type="submit" value="upload" class='btn-danger'></td>
            </tr>
        </table>
        </div>
    </form>
        
   
<div id='image_status'>{% include "gallery_admin/file.volt" %}</div>   


</div>
<script type="text/javascript">
    $(function () {
        var bar = $('.up_bar');
        var percent = $('.up_percent');
        var status = $('#image_status');

        $('#upfile').ajaxForm({
            beforeSend: function () {
                $('#up_div').show();
                status.empty();
                percent.show();
                bar.show();
                var percentVal = '0%';
                bar.width(percentVal)
                percent.html(percentVal);
            },
            uploadProgress: function (event, position, total, percentComplete) {
                var percentVal = percentComplete + '%';
                bar.width(percentVal)
                percent.html(percentVal);
            },
            success: function () {
                var percentVal = '100%';
                bar.width(percentVal)
                percent.html(percentVal);
            },
            complete: function (xhr) {
                status.html(xhr.responseText);
                bar.hide();
                percent.hide();
                $('#up_div').hide();
             }
        });

    })();
</script>
<script type="text/javascript">
    function updateImages()
    {
        var gid = $('#galleryid').val();
        var req = $.ajax({
            url: "{{ myController }}" + "file?gid=" + gid;
                    type: "GET",
            dataType: "html"
        });
        req.done(function (msg) {
            $('#up_status').html(msg);
        });

        req.fail(function (jqXHR, textStatus) {
            alert("Request failed: " + textStatus);
        })
    }
</script>
