{{ content() }}


<div class='container'>
<div align="center">
    <h2>Create Content</h2>
</div>
<form action="{{myModule ~ 'edit/new'}}">
<table>
    <tr>
        <td align="right">
            <label for="title">Title</label>
        </td>
        <td align="left">
            <?php echo $this->tag->textField(array("title", "size" => 30, "type" => "hidden")) ?>
        </td>
    </tr>
    <tr>
        <td align="right">{{ submit_button("Save" , 'class':'pure-button pure-button-primary')}}</td>
    </tr>

</table>

</form>
</div>


