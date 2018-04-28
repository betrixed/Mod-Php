{{ content() }}

<ul class="pager">
    <li class="pull-right">
        {{ link_to(myController ~ "new", "Create New User", "class": "btn") }}
    </li>
</ul>

<div class="container">
    <div class="btn-group">
     {{ link_to(myController ~ "index", '<i class="icon-fast-backward"></i> First', "class": "btn") }}
     {{ link_to(myController ~ "index?page=" ~ page.before, '<i class="icon-step-backward"></i> Previous', "class": "btn ") }}
     {{ link_to(myController ~ "index?page=" ~ page.next, '<i class="icon-step-forward"></i> Next', "class": "btn") }}
     {{ link_to(myController ~ "index?page=" ~ page.last, '<i class="icon-fast-forward"></i> Last', "class": "btn") }}
 </div>
<span class="help-inline">{{ page.current }}/{{ page.last }}</span>
</div>
<table class="table table-bordered table-striped" align="center">
    <thead>
        <tr>
            <th>Id</th>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        {% for item in page.items %}

        <tr>
            <td>{{ item.id }}</td>
            <td>{{ item.name }}</td>
            <td>{{ item.email }}</td>
            <td>{{ item.status }}</td>
            <td width="12%">{{ link_to(myController ~ "edit/" ~ item.id, '<i class="icon-pencil"></i> Edit', "class": "btn") }}</td>
        </tr>
        {% endfor %}
    </tbody>
</table>
