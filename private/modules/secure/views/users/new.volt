
<form method="post" autocomplete="off">

<ul class="pager">
    <li class="previous pull-left">
        {{ link_to("index", "&larr; Users Index") }}
    </li>
    <li class="pull-right">
        {{ submit_button("Save", "class": "btn btn-success") }}
    </li>
</ul>

{{ content() }}

<div class="container scaffold">
    <h2>Create a User</h2>
    {{ form.renderCustom("name") }}

    {{ form.renderCustom("email") }}

    {{ form.renderCustom("mustChangePassword") }}

    {{ form.renderCustom("password") }}

    {{ form.renderCustom("passwordCheck") }}

    {{ form.renderCustom("status") }}

</div>

</form>