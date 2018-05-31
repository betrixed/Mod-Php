

{{ content() }}
<div class="container">
<form action="create" method="post" >
<table>
    <thead>
        
    </thead>
    <tbody>
        <tr>
            <td>{{ form.label('name') }}</td>
            <td>{{ form.render('name') }}</td>
        </tr>
         <tr>
            <td>{{ form.label('name_clean') }}</td>
            <td>{{ form.render('name_clean') }}</td>
        </tr>
        <tr>
            <td>{{ form.label('enabled') }}</td>
            <td>{{ form.render('enabled') }}</td>
        </tr>
        <tr>
            <td></td>
            <td>{{ submit_button("Create", 'class':'submit') }}</td>
        </tr>
    </tbody>
</table>
</form>
</div>