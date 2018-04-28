

{{ content() }}


<div style="margin-left:100px;">
    <br/>


    <?php 
    $myview = $this->view;
    $catId = $myview->catId;
    $orderalt = $myview->orderalt;
    $col_arrow = $myview->col_arrow;
    $isEditor = $myview->isEditor;
    $user_id = $myview->userId;
    $catItems = $myview->catItems;
    $burl = "/page/edit/";

    if ($myview->catId > 0)
    {
    $ubase = $burl . "index?catId=$catId&orderby=";
    }
    else {
    $ubase = $burl . "index?orderby=";
    }
    $link = $ubase.$myview->orderby;  

    $page = $myview->page;
    if ( $page->last > 1)
    {
    echo " " . $this->tag->linkTo($link, 'First');
    echo " | " . $this->tag->linkTo($link.'&page=' . $page->before, 'Previous');
    echo " | " .  $this->tag->linkTo($link.'&page=' . $page->next, 'Next');
    echo " | " .  $this->tag->linkTo($link. '&page=' . $page->last, 'Last');
    echo " | " .  $page->current, "/", $page->last . " | ";
    }
    echo $this->tag->linkTo(array($burl . "new", "New Article ", "class" => "button" )); 
    ?>

    <?php
    $ct = count($myview->catItems);
    if ($ct > 0)
    {
    ?>
    <div class="container">
        <form method='get'>
            <label>Category</label>
            <select id="catId" name="catId">
                <option value="0">- None -</option>
                {% for category in catItems %}
                <option value='{{ category.id }}' {% if category.id == catId %}selected{% endif %}>{{ category.name }}</option>
                {% endfor %}
            </select>
            {{ submit_button('Fetch') }}
        </form>
    </div>
    <?php
    }
    ?>
</div>


<div class="container">
    <table class="table table-bordered table-striped" align="center">

        <thead>
            <tr>
                <th style="width:50%;"><?php echo $this->tag->linkTo($ubase.$orderalt['title'], 'Title') . $col_arrow['title'] ?></th>
                <th style="width:15%;"><?php echo $this->tag->linkTo($ubase.$orderalt['author'], 'Author') . $col_arrow['author']  ?></th>
                <th style="width:10%;"><?php echo $this->tag->linkTo($ubase.$orderalt['date'], 'Date') . $col_arrow['date']  ?></th> 
                <?php if ($isEditor) { ?>
                <th>Edit</th>
                <?php } ?>
                <th>Feature</th>
                <th>Enable</th>
                <th>Comment</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($page->items as $blog) { ?>
            <tr>
                <td class="leftCell"> {{ link_to("/article/" ~ blog.title_clean, blog.title) }} </td>
                <td>{{ blog.author_name }}</td>


                <td><?php 
                    // reformat
                    $dt = new DateTime($blog->date_published);
                    echo $dt->format('d M y'); ?></td>
                {% if isEditor %}
                <td>
                    {% if blog.canEdit %}
                        {{ link_to(myModule ~ "edit/blog/" ~ blog.id, "Edit") }}
                    {% endif %}
                </td>
                {% endif %}
                <td><?php
                    if ($blog->featured == 1) {
                    echo $this->tag->image("/image/gallery/site/tick16.png");
                    }
                    ?></td>
                <td><?php
                    if ($blog->enabled == 1) {
                    echo $this->tag->image("/image/gallery/site/tick16.png");
                    }
                    ?></td>
                <td><?php
                    if ($blog->comments == 1) {
                    echo $this->tag->image("/image/gallery/site/tick16.png");
                    }
                    ?></td>

            </tr>
            <?php } ?>
        </tbody>

    </table>

</div>
