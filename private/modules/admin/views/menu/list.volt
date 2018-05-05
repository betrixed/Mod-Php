{{ content() }}
<style>
    a.btn {
        margin-left:30px;
    }
</style>
<div class="container-fluid">
<h1>Menu List</h1>

<div class='row center'>
{{ link_to(myController ~ "index", "Menu Trees", 'class':'btn btn-default') }}
{{ link_to(myController ~ "submenu", "New Sub-menu", 'class':'btn btn-default') }}
{{ link_to(myController ~ "subitem","New Menu Item", 'class':'btn btn-default') }}
{{ link_to(myController ~ "link","New Link", 'class':'btn btn-default') }}
{{ link_to(myModule ~ "cache/clearMenus","Clear Cache", 'class':'btn btn-default') }}

</div>


<table class="table table-condensed" align="center">
    <thead>
        <tr>
            <th>Link</th>
            <th>Order</th>
            <th>Caption</th>
            <th>Class</th>
            <th>Controller</th>
            <th>Action</th>
            <th>Role</th>
            <th>Id</th>
            <th>Unlink</th>
        </tr>
    </thead>

    <tbody>
        {% for menu in menulist %}
            <tr>
                <td>{{ menu.link }}</td>
                <td>{{ menu.serial }}</td>
                {% if menu.id > 0 %}
                <td>{{ link_to( myController ~ "item/" ~ menu.id, menu.caption) }}</td>
                {% else %}
                <td>{{ menu.caption }}</td>
                {% endif %}
                <td>{{ menu.class }}</td>
                <td>{{ menu.controller }}</td>
                <td>{{ menu.action }}</td>
                <td>{{ menu.user_role }}</td>
                <td>{{ menu.id }}</td>
                <td>{{ link_to(myController ~ "link?id=" ~ menu.id ~ "&link=" ~ menu.link,"Edit") }}</td>
            </tr>
        {% endfor %}
    </tbody>

</table>
</div>

        

