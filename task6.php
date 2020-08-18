<?php
$content = file_get_contents("count.txt");
echo $content;
file_put_contents("count.txt",intval($content)+1);