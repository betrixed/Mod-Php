<footer class='footer'>

        <p class="text-muted">&nbsp;&copy; Mod PHP  | 
            <?php
            echo " Response time " . sprintf('%.2f ms', (microtime(TRUE) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
            echo " | Memory " . sprintf('%.2f MiB', memory_get_peak_usage() / 1024 / 1024);
            ?>
            &nbsp; <!-- {{ myDir }} -->
        </p>

</footer>