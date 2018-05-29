{{ content() }}

<script language='javascript'>
    function doDelete(myform)
    {
        myform.action = '{{ url("links/delete") }}';
        myform.method = 'post';

        myform.submit();
        return false;
    }
</script>
<div class="container">
    <form id="linkform" name="linkform" method='post' onsubmit="smotePost()">


        <table class='table'>
            <tbody>
                {% for element in linkform %}
                <?php 
                    $ename = $element->getName(); 
                    $label = $element->getLabel();
                    $messages = $linkform->getMessagesFor($element->getName());
                    if (count($messages)) {
                ?>
                    <tr><td><div class="messages">
                       {% for msg in messages %}
                       <p>{{ msg }}</p>
                       {% endfor %}
                    </div></td></tr>
                <?php
                }
                ?>
                    {% if ename !== 'summary' %}
                    <tr><td class="rightCell">
                            <label for="{{ ename }}">{{label}}</label>
                        </td>
                        <td class="leftCell">{{ element }}</td>
                    </tr>
                    {% else %}
                <?php $summary = $element; ?>
                    {% endif %}
                {% endfor %}

                <tr><td><input name='btnDelete' class='btn btn-danger' type='button' id='btndelete' value="Delete" onClick="return doDelete(this.form);"/></td>
                    <td><input name='btnUpdate' class='btn btn-success' type='submit' id='btnupdate' value="Update"</td></tr>
            </tbody>
        </table>
           <button id="airbtn" type="button" title="This also does a save-submit" onclick="codeSwitch()">Switch to 'Air-Mode'</button>

        <?php
        if (isset($summary))
        {
        echo PHP_EOL . '<div class="summernote">';
        echo PHP_EOL . '<div id="summernote">';

        echo PHP_EOL . '</div></div>';
        echo PHP_EOL . $summary;
        }
        ?>

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