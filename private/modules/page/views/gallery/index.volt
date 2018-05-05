{{ content() }}
<div class='container'>
<?php

    $ubase = $myController . "index?orderby=";
      
    $link = $ubase . $orderby;  
    echo " " . $this->tag->linkTo($link, 'First');
    echo " | " . $this->tag->linkTo($link.'&page=' . $page->before, 'Previous');
    echo " | " .  $this->tag->linkTo($link.'&page=' . $page->next, 'Next');
    echo " | " .  $this->tag->linkTo($link. '&page=' . $page->last, 'Last');
    echo " | " .  $page->current, "/", $page->last;
    
?>
</div>
<div class='container'>
<table class='table table-striped'>
    <thead>
        <tr>
            <th>Path</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($page->items as $gallery) { ?>
        <tr>
            <td class='leftCell'>{{ link_to(myController ~ "view/" ~ gallery.name, gallery.name) }}</td>
            <td>{{ gallery.description }}</td>
        </tr>
    <?php } ?>
    </tbody>
</table>
</div>
