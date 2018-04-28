<?php
        if (count($events) > 0) {
        ?>
        <div id="events-menu" class="sidelink" style="background-color:#FAFBDA;">
            <p class="side_title">Events</p>
            <p><?php foreach ($events as $evt) { ?>
            <li ><?php 
                $dt = new DateTime($evt->fromTime);
                echo '<b>' . $dt->format('d-M-y') .' </b><br>';
                echo $this->tag->linkTo(array("article/" . $evt->title_clean, $evt->title)); 
                ?></li>
            <?php } ?>
            </p>
        </div>
        <?php } ?>
        <!--
        <a href="http://parracan.org/article/climate-sensitivity-invalidates-carbon-budget">
            <img src="/image/gallery/Coal/climateimperative.jpg" width="100%"  class="img"/>
        </a>
        <div class="center-block">
            <p class="text-center">Global Warming Plan</p>
	<a href="http://www.theguardian.com/commentisfree/picture/2015/dec/14/brenda-the-civil-disobedience-penguin-explains-the-paris-climate-agreement-vive-la-difference" >
            <img src="/image/gallery/Outlook/theplan.jpg" width="100%" />
        </a>
        </div>
        <a href="http://parracan.org/article/greg-hunt-as-minister-for-direct-eco-fraud">
            <img src="/image/gallery/Coal/coalecofraud3.jpg" width="100%"   class="img"/>
        </a>
        -->
<?php   if (count($sides) > 0) {
        foreach ($sides as $box) { 
?>
        <div class="sidebox">
                <p class="side_title">{{link_to(box.url, box.title, 0)}}</p>
                {{box.summary}}

        </div>
<?php } } ?>
        <div id="blogs-menu" class="sidelink" style="background-color:#FAEBDA;">
            <p class="side_title">Recent</p>
            <p><?php foreach ($recent as $blog) { ?>
            <li ><?php echo $this->tag->linkTo(array("article/" . $blog->title_clean, $blog->title)); ?></li>
            <?php } ?></p>
        </div>
        <?php if (count($campaigns) > 0) { ?>
        <div id="campaigns" class="sidelink" style="background-color:#CBFBFA;">

            <p class="side_title">Campaigns</p>
            <p><?php foreach ($campaigns as $blog) { ?>
            <li ><?php
                echo $this->tag->linkTo($blog->url, $blog->title);
                ?></li>
            <?php } ?>
            </p>
        </div> 
        <?php }?>
        <div id="links" class="sidelink" style="background-color:#EBFBDA;">
            <p class="side_title">Sustainability</p>
            <p>
            <li >{{ link_to('http://www.worldwatch.org/','World Watch Institute',0) }}</li>
            <li >{{ link_to('http://www.postcarbon.org/','Post Carbon Institute',0) }}</li>
            <li >{{ link_to('http://ourfiniteworld.com/','Our Finite World : Gail Tverberg',0) }}</li>
            <li >{{ link_to('http://www.sustainable.unimelb.edu.au/files/mssi/MSSI-ResearchPaper-4_Turner_2014.pdf','Is Global Collapse Imminent? : Graham Turner',0) }}</li>
            <li >{{ link_to('http://steadystate.org/',"Steady State Economy",0) }} </li>
            <li >{{ link_to('http://cassandralegacy.blogspot.it/',"Resource Crisis",0) }} </li>
            <li >{{ link_to('http://resilience.org/',"Resilience",0) }} </li>
            </p>
        </div>        
        <div id="links" class="sidelink" style="background-color:#FAFBDA;">
            <p class="side_title">Australia</p>
            <p>
            <li >{{ link_to('http://www.tai.org.au/','The Australia Institute',0) }}</li>
            <li >{{ link_to('https://www.climatecouncil.org.au/','Climate Council',0) }}</li>
            <li >{{ link_to('http://steadystatensw.wordpress.com/','Steady State Economy NSW',0) }}</li>
            <li >{{ link_to('http://www.westconnex.info/','No WestCONnex',0) }}</li>
            </p>
        </div> 
        

