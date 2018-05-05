<!DOCTYPE html>
<html>
    <?php echo '<!--', __FILE__, ' -->'; ?>
    <head>
        <meta charset="utf-8">
         <!--{{ get_title() }}-->
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        {% block assets %} 
        {{ this.elements.getHeaders() }}
        {{ this.elements.getAssets() }}
        {% endblock %}
        
    </head>
    <body>
        {{ content() }}
        {% include "partials/footer.volt" %}
    </body>
    
</html>