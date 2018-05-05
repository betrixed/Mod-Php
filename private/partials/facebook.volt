

{{ this.elements.getFBook() }}

<script id="fbjs" type="text/javascript">
    
   window.fbAsyncInit = function() {
    FB.init({
      appId      : '{{ config.facebook.app_id }}',
      xfbml      : true,
      version    : '{{ config.facebook.default_graph_version }}'
    });
    FB.AppEvents.logPageView();
  };

  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "//connect.facebook.net/en_US/sdk.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));
</script>



