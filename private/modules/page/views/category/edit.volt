
<div class='header'><p>{{ link_to(  myController ~ 'index', 'Category List') }} -> Category</p></div>
<?php
    $form = $this->view->form;
    $catid = $this->view->catid;
?>
<div class="container">
    
    
<form action="{{ '/' ~ myURL ~ catid}}" method="post">

    <p>
        <label>Id</label>
        <?php echo $form->render("id"); ?>
    </p>

    <p>
        <label>Name</label>
        <?php echo $form->render("name"); ?>
    </p>

    <p>
        <label>Name Clean</label>
        <?php echo $form->render("name_clean"); ?>
    </p>
    <p>
        <label>Enabled</label>
        <?php echo $form->render("enabled"); ?>
    </p>
    <p>
        <input type="submit" value="Save" />
    </p>

</form>
</div>