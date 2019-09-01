{{ content() }}
{% if view.google['recaptcha'] %}
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script type='text/javascript'>
function mysubmit()
{
    document.getElementById("eform").submit();
}
</script>
{% endif %}

<style>
    label.control-label {
        min-width:110px;
        text-align:right;
        margin-right:10px;
    }
    
    a.btn {
        margin-right:10px;
    }
</style>   

    {% if blog %}
 <div class="container-fluid ">
     <div class="{{blog.style}}" >
         {{blog.article}}
     </div>
 </div> 
    {% endif %}
    <div class='container-fluid'>
<div class='panel panel-default'>
    <div class='panel-heading center'>Email Form</div>
<div class='panel-body'>


        <form id='eform' method='post'>
{{ form.renderCustom('name')}}
{{ form.renderCustom('telephone')}}
{{ form.renderCustom('email')}}      
{{ form.renderCustom('body')}}  
{% if not view.donePost %}        
    {% if google['loginCaptcha'] %}
     <div class="row center">
          <button class="g-recaptcha" data-sitekey="{{ google['captchaPublic'] }}" data-callback="mysubmit">Send as Email</button>
     </div>
    {% else %}
    <div class="row center">
        {{ submit_button('Send as Email', 'class': 'btn btn-primary') }}
    </div>
    {% endif %}
 {% else %}
    <div class="row center">
        {{ submit_button('Send as Email', 'class': 'btn btn-primary') }}
    </div>
{% endif %}
        </form>
</div>
</div>
    </div>
