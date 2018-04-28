<!-- users/index.volt -->
<ul class="pager">
    <li class="pull-left">
        {{ link_to("users/create", "Create User", "class": "btn btn-primary") }}
    </li>
</ul>

<div class="container">
    <div class="btn-group">
     {{ link_to("users/index", '<i class="icon-fast-backward"></i> First', "class": "btn") }}
     {{ link_to("users/index?page=" ~ page.before, '<i class="icon-step-backward"></i> Previous', "class": "btn ") }}
     {{ link_to("users/index?page=" ~ page.next, '<i class="icon-step-forward"></i> Next', "class": "btn") }}
     {{ link_to("users/index?page=" ~ page.last, '<i class="icon-fast-forward"></i> Last', "class": "btn") }}
 </div>
<span class="help-inline">{{ page.current }}/{{ page.last }}</span>
</div>
<table class="table table-bordered table-striped" align="center">
    <thead>
        <tr>
            <th>Id</th>
            <th>Name</th>
            <th>Email</th>
            <th>Profile</th>
            <th>Banned?</th>
            <th>Suspended?</th>
            <th>Confirmed?</th>
        </tr>
    </thead>
    <tbody>
        {% for user in page.items %}

        <tr>
            <td>{{ user.id }}</td>
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>{{ user.profile }}</td>
            <td>{{ user.banned == 'Y' ? 'Yes' : 'No' }}</td>
            <td>{{ user.suspended == 'Y' ? 'Yes' : 'No' }}</td>
            <td>{{ user.active == 'Y' ? 'Yes' : 'No' }}</td>
            <td width="12%">{{ link_to("users/edit/" ~ user.id, '<i class="icon-pencil"></i> Edit', "class": "btn") }}</td>
            <?php if ($user->id != 1) { ?>
            <td width="12%">{{ link_to("users/delete/" ~ user.id, '<i class="icon-remove"></i> Delete', "class": "btn") }}</td>
            <?php } else { ?>
            <td></td>
            <?php } ?>
            
        </tr>
        {% endfor %}
    </tbody>
</table>
