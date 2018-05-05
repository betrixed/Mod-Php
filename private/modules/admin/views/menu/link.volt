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
</style>
<div class='container-fluid'>
<h1>Edit Menu Link</h1>

<div class='row center'>
{{ link_to(myController ~ "index", "Menus List", 'class':'btn btn-default') }}
{% if allowUnlink %}
    {{ link_to(myController ~ "unlink?id=" ~ id ~ '&link=' ~ link, "Unlink", 'class':'btn btn-warning') }}

{% endif %}
</div>
<hr>
<form action="{{myController ~ "link"}}" method="post">

<div class='row'>
{{ form.renderCustom('serial') }}
</div>

<div class='row'>
    {{ form.renderCustom('menu_top_id') }}
</div>
<div class='row'>
    {{ form.renderCustom('menu_item_id') }}
</div>
<div class='row'>
    {{ submit_button("Save") }}
</div>
</form>
</div>

        

