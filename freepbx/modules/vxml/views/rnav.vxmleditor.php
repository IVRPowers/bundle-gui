<?php 
$li[] = '<a href="javascript:;" onclick=\'deploy("newfolder")\'>' . _("Create Directory") . '</a>';
$li[] = '<a href="javascript:;" onclick=\'deploy("newfile")\'>' . _("Create File") . '</a>';

echo '<div class="rnav">' . ul($li) . '</div>';
?>