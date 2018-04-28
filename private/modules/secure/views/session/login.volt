<div class="page-header">
    <h2>Log In</h2>
</div>

{{ form( formAction , 'role': 'form') }}
<fieldset>
    <div class="form-group">
        <label for="email">Username/Email</label>
        <div class="controls">
            {{ text_field('email', 'class': "form-control") }}
        </div>
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <div class="controls">
            {{ password_field('password', 'class': "form-control") }}
        </div>
    </div>
    {% if google['loginCaptcha'] %}
          <script src="https://www.google.com/recaptcha/api.js" async defer></script>
          <div class="g-recaptcha" data-sitekey="{{ google['captchaPublic'] }}"></div>   
    {% endif %}
    <div class="form-group">
        {{ submit_button('Login', 'class': 'btn btn-primary btn-large') }}
    </div>
</fieldset>
    
</form>

{{ link_to("/secure/id/forgotPassword", "Forgot my password") }}