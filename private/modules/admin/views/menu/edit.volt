{{ content() }}
<style>
    label.control-label {
        min-width:110px;
        text-align:right;
        margin-right:10px;
    }
    
    span.input-group-addon {
        display: inline-table;
    }
    
    a.btn {
        margin-right:10px;
    }
</style>

<div class="container-fluid">
    <h1>Menu {{ menuName }}</h1>
<div class='row center'>
{{ link_to(myController ~ "index", "Menu Trees", 'class':'btn btn-default') }}    
{{ link_to(myController ~ "reset?m0="  ~ rootid, "Clear Cache", 'class':'btn btn-default') }}
{{ link_to(myController ~ "submenu", "New Sub-menu", 'class':'btn btn-default') }}
{{ link_to(myController ~ "subitem","New Menu Item", 'class':'btn btn-default') }}

{{ link_to(myController ~ "link","New Link", 'class':'btn btn-default') }}
</div>
<hr>
<div class="col-md-2">
<table class="table table-condensed" align="center">
    <thead>
        <tr>
            <th >level</th>
            <th >caption</th>
            <th >Id</th>
            <th >Parent</th>
            <th >Class</th>
            <th >Controller</th>
            <th >Action</th>
            <th >Role</th>
            <th>Unlink</th>
        </tr>
    </thead>

    <tbody>
        {% for menu in menulist %}
            <tr>
                <td>{{ menu.level }}</td>
               
                <td>{{ menu.caption }}</td>
                
                <td>{{ menu.id }}</td>
                 <td>{{ menu.link }}</td>
                <td>{{ menu.class }}</td>
                <td>{{ menu.controller }}</td>
                <td>{{ menu.action }}</td>
                <td>{{ menu.user_role }}</td>
                <td>{{ link_to(myController ~ "unlink?m0=" ~ rootid ~ "&id=" ~ menu.id ~ "&link=" ~ menu.link,"unlink") }}</td>
            </tr>
        {% endfor %}
    </tbody>

</table>
</div>
</div>

        

