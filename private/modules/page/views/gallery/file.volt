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
    $idate = strtotime($fup->date_upload);
?>
<tr>
    <td class="leftCell">{{ fup.name }}</td>
    <td><a href="{{ "/" ~ linkpath ~ fup.name }}">{{ image( "/" ~ linkpath ~ 'thumbs/' ~ fup.name, "title" : info) }}</a></td>
    <td>{{ date('d-M-Y',idate) ~ '</br>' ~ date('H:i:s', idate) }}</td>
    <td><textarea class="imageDesc" id="img{{fup.id}}" cols='40' readonly>{{ fup.description }}</textarea></td> 
</tr>
{% endfor %}
</tbody></table>
<?php
    } else {
?>  
    <p>No image files in this gallery yet</p>
<?php
    } 
    

?>
    
</div>

    