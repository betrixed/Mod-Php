<?php 
    if (isset($upfiles) && count($upfiles) > 0)
    {
        echo "<p><span style='font-size:1.0em;'><b>Uploaded Files</b></span></p>";
        echo '<div class="container">';
        echo '<table class="table table-condensed table-striped"><tbody>';
        foreach($upfiles as $fup)
        {
            $linkpath = $fup->path . $fup->name;
            echo '<tr><td class="leftCell">' . ' /' . $linkpath;
            echo '</td><td>' .  $this->tag->linkTo(array($linkpath, 'get', 'target' => '_blank') );
            echo '</td><td>' . $fup->date_upload;  
             echo '</td><td>' . $fup->mime_type;  
             echo '</td><td>' . $fup->getSizeStr(); 
             echo '</td><td><button type="button" onclick="del_file('
                    . $fup->id . ',' . $fup->blog_id . ')">DEL</button>'; 
            echo '</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }
    if (isset ($replylist))
    {
        foreach($replylist as $reply)
        {
            echo '<p>' . $reply . '</p>';
        }
    }
?>

