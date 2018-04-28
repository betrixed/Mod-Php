
<div class="container">
 <h2>Dashboard</h2>
    {% if isUser %}
    <div class="panel panel-default">
    <div class="panel-heading">Account details</div>
    <div class="panel-body">
        <p>
            {{ link_to( "admin/id/changePassword", "Change Password", 'class':'btn btn-warning') }}
            {{ link_to( "admin/account/edit", "Details", 'class':'btn btn-warning') }}
      </p>
           <div class="container">
           <p><label>Name: </label> {{view.userName}}</p>
           {% if lastLogin %}
           <p><label>Last Login: </label> {{ lastLogin.created_at ~ "<br>by: " ~ lastLogin.userAgent ~ "<br>IP: " ~ lastLogin.ipAddress }}</p>
           {% endif %}
           <p><label>Roles: </label> 
       {% for role in view.roleList %}
           {{ role ~ ", " }}
       {% endfor %}
           </p>
       </div>
    </div>
        
    </div>
    {% else %}
    <div class="panel panel-default">
    <div class="panel-heading">You are not logged in</div>
    <div class="panel-body">
        {{ link_to( "/secure/id/index", "Log In", 'class':'btn btn-warning') }}
    </div>
    {% endif %}
    {% if isAdmin %}
    <div class="panel panel-default">
        <div class="panel-heading">Links</div>
        <div class="panel-body">
                <div class="panel-body">
                    <table>
                        <tr>
                            <td>{{ link_to( "/page/home/index", "Home Page", 'target':'_blank') }}</td>
                            <td>{{ link_to("cat/index/help", "Help Pages") }}</td>
                        </tr>
                        <tr><td>{{ link_to("/page/edit/index", "Article List")}}</td></tr>
                    </table>

        </div>
        </div>
    </div>
    {% if chimpEnabled %}
    <div class="panel panel-default">
        <div class="panel-heading">Members admin</div>
        <div class="panel-body">
                <div class="panel-body">
        <p>
        {{ link_to( "chimp/mail/query", "Member Search") }}
        </p>
        <p>
        {{ link_to("chimp/member/donate", "Donations and Fees") }}
       </p>
        </div>
        </div>
    </div>
    {% endif %}
    <div class="panel panel-default">
        <div class="panel-heading">Configure</div>
        <div class="panel-body">
        <p>
            {{ link_to( "/secure/users/index", "Users List") }}
        </p>
        <p>
            {{ link_to( "/admin/cat/index", "Categories") }}
        </p>
        <p>
            {{ link_to( "/admin/menu/index", "Menus") }}
        </p>
        <p>
            {{ link_to( "/admin/meta/index", "Meta-data") }}
        </p>
        <p>
            {{ link_to( "/secure/permissions/index", "Permissions") }}
        </p>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">Clear cache data</div>
        <div class="panel-body">
        <p>
            {{ link_to( "/admin/cache/clearViews", "Views") }}
       </p>
        <p>
            {{ link_to( "/admin/cache/clearMenus", "Menus") }}
       </p>
        <p>
            {{ link_to( "/admin/cache/clearAssets", "Assets") }}
        </p>    
    </div>
    </div>
    
    <?php 
        $verphp=phpversion();
        $verphal=phpversion("phalcon");
    ?>
    <p>php version: {{verphp}}</p>
    <p>phalcon version: {{verphal}}</p>
    {% endif %}
</div>