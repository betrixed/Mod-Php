<html>
    <head>
   
       <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link href="/css/bootstrap-3.2/css/bootstrap.min.css" rel="stylesheet" />
        <link href="/css/bootstrap-3.2/css/bootstrap-theme.min.css" rel="stylesheet" />
        <!-- JQuery must be first -->
        <script src="/js/jquery/jquery-2.1.1.min.js" type="text/javascript" ></script>
        <script src="/css/bootstrap-3.2/js/bootstrap.min.js" type="text/javascript" ></script>
        
        {{ stylesheet_link("/css/font-awesome.min.css", false) }}
     
    </head>
        <body>
<div class="container" style='margin:10px;'>
    
    <form name='iform' method="post">
        {{ hidden_field("id", 'value':image.id) }}
        <div class='row'>
        <label>File</label>
        
        <div class='row-fluid'>
            {{ text_field("name", 'size':45, 'value':image.name) }}
        </div>
       <label>Description</label>
        <div class='row-fluid'>
            {{ text_area('description', 'cols':45, 'rows':6, 'value':image.description) }}
            </div>
        </div>
        {{ submit_button("Save", 'onClick':'myClose();') }} 
    </form>
</div>
            
<script type='text/javascript'>
    window.onunload = function(){
        if (window.opener && !window.opener.closed)
        {
            window.opener.popupClosed();
        }
    };
    
    function myClose() {
        document.forms['iform'].submit();
        window.close();
    }
</script>

    </body>
</html>