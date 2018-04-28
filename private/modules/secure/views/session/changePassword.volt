{{ content() }}

<form method="post" autocomplete="off" action="{{ url(myController ~ "changePassword") }}">

    <div class="container">
        <h3>Change Password</h3>
        <table class='table table-striped' style='width:350px;'>
            <tbody>
            <tr class="clearfix">
                <td><label for="password">Password</label></td>
                <td>{{ form.render("password") }}</td>
            </tr>

            <tr class="clearfix">
                <td><label for="confirmPassword">Confirm Password</label></td>
                <td>{{ form.render("confirmPassword") }}</td>
            </tr>

            <tr class="clearfix">
                <td colspan='2'>{{ submit_button("Change Password", "class": "btn btn-primary") }}</td>
            </tr>
        </tbody>
        </table>
    </div>

</form>