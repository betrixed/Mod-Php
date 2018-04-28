{{ content() }}

{{ javascript_include("assets/js/tinymce/tinymce.min.js") }}
{{ javascript_include("assets/js/comment.js") }}

<?php 
$blog = $this->view->blog;
$metadata = $this->view->metadata;
?>
{% include "partials/facebook.volt" %}
<div class='container-fluid'>
    
    <div id='article-box'>
        <h2>{{ blog.title }}</h2>
        <div class='{{blog.style}}' id='article'>{{ blog.article }}</div>
        <?php if (count($metadata) > 0) { ?>
            <div class='container-fluid' id='tags' style='font-size-adjust:0.5;'>
                <table class='table table-nonfluid table-condensed' style='outline: 1px solid orange;'>
                    <tbody>
                        {% for mtag in metadata %}
                        <?php if (($mtag->display == 1) && (strlen($mtag->content) > 0)) { ?>
                            <tr>
                                <td class='rightCell'>{{mtag.meta_name}}:</td>
                                <td class='leftCell'>{{mtag.content}}</td>
                            </tr>
                        <?php } ?>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            <hr />
        <?php } ?> 
        <!-- include "comment/load_edit.volt" %}
         include "comment/load_page.volt" %} -->
        
    </div>
   
   {% if metadata %}
   <div class="fb-share-button" 
    data-href="{{ canonical }}" 
    data-layout="button_count">
   </div>
   {% endif %}
    {% if blog.comments %}
    <div>
        <div id="fb-root"></div>
        <fb:login-button autologoutlink="True" length="short" background="white" size="large"></fb:login-button>
    <div class="fb-comments" data-href="{{canonical}}" data-width="900" data-numposts="5"></div>
        
    </div>
    {% endif %}
</div>






