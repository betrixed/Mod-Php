{{ content() }}

<script type="text/javascript">
    function doDelete(myform)
    {
        myform.action = '{{ url("resource/delete") }}';
        myform.method = 'post';

        myform.submit();
        return false;
    }
</script>


<div class="container">
    <span class='pull-right'> {{ link_to(myController ~ 'index',"List", 'target':'_blank') }}</span>

 
    <form id="form" name="form" method='post' onsubmit="smotePost()">
        <?php
        $elementId = $form->get("id");
        echo $elementId;
        ?>
        <table class='table'>
            <tbody>
                <?php
                $summary = null;
                //Traverse the form
                foreach ($form as $element) {

                //Get any generated messages for the current element
                $ename = $element->getName();
                $label = $element->getLabel();
                
                $messages = $form->getMessagesFor($ename);
                
                if ($ename === 'id')
                {
                    continue;
                }
                if (count($messages)) {
                    echo '<tr><td>';
                    //Print each element
                    echo '<div class="messages">';
                    foreach ($messages as $message) {
                    echo $message;
                    }
                    echo '</div>';
                    echo '</td></tr>';
                }
                

                    echo '<tr><td class="rightCell">';
                    echo '<label for="', $ename, '">', $label, '</label>';
                    echo '</td><td class="leftCell">';
                    echo $element;
                    echo '</td></tr>' . PHP_EOL;
                }

                
                ?>

            </tbody>
        </table>
         

<div class="row">
    {{ submit_button("Save", 'class':'submit') }}
</div>
        
    </form>
</div>

