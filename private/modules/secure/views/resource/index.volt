{{ content() }}
<div class='container'>
    <div class='row'>
    {{ link_to(myModule ~ "permissions/index", 'Permissions', 'class':'btn btn-success') }}
    {{ link_to(myModule ~ 'resource/new', 'New Resource', 'class':'btn btn-success') }}
    </div>
    <div class='pager'>
    {% set ubase =  myModule ~ "resource/index?orderby=" %}
    {% set link = ubase ~ orderby %}
    <p> {{ link_to( link, "First") }} | 
    {{ link_to( link ~ '&page=' ~ page.before, "Previous") }} |
    {{ link_to( link ~ '&page=' ~ page.next, "Next") }} |
    {{ link_to( link ~ '&page=' ~ page.last, "Last") }} |
    {{ page.current ~ " / " ~ page.last }} </p>
    </div>
</div>
<div class='container'>
<table class='table table-striped'>
    <thead>
        <tr>
            <th><?php echo $this->tag->linkTo($ubase.$orderalt['name'], 'Name') . $col_arrow['name'] ?></th>
            <th><?php echo $this->tag->linkTo($ubase.$orderalt['action'], 'Action') . $col_arrow['action']  ?></th>
            <th>Edit</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($page->items as $resource) { ?>
        <tr>
            <td class='leftCell'>{{ resource.name }}</td>
            <td>{{ resource.action }}</td>
            <td>{{ link_to( myController ~ "edit/" ~ resource.id, "Edit") }}</td>
        </tr>
    <?php } ?>
    </tbody>
</table>
</div>