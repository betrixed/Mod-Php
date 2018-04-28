

<form method="post" autocomplete="off">

<ul class="pager">
    <li class="previous pull-left">
        {{ link_to(myController ~ "index", "&larr; User Index") }}
    </li>
    <li class="pull-right">
        {{ submit_button("Save", "class": "btn btn-big btn-success") }}
    </li>
</ul>

{{ content() }}

<div class="container scaffold">
    <h2>Edit User</h2>

    <ul class="nav nav-tabs">
        <li class="active"><a href="#A" data-toggle="tab">Basic</a></li>
        <li><a href="#B" data-toggle="tab">Successful Logins</a></li>
        <li><a href="#C" data-toggle="tab">Password Changes</a></li>
        <li><a href="#D" data-toggle="tab">Reset Passwords</a></li>
        <li><a href="#E" data-toggle="tab">Groups</a></li>
    </ul>

<div class="tabbable">
    <div class="tab-content">
        <div class="tab-pane active" id="A">
                <hr>
            {{ form.render("id") }}

            <div class="span4">

                <div class="form-group">
                    <label for="name">Name</label>
                    {{ form.render("name") }}
                </div>

            </div>

            <div class="span4">

                <div class="form-group">
                    <label for="email">E-Mail</label>
                    {{ form.render("email") }}
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    {{ form.render("status") }}
                </div>


            </div>
        </div>

        <div class="tab-pane" id="B">
             <hr>
            <p>
                <table class="table table-bordered table-striped" align="center">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Date</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    {% if successLogins is not empty %}
                    <tbody>
                    {% for login in successLogins %}
                        <tr>
                            <td>{{ login.created_at }}</td>
                            <td>{{ login.status_ip }}</td>
                            <td>{{ login.data }}</td>
                        </tr>
                    {% endfor %}
                    </tbody>
                    {% else %}
                        <tr><td colspan="3" align="center">User does not have successfull logins</td></tr>
                    {% endif %}
                </table>
            </p>
        </div>

        <div class="tab-pane" id="C">
             <hr>
            <p>
                <table class="table table-bordered table-striped" align="center">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>IP Address</th>
                            <th>User Agent</th>
                            
                        </tr>
                    </thead>
                    {% if passwordChanges is not empty %}
                    <tbody>
                    {% for change in passwordChanges %}
                        <tr>
                            <td>{{ change.created_at}}</td>
                            <td>{{ change.status_ip }}</td>
                            <td>{{ change.data }}</td>
                            
                        </tr>
                    {% endfor %}
                    </tbody>
                    {% else %}
                        <tr><td colspan="3" align="center">User has not changed his/her password</td></tr>
                    {% endif %}
                </table>
            </p>
        </div>

        <div class="tab-pane" id="D">
             <hr>
            <p>{{ link_to(myController ~ "sendPasswordReset/" ~  user.id, "Email Password Reset", 'class':'btn btn-default') }}</p>
                <p>
                <table class="table table-bordered table-striped" align="center">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>IP</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    {% if resetPasswords is not empty %}
                    <tbody>
                        
                    {% for reset in resetPasswords %}
                        <tr>
                            <td>{{ reset.created_at }}</td>
                            <td>{{ reset.status_ip }}</td>
                            <td>{{ reset.data }}</td>
                        </tr>
                    {% endfor %}
                    </tbody>
                     {% else %}
                        <tr><td colspan="3" align="center">User has not requested reset his/her password</td></tr>
                     {% endif %}
                </table>
            </p>
        </div>
        <div class="tab-pane" id="E">
             <hr>
                {{ link_to( myController ~ "groups/", "Edit Groups", 'class':'btn btn-default')}}
                <table class="table table-bordered table-striped" align="center">
                    <thead>
                        <tr>
  
                            <th>Name</th>
                            <th>Status</th>
                            <th>Created</th>

                        </tr>
                    </thead>
                    {% if groups is not empty %}
                    <tbody>
                    {% for group in groups %}
                        <tr>
                            <td>{{ group.name }}</td>
                            <td>{{ group.status }}</td>
                            <td>{{ group.created_at }}</td>
                         </tr>
                    {% endfor %}
                    </tbody>
                     {% else %}
                        <tr><td colspan="4" align="center">User has no groups assigned</td></tr>
                     {% endif %}
                </table>
  
        </div>

    </div>
</div>

</form>

{% if isAdmin %}
<form id="deleteUser" method="post" action="{{ '/' ~ myController ~ 'delete'}}">
<div>
    <input type="hidden" name="<?= $this->security->getTokenKey() ?>"
        value="<?= $this->security->getToken() ?>"/>
    <input type="hidden" name="userId"
        value="{{ user.id }}"/>
    <input type="submit" value="Delete User" />
</div>
</form>

{% endif %}