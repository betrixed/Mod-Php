<form id='imageList' action="{{ myController ~ 'imageList' }}" method="post">
<?php 
    if (isset ($replylist))
    {
        foreach($replylist as $reply)
        {
            echo '<p>' . $reply . '</p>';
        }
    }
    $chkct = count($images);
    if ($chkct > 0)
    {
        $linkpath = $gallery->path . '/';
        $row_id = 0;
?>
<table class="table-condensed table-striped"><tbody>
        <tr>
            <th>select</th>
            <th>visible</th>
            <th>name</th>
            <th>thumb</th>
            <th>date</th>
            <th>description</th>
        </tr>
{% for fup in images %}
<?php 
    $info = $fup->size_str . ' ' . $fup->mime_type;
    $row_id = $row_id + 1;
    $chkid = "chk" . $row_id;
    $descid = "desc" . $row_id;
    $idate = strtotime($fup->date_upload);
?>
<tr>
    <td><input name='{{chkid}}' id='{{chkid}}' type='checkbox' value='{{fup.id}}' /></td>
    <td>{{fup.visible}}</td>
    <td class="leftCell">{{ fup.name }}</td>
    <td><a href="{{ "/" ~ linkpath ~ fup.name }}">{{ image( "/" ~ linkpath ~ 'thumbs/' ~ fup.name, "title" : info) }}</a></td>
    <td>{{ date('d-M-Y',idate) ~ '</br>' ~ date('H:i:s', idate) }}</td>
    <td><textarea class='imageDesc' name='{{descid}}' id='{{descid}}' cols='40'>{{ fup.description }}</textarea></td>
</tr>
{% endfor %}
<tr><td><label>Perform</label></td>
    <td class="leftCell"><select name='image_op' id="image_op">
{% for skey, sval in select %}
        <option value='{{skey}}'{% if sval[1]==1 %} selected='selected'{% endif %}>{{sval[0]}}</option>
{% endfor %}
        </select></td>
        <td><input type='submit' value='Update Selected' class='btn-danger' /></td>

</tr>
</tbody></table>
<input type='hidden' name='chkct' value='{{chkct}}' />
<input type='hidden' name='galleryid' value='{{gallery.id}}' />

<?php
    } else {
?>  
    <p>No image files in this gallery yet</p>
<?php
    } 
?>
    
</div>

</form>
<script type="text/javascript">

$(function () {
        var status = $('#image_status');
        $('#imageList').ajaxForm({
            complete: function (xhr) {
                status.html(xhr.responseText);
                status.style.display = 'block';

            }
        });
    })();
    
$( ".imageDesc" ).click(function(event) {
     var isReadOnly = $(event.target).prop('readonly');
     if (isReadOnly)
     {
        var id = '#chk' + event.target.id.substring(4);
        $(id).prop('checked',true); 
        $(event.target).prop('readonly',false);
     }
});

$( ".imageDesc" ).change(function(event) {
     var id = '#chk' + event.target.id.substring(4);
     $(id).prop('checked',true); 
});



</script>