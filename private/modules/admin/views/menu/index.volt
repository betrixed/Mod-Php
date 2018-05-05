{{ content() }}
<div class='container-fluid'>
<h1>Menu Index</h1>
<div class='row center'>
{{ link_to(myController ~ "list", "All items", 'class':'btn btn-default')}}</p
</div>
<div class="col-md-2">
<table class="table table-condensed" align="center">
    <thead>
        <tr>
            <th style="width:65%;">Name</th>
        </tr>
    </thead>

    <tbody>
        {% for menu in menulist %}
            <tr>
                <td class="leftCell"><a href="/admin/menu/edit?m0={{menu.id}}">{{ menu.name }}</a></td>
            </tr>
        {% endfor %}
    </tbody>

</table>
</div>

</div>
        

