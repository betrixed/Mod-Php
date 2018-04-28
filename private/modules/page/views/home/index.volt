{{ content() }}
<div id='home_div'></div>
<script type="text/javascript">
    $(document).ready()
    {    
        $('#home_div').load('/index/home');
    }
    function linkload(id)
    {
        $('#home_div').load('/index/link/' + id);
    }
</script>

