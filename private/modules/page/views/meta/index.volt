{{ content() }}
<div style="margin-left:100px;">
    <br/>
    &nbsp; {{ link_to(myController ~ "index", "First") }} 
    &nbsp;|&nbsp; {{ link_to(myController ~ "index?page=" ~ page.before, "Previous") }} 
    &nbsp;|&nbsp; {{ link_to(myController ~ "index?page=" ~ page.next, "Next") }} 
    &nbsp;|&nbsp; {{ link_to(myController ~ "index?page=" ~ page.last, "Last") }} 
    &nbsp;|&nbsp; {{ page.current ~ ' of ' ~ page.last }} 
 &nbsp;&nbsp;{{ link_to(myController ~ 'new', 'Create New', 'class':'btn btn-success') }}

</div>
<div class='container' >
    

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th style="width:8%;">ID</th>
            <th style="width:20%;">Name</th>
            <th>Template</th>
            <th style="width:15%;">Size Limit</th>
            <th style="width:15%;">Display</th> 
         </tr>
    </thead>

    <tbody>
    <?php foreach ($page->items as $meta) { ?>
        <tr>
            <td>{{ meta.id }}</td>
            <td style="text-align:left">{{ link_to(myController ~ "edit?id=" ~ meta.id, meta.meta_name) }}</td>
            <td><?php echo htmlentities($meta->template); ?></td>
            <td><{{ meta.data_limit }}</td>
            <td><?php 
                $howset = isset($meta->display) && $meta->display==1 ? "Shown" : "Hidden";
                echo $howset;
                ?></td>
        </tr>
    <?php } ?>
    </tbody>

</table>
</div>