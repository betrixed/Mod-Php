
<?php 
    $categoryList = $this->view->categoryList;
    $cat_values =  $this->view->cat_values;
    $cat_blogid =  $this->view->cat_blogid;
    $chkct = count($categoryList);
    if ($chkct > 0)
    {
?>
<h5>Category List</h5>   
<script>
$(function() {
    var options = {
      target: '#category_status'
    };
    $('#categoryList').ajaxForm(options);
})(); 
</script>
<p><em>Unselected categories are: </em>{{ cat_values }}</p>
<form id='categoryList' action="{{ myController }}categorytick" method="post">
<table class='table table-striped'>
<?php 
    $rowid = 0;
    echo "<tbody><tr>".PHP_EOL;
    foreach ($categoryList as $cat) 
    { 
          
        if ($rowid % 3 == 0)
        {
            echo '</tr><tr>';
        }
        $rowid = $rowid + 1;            
        
        $catid = "cat" . $rowid;   
        $checked = ($cat->blog_id > 0) ? "checked" : "";
        echo "<td class='leftCell'><input name='$catid' id='$catid' type='checkbox' value='$cat->id' $checked/> $cat->name</td>". PHP_EOL; 
        
    }
    echo "</tr><tr><td><input type='submit' value='Update' class='btn-danger' /></td></tr>";

    echo "</table>"; 
    echo "<input type='hidden' name='chkct' value='$chkct' />" . PHP_EOL;
    echo "<input type='hidden' name='blogid' value='$cat_blogid' />" . PHP_EOL;
    echo "</form>" . PHP_EOL;
        }
else {
    echo "<h5>Category List (empty)</h5>";
}
?>
