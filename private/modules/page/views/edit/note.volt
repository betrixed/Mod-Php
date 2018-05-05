
<?php 
echo $this->getContent(); 
$myview = $this->view;
$id = $myview->id;
$blog = $myview->blog;
$metatags = $myview->metatags;
//$events = $myview->events;
$categoryList = $myview->categoryList;
$cat_values = $myview->cat_values;
$cat_blogid = $myview->cat_blogid;
//$events = $myview->events;
?>

<div class='row center'>

            {{ link_to( myController ~ "comment/" ~ id, "Internal Comment", 'target':'_blank', 'class':'btn btn-default') }}
            {{ link_to( myModule ~ "review/form/" ~ id, "Request Review", 'target':'_blank', 'class':'btn btn-default') }}
            {{ link_to( myModule ~ "links/blog/" ~ id, "Generate Link", 'target':'_blank', 'class':'btn btn-default') }}
            {{ link_to( myModule ~ "blog/email/" ~ id, "Email Content", 'target':'_blank', 'class':'btn btn-default') }}
            {{ link_to( myModule ~ "blog/email/" ~ id, "Email Content", 'target':'_blank', 'class':'btn btn-default') }}
             {{ link_to( myModule ~ "gallery/index", "Edit Gallery", 'target':'_blank', 'class':'btn btn-default') }}

</div>

<form action="{{  myController ~ 'blog/' ~ id}}" 
      id="postForm" 
      method='post'
      onsubmit='smotePost()'>
    <?= $this->tag->hiddenField(["id", "value" => $id]) ?>
    <div class='panel panel-success'>
        <div class='panel-heading'>Blog Contents</div>
        <div class='panel-body'>

            <div class='form-group'>
                <label class='col-form-label' for="title">Title</label>
                <?= $this->tag->textField(array("title", "size" => 40)) ?>
                 <label class=' col-form-label' for="issue">Issue</label>
                 <?= $this->tag->textField(array("issue", "type" => "number", "size" =>4)) ?>

            </div>
            <div class='form-group'>
                <label for="lock_url">Unlock URL</label>

                     <?= $this->tag->checkField(array("lock_url", "size" => 4)) ?>
                    <label for="title_clean">Unique URL</label>

                    <?= $this->tag->textField(array("title_clean", "size" => 40)) ?>
            </div>
             <div class='form-group pull-right'>
                                {{ 'Updated ' ~ blog.date_updated ~ ' &nbsp;'  }}
                     {{ submit_button('Save' , 'class':'btn btn-success') }}
             </div>
            
            <div class='form-group'>
            <label for="class_select">Select style wrapper</label>
          
            
            <?php 
            use Page\Models\BlogStyle;
            $stylelist = BlogStyle::find();

            echo $this->tag->select(
            [  
            "style", $stylelist, "using" => [ "style_class", "style_name",],
            "useEmpty" => false,
            "onChange" => "wrapStyle()",
            ]
            );
            ?>
            </div>
            <div class='form-group pull-right'>
            <label for="summernote">Article Text</label>
            <button id="airbtn" type="button" class='col-form-button' title="This also does a save-submit" 
                    onclick="codeSwitch()">Switch to 'Air-Mode'</button>
            </div>
            <div class="clear"></div>
            <div id='wrap_style' class="{{ get_value('style') }}">
                <div id="summernote" ><p>Hello</p></div>
            </div>
            <?= $this->tag->textArea(array("article", "id" => "article", 
                    "style" => "display:none;", "cols" =>100, "rows" => 25)) 
            ?>
        </div>
    </div>
    <div class='panel panel-success'>
        <div class="panel-heading">Author / Flags</div>
        <div class='panel-body'>
            <table class='table table-bordered' style='width:600px;'>
                <tbody>
                    <tr>
                        <td class='rightCell'>
                            <label for="author_id">Author</label>
                        </td>
                        <td class='leftCell'>
                            <?php echo $this->tag->textField(array("author_id", "type" => "number", "readonly" => "readonly" )) ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='rightCell'>
                            <label for="date_published">Published on</label>
                        </td>
                        <td class='leftCell'>
                            <?php echo $this->tag->textField(array("date_published", "size" => 30)) ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='rightCell'><label for="enabled">Enabled</label></td>
                        <td class='leftCell'><?php echo $this->tag->checkField(array("enabled", "value" => $blog->enabled)); ?></td>
                    </tr>
                    <tr>
                        <td class='rightCell'><label for="featured">Featured</label></td>
                        <td class='leftCell'><?php echo $this->tag->checkField(array("featured", "value" => $blog->featured)); ?></td>
                    </tr>
                    <tr>
                        <td class='rightCell'><label for="comments">Comments</label></td>
                        <td class='leftCell'><?php echo $this->tag->checkField(array("comments", "value" => $blog->comments)); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class='panel panel-success'>
        <div class='panel-heading'>Meta-Tags for Search Engines</div>
        <div class='panel-body'>

                <table class='table table-striped'>
                    <tbody>
                        <?php
                        foreach ($metatags as $meta) {
                        // generate a name using a prefix.
                        $label = $meta->meta_name;
                        $name = 'metatag-' . $meta->id;
                        $value = $meta->content;
                        $setup = array($name, 'value' => $meta->content, 'size' => 60, 'cols' => 60, 'maxlength' => $meta->data_limit);

                        if ($meta->data_limit <= 80) {
                        $input = $this->tag->textField($setup);
                        } else {
                        $input = $this->tag->textArea($setup);
                        }
                        ?>
                        <tr>
                            <td class='rightCell'><label for='{{ name }}'> {{ label }}</label>
                                <td class='leftCell'>{{ input }}</td>
                        </tr> 
                        <?php } ?>

                    </tbody>
                </table>

           
        </div>
    </div>
 </form>

    <div class='panel panel-success'>

        <div class='panel-heading'>Blog Categories</div>
        <div class='panel-body'>
            <div class="container-fluid padborder">
                <div id="category_status" >{% include "edit/category.volt" %}</div>
            </div>
        </div>
    </div>

    <div class='panel panel-success'>
        <div class='panel-heading'>Event Dates</div>
        <div class='panel-body'>
            <div class="container padborder">
                <div id="event_status" >{% include "edit/event.volt" %}</div>
                <form id='eventForm' action="{{ myController ~ 'event'}}" method="post">
                    <input type="hidden" name="event_id" value="" />
                    <input type="hidden" name="event_blogid" value="{{ id}}" />
                    <p>Add Event</p>
                    <div class="form-group">
                        <div class='input-group date' id='fromDate' >
                            <input type='text' class="form-control" name="fromDate"  />
                            <span class="input-group-addon">
                                <span class="glyphicon glyphicon-calendar"></span>
                            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class='input-group date' id='toDate'>
                            <input type='text' class="form-control" name="toDate"  />
                            <span class="input-group-addon">
                                <span class="glyphicon glyphicon-calendar"></span>
                            </span>
                        </div>    
                    </div>
                    <input type="submit" class='btn-danger' value='Add Event'></input>
                </form>
            </div>
        </div>
    </div>




    <script type="text/javascript">
        var AboutToSubmit = false;

        function smotePost() {

            var content = $('#summernote').summernote('code');
            $("textarea[name=article]").val(content);
            AboutToSubmit = true;
            return true;
        }

        var getUrlParameter = function getUrlParameter(sParam) {
            var sPageURL = decodeURIComponent(window.location.search.substring(1)),
                    sURLVariables = sPageURL.split('&'),
                    sParameterName,
                    i;

            for (i = 0; i < sURLVariables.length; i++) {
                sParameterName = sURLVariables[i].split('=');

                if (sParameterName[0] === sParam) {
                    return sParameterName[1] === undefined ? true : sParameterName[1];
                }
            }
        };

        function smoteOptions(isAirMode) {
            smote = {
                popover: {
                    image: [
                        ['imagesize', ['imageSize100', 'imageSize50', 'imageSize25']],
                        ['float', ['floatLeft', 'floatRight', 'floatNone']],
                        ['remove', ['removeMedia']]
                    ],
                    link: [
                        ['link', ['linkDialogShow', 'unlink']]
                    ],
                    air: [
                        ['text', ['bold', 'italic', 'underline', 'color', 'clear']],
                        ['fontsize', ['fontsize']],
                        ['font', ['strikethrough', 'superscript', 'subscript']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['table', ['table']],
                        ['insert', ['link', 'picture', 'video', 'hr']]
                    ],

                },
                height: "600px",
                toolbar: [
                    ['style', ['style']],
                    ['text', ['bold', 'italic', 'underline', 'color', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['height', ['height']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['fontsize', ['fontsize']],
                    ['font', ['fontname']],
                    ['insert', ['link', 'picture', 'video', 'hr', 'readmore']],
                    ['view', ['fullscreen', 'codeview']]
                ],
                fontsize: '16px',
                callbacks: {
                    onChange: function (contents) {
                        if (contents) {
                            var winEvent = window.attachEvent || window.addEventListener;
                            var chkEvent = window.attachEvent ? 'onbeforeunload' : 'beforeunload';

                            winEvent(chkEvent, function (e) {
                                if (AboutToSubmit)
                                    return true;
                                var confirmationMessage = 'This page is asking you to confirm that you want to leave - data you have entered may not be saved';
                                (e || window.event).returnValue = confirmationMessage;
                                return confirmationMessage;
                            });
                        }
                    }
                }
            };
            if (isAirMode)
                smote['airMode'] = true;
            return smote;
        }
        $(document).ready(function () {

            var html = $("#article").val();

            $("#summernote").html(html);

            var airMode = getUrlParameter('airmode');
            var isAirMode = (airMode == '1');

            $("#summernote").summernote(smoteOptions(isAirMode));
            if (isAirMode)
            {
                $('#airbtn').html('Switch to Editor');
            }

        });

        function codeSwitch()
        {
            var airMode = getUrlParameter('airmode');
            var isAirMode = (airMode == '1');
            var loc = window.location;
            var newloc = loc.protocol + '//' + loc.host + loc.pathname;
            if (!isAirMode)
            {
                newloc = newloc + '?airmode=1';
            }
            // force submit
            $('#postForm').trigger('submit');
            window.location = newloc;
        }

        function wrapStyle()
        {
            var sel = $('#style').val();
            $('#wrap_style').attr('class', sel);
        }
    </script>


    <script type="text/javascript">
        $(function () {
            var opt = {
                format: 'YYYY-MM-DD HH:mm'
            };

            var ct = $('#fromDate');

            ct.datetimepicker(opt);

            ct = $('#toDate');

            ct.datetimepicker(opt);

            var status = $('#event_status');

            $('#eventForm').ajaxForm({
                complete: function (xhr) {
                    status.html(xhr.responseText);
                    status.style.display = 'block';
                    document.refresh;
                }
            });

        })();
    </script>