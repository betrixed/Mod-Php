<form method="post">

    <h2>Manage Permissions</h2>

    <div class="well" align="center">
        <tr>
            <td>{{ link_to(myModule ~ 'resource/index', 'Resources',  'class':'btn btn-success') }}</td>
            <td>{{ link_to(myModule ~ 'resource/new', 'New Resource', 'class':'btn btn-success') }}</td>
       </tr>
        <table class="perms">
            <tr>
                <td><label for="groupId">Group</label></td>
                <td>{{ select('groupId', groups, 'using': ['id', 'name'], 'useEmpty': true, 'emptyText': '...', 'emptyValue': '') }}</td>
                <td>{{ submit_button('name': 'submit', 'class': 'btn btn-default', 'value': 'Fetch') }}</td>
                {% if hasData %}
                <td>{{ submit_button('name': 'submit', 'class': 'btn btn-primary', 'value': 'Update', 'style':'visibility:hidden;') }}</td>
                {% endif %}
            </tr>
        </table>

    </div>

    {% if request.isPost() and group %}

    {% for resource in  acl %}

    <h3>{{ resource.name }}</h3>

    <table class="table table-bordered table-striped" align="center">
        <thead>
            <tr>
                <th width="8%">Allow</th>
                <th width="25%">Action</th>
                <th>Other groups</th>
            </tr>
        </thead>
        <tbody>
            {% for action in resource.actions %}
            <tr>
                <td><input type="checkbox" class="checkbox" 
                           name="permissions[]"  value="{{ 'r.' ~ action[1] }}"  
                           {% if plist['r.' ~ action[1] ] is defined %} checked="checked" {% endif %}>
            </td>
            <td class="leftCell">{{ action[0] }}</td>
            <td class="leftCell">{% if glist['r.' ~ action[1] ] is defined %}{% for s in glist['r.' ~ action[1] ] %}{{s}},{% endfor %}{% endif %}</td>
        </tr>
        {% endfor %}
    </tbody>
</table>

{% endfor %}

{% endif %}


</form>
<script>
    $(".checkbox").click(function () {
        $('input[type="submit"][value="Update"]').css('visibility', 'visible');
    });

</script>