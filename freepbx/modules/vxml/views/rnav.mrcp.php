<?php

if (!empty($providers)) {
	foreach ($providers as $provider) {
		$li[] = '<a href="config.php?display=mrcp&id='.$provider['id'].'">'._($provider['provider']).'</a>';
	}
}

echo '<div class="rnav">' . ul($li) . '</div>';
?>