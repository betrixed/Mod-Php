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
    echo " | " .  $this->tag->linkTo('admin/gallery/new', 'Create Gallery');
    
?>
</div>
<div class='container'>
<table class='table table-striped'>
    <thead>
        <tr>
            <th>Name / Path </th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($page->items as $gallery) { ?>
        <tr>
            <td class='leftCell'>{{ link_to(myController ~ "edit/" ~ gallery.name, gallery.name) }}</td>
            <td class='leftCell'>{{ gallery.description }}</td>
        </tr>
    <?php } ?>
    </tbody>
</table>
</div>
