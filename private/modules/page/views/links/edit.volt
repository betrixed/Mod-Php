
<style>
    label.control-label {
        min-width:110px;
        text-align:right;
        margin-right:10px;
    }

    span.input-group-addon {
        display: inline-table;
    }
</style>
<script type="text/javascript">
    function doDelete(myform)
    {
        myform.action = '{{ url("links/delete") }}';
        myform.method = 'post';

        myform.submit();
        return false;
    }
</script>


<div class="container-fluid">
    <div class="row">
        {{ link_to(myController ~ "index", 'Links Index', 'target':'_blank', 'class':'btn btn-warning') }}
        <?php if($isEditor && !(($refid === NULL)or(is_null($refid))) ) { ?>
        {{ link_to(myModule ~ 'blog/edit/' ~ refid,"Edit Blog", 'target':'_blank', 'class':'btn btn-default') }}
        <?php } ?>
    </div>
    <hr />
        <div class="clear"></div>
        <form id="linkform" name="linkform" method='post' onsubmit="smotePost()">

                {{ linkform.renderCustom('sitename') }}

                {{ linkform.renderCustom('title') }}

                {{ linkform.renderCustom('url') }}

                {{ linkform.renderCustom('enabled') }}

                {{ linkform.renderCustom('date_created') }}

                {{ linkform.renderCustom('urltype') }}

                <?php 
                $element = $linkform->get("id"); 
                $id = $element->getValue();
                echo $element;
                ?>


                <div>
                    <button id="airbtn" type="button" 
                            title="This also does a save-submit" 
                            onclick="codeSwitch()">Switch to 'Air-Mode'</button>
                </div>


                <div class="summernote">
                    <div id="summernote"></div>
                </div>

                <div class="form-group">
                    {{ linkform.render('summary') }}
                    {% if id == "0" %}
                    <div class="col-sm-2">
                        <input name='btnUpdate' class='btn btn-success' type='submit' id='btnupdate' value="Create">
                    </div>
                    {% else %}
                    <div class="col-sm-2">
                        <input name='btnDelete' class='btn btn-danger' type='button' id='btndelete' value="Delete" onclick="return doDelete(this.form);"/>
                    </div>
                    <div class="col-sm-4">
                        <input name='btnUpdate' class='btn btn-success' type='submit' id='btnupdate' value="Update">
                    </div>
                    {% endif %}
                </div>
        </form>
</div>

<script type="text/javascript">
    var AboutToSubmit = false;
    function smotePost(f) {
        var content = $('#summernote').summernote('code');
        $("textarea[name=summary]").val(content);
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
            height: "300px",
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
        var html = $("#summary").val();
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
        $('#linkform').trigger('submit');
        window.location = newloc;
    }
</script>

<script type="text/javascript">
    $(function () {
        var opt = {
            format: 'YYYY-MM-DD HH:mm'
        };

        var ct = $('#date_created');

        ct.datetimepicker(opt);
    })();
</script> 