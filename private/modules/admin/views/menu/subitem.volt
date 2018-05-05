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
        margin-left:50px;
    }
</style>
<div class='container-fluid'>
<h1>Edit SubItem</h1>
<div class='row center'>
{{ link_to(myController ~ "index", "Menus List",'class':'btn btn-default') }}
</div>
<hr>
<form action="{{myController ~ "subitem"}}" method="post">
{{ form.render('id') }}
<div class='row'>
{{ form.renderCustom('caption') }}
</div>
<div class='row'>
    {{ form.renderCustom('controller') }}
</div>
<div class='row'>
    {{ form.renderCustom('action') }}
</div>
<div class='row'>
    {{ form.renderCustom('user_role') }}
</div>
<div class='row'>
    {{ form.renderCustom('class') }}
</div>
<div class='row center'>
    {{ submit_button("Save") }}
        {% if menuId %}
    {{ link_to(myController ~ "delitem/" ~ menuId, "Delete", 'class':'btn btn-warning') }}
    {% endif %}
</div>
</form>
</div>

        

