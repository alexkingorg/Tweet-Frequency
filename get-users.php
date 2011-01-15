<?php

set_time_limit(0);

include('config.php');

// get all users

$cursor = '-1';
$tw_users = array();
$i = 0;
while ($cursor != '0') {
	$url = 'http://api.twitter.com/1/statuses/friends/'.$username.'.json?cursor='.$cursor;
	if ($data = file_get_contents($url)) {
		if ($data = json_decode($data)) {
			foreach ($data->users as $tw_user) {
				if (isset($tw_user->screen_name)) {
					$tw_users[] = $tw_user->screen_name;
				}
			}
			$cursor = $data->next_cursor_str;
		}
	}
}
sort($tw_users);

setcookie('tf_tw_users', json_encode($tw_users), strtotime('+1 week'));

die(count($tw_users).' users found. On to step 2.');