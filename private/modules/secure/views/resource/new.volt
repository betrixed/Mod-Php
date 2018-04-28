

{{ content() }}
<div class="container">
<form action="new" method="post" >
{{ form.render('id') }}    
<table>
    <thead>
        
    </thead>
    <tbody>
        <tr>
            <td>{{ form.label('name') }}</td>
            <td>{{ form.render('name') }}</td>
        </tr>
         <tr>
            <td>{{ form.label('action') }}</td>
            <td>{{ form.render('action') }}</td>
        </tr>
        <tr>
            <td></td>
            <td>{{ submit_button("Create", 'class':'submit') }}</td>
        </tr>
    </tbody>
</table>
</form>
</div>