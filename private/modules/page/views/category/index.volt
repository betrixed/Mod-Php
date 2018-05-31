{{ content() }}

<h1>Index</h1>
{{ link_to( myController ~ "create", "New Category") }}

<div class="container">
<table class="table table-bordered table-striped" align="center">

    <thead>
        <tr>
            <th style="width:65%;">Category</th>
            <th style="width:20%;">URL Part</th>
            <th style="width:15%;">Enabled</th> 
        </tr>
    </thead>

    <tbody>
        {% for cat in page.items %}
            <tr>
                <td class="leftCell">{{ link_to( myController ~ 'edit/' ~ cat.id, cat.name) }}</td>
                <td>{{ cat.name_clean }}</td>
                <td><?= $cat->enabled ? 'Y' : 'N' ?></td>
            </tr>
        {% endfor %}
    </tbody>

</table>

</div>
        

