{{ content() }}
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<div align="center" class="well" style="max-width:60em">
	{{ form('class': 'form-search') }}

		<table class="table table-hover">
                    <thead>
                        <tr><th colspan='2' class='centerCell'><label>Sign Up</label><p>If successful, an email will be sent with a confirmation link</p></th></tr>
                    
                    </thead>
			<tr>
				<td class='rightCell'>{{ form.label('name') }}</td>
				<td class='leftCell'>
					{{ form.render('name') }}
					{{ form.messages('name') }}
				</td>
			</tr>
			<tr>
				<td  class='rightCell'>{{ form.label('email') }}</td>
				<td class='leftCell'>
					{{ form.render('email') }}
					{{ form.messages('email') }}
				</td>
			</tr>
			<tr>
				<td  class='rightCell'>{{ form.label('password') }}</td>
				<td class='leftCell'>
					{{ form.render('password') }}
					{{ form.messages('password') }}
				</td>
			</tr>
			<tr>
				<td class='rightCell'>{{ form.label('confirmPassword') }}</td>
				<td class='leftCell'>
					{{ form.render('confirmPassword') }}
					{{ form.messages('confirmPassword') }}
				</td>
			</tr>
                        {% if form.hasTerms %}
                        <tr><td class='rightCell'><label>Check me</label></td></tr>
                        {% endif %}
			
          <?php
             $config = $this->getDI()->get('config');
             if ($config->google['signupCaptcha'])
             {
          ?>
          <tr><td class='leftCell'>
                 <div class="g-recaptcha" data-sitekey="{{ google.captchaPublic }}"></div> 
              </td></tr>
          <?php   
             }
          ?>
			<tr>
				<td colspan='2' >{{ form.render('Sign Up') }}</td>
			</tr>
 		</table>

		{{ form.render('csrf', ['value': security.getSessionToken()]) }}
		{{ form.messages('csrf') }}

		<hr>

	</form>

</div>