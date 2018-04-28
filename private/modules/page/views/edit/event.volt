
<?php
$events = $this->view->events;
$chkct=count($events);
if ( $chkct > 0)
{
?>

<p>Event List</p>
<form id='eventList' action="{{ '/' ~ myController ~ 'eventList'}}" method="post">
<table class='table table-striped' style='width:600px;'>
    <thead>
        <tr>
            <th width='10%' class='centerCell'></th>
            <th  width='20%' class='centerCell'><label>Start</label></th>
            <th width='20%' class='centerCell'><label>End</label></th>
            <th width='10%' class='centerCell'><label>Enabled</label></th>
        </tr>
    </thead>
<?php
$event_blogid = 0;
    $row_id = 0;
    foreach ($events as $bevt) { 
        $row_id = $row_id + 1;
        $chkid = "chk" . $row_id;
        echo "<tr><td><input name='$chkid' id='$chkid' type='checkbox' value='$bevt->id' /></td>";
        echo "<td>" . $bevt->fromTime . "</td>";
        echo '<td>' . $bevt->toTime . '</td>';
        echo '<td>' . (($bevt->enabled) ? "Yes" : "No") . '</td></tr>' . PHP_EOL;
        $event_blogid = $bevt->blogId;
    }
    echo "<tr><td><select name='event_op'>" 
                    . "<option value='enable'>enable</option>"
                    . "<option value='disable'>disable</option>"
                    . "<option value='remove'>remove</option>"
                . "</select></td>";
    
    echo "<td><input type='submit' value='Update Selected' class='btn-danger' /></td></tr>";
    echo "</table>"; 
    echo "<input type='hidden' name='chkct' value='$chkct' />";
    echo "<input type='hidden' name='blogid' value='$event_blogid' />";
    echo "</form>";
}
else {
    echo "<p>Event list is empty<p>";
    }
    
?>


<script type="text/javascript">


$(function() {
    var status = $('#event_status');
    $('#eventList').ajaxForm({
        complete: function(xhr) {
            status.html(xhr.responseText);
            status.style.display = 'block';
            document.refresh;
        }
    });
})();
</script>