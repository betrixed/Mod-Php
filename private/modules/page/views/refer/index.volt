{{ content() }}
<div style="font-family:Gill, Helvetica, sans-serif;font-size:16px;">
<div class='container'>
    <div class='row'>
        <p class='text-left pagetitle' style=''>Content</p>
    </div>
<?php

    $ubase = $this->view->myController . "index?orderby=";
    $page = $this->view->page;
    $orderalt = $this->view->orderalt;
    $col_arrow = $this->view->col_arrow;
    $url = $ubase . $this->view->orderby;  
    echo " " . $this->tag->linkTo($url, 'First');
    echo " | " . $this->tag->linkTo($url.'&page=' . $page->before, 'Previous');
    echo " | " .  $this->tag->linkTo($url.'&page=' . $page->next, 'Next');
    echo " | " .  $this->tag->linkTo($url. '&page=' . $page->last, 'Last');
    echo " | " .  $page->current, "/", $page->last;
    
?>
</div>
<div class='container' style="background-color:white;">
<table class='table-striped'>
    <thead>
        <tr>
            <th></th>
            <th class="centerCell"><?php echo $this->tag->linkTo($ubase.$orderalt['title'], 'Title') . $col_arrow['title'] ?></th>
            <th class="centerCell"><?php echo $this->tag->linkTo($ubase.$orderalt['site'], 'Site') . $col_arrow['site']  ?></th>
            <th class="centerCell"><?php echo $this->tag->linkTo($ubase.$orderalt['type'], 'Type') . $col_arrow['type']  ?></th>
            <th class="centerCell"><?php echo $this->tag->linkTo($ubase.$orderalt['date'], 'Date') . $col_arrow['date']  ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($page->items as $link) { ?>
        <tr data-toggle="collapse" data-target="#r{{link.id}}" class="accordian-toggle">
            <td><button class="btn btn-default btn-xs"><span class="glyphicon glyphicon-eye-open"/></button></td>
            <td class='leftCell'><a href="{{ link.url }}" target="_blank">{{ link.title }}</a></td>
            <td class='leftCell'>{{ link.sitename }}</td>
            <td>{{ link.urltype }}</td>
            <td><?php echo substr($link->date_created,0,10); ?></td>
        </tr>
        <tr >
            <td colspan="5" class="hiddenRow"><div 
                class="accordian-body-collapse collapse row" 
                id="r{{link.id}}" >
                <div class="text-left bg-white">
                    <div class="col-sm-12 bg-white">{{ link.summary }}</div>
                </div>
                </div></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
</div>
</div>