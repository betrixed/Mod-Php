{{ content() }}

<div class="container">
    <h1>Create Image Gallery</h1>
    <form method="post">
        <table class='table'>
            <tbody>
                <?php
                //Traverse the form
                foreach ($myform as $element) {

                //Get any generated messages for the current element
                $messages = $myform->getMessagesFor($element->getName());

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
                echo '<label for="', $element->getName(), '">', $element->getLabel(), '</label>';
                echo '</td><td class="leftCell">';
                echo $element;
                echo '</td></tr>' . PHP_EOL;
                }
                ?>
                <tr><td><input name='btnCreate' class='btn btn-success' type='submit' id='btnCreate' value="Create"</td></tr>
            </tbody>
        </table>
    </form>
</div>