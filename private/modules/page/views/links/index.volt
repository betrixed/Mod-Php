
{% set ubase =  myController ~ "index?orderby=" %}   
{% set link  = ubase ~ orderby %}
<div class='container'>
    <div class="row">
        <h2>Links</h2> 
    </div>
&nbsp;&nbsp;[{{ link_to( myController ~ 'new', 'New link record' ) }}]
&nbsp;-&nbsp;[{{ link_to( link, 'First') }}]
&nbsp;-&nbsp;[{{ link_to( link ~ '&page=' ~ page.before, 'Previous' ) }}]
&nbsp;-&nbsp;[{{ link_to( link ~ '&page=' ~ page.next, 'Next' ) }}]
&nbsp;-&nbsp;[{{ link_to( link ~ '&page=' ~ page.last, 'Last' ) }}]
&nbsp;&nbsp;Page {{ page.current ~ " of " ~ page.last }}
</div>
<div class='container'>
    <form id="links_form" method="post" >
<table class='table table-striped'>
    <thead>
        <tr>
            <th><?= $this->tag->linkTo($ubase.$orderalt['title'], 'Title') . $col_arrow['title'] ?></th>
            <th><?= $this->tag->linkTo($ubase.$orderalt['site'], 'Site') . $col_arrow['site']  ?></th>
            <th><?= $this->tag->linkTo($ubase.$orderalt['type'], 'Type') . $col_arrow['type']  ?></th>
            <th><?= $this->tag->linkTo($ubase.$orderalt['date'], 'Date') . $col_arrow['date']  ?></th>
            <th><?= $this->tag->linkTo($ubase.$orderalt['enabled'], 'Enabled') . $col_arrow['enabled']  ?></th>
            <th>Edit</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($page->items as $link) { 
        $enabled = $link->enabled ? 'Y' : 'N';
        $created = substr($link->date_created,0,10);
    ?>
        <tr>
            <td class='leftCell'><a href="{{ link.url }}">{{ link.title }}</a></td>
            <td>{{ link.sitename }}</td>
            <td>{{ link.urltype }}</td>
            <td>{{ created }}</td>
            <td><label>{{ enabled }}&nbsp;<input type="checkbox" name="lid{{link.id}}" value="{{link.id}}" /></label></td>
            <td>{{ link_to( myController ~ "edit/" ~ link.id, "Edit") }}</td>
        </tr>
    <?php } ?>
    </tbody>
</table>
        <div class="row center">
        <div class="form-group">
            <label for="link_enable">Change Selected</label>
        <select name="link_enable" id="link_enable">
            <option value="1">Enable</option>
            <option value="0">Disable</option>
        </select>
            {{ submit_button("Save", 'class':'btn btn-warning') }}
        </div>
        </div>
    </form>
</div>