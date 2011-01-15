<?php

define('DB_USERNAME', 'username');
define('DB_PASSWORD', 'password');
define('DB_NAME', 'tweet_frequency');
define('DB_TABLE', 'tweets_006');

define('LIMIT_DAYS', '30');

set_time_limit(0);

header('Content-type: text/plain');

$db_connect = mysql_connect('localhost', DB_USERNAME, DB_PASSWORD);
mysql_select_db(DB_NAME, $db_connect);

mysql_query("
CREATE TABLE IF NOT EXISTS `".DB_TABLE."` (
  `id` int(5) NOT NULL auto_increment,
  `timestamp` datetime NOT NULL,
  `day` datetime NOT NULL,
  `retweet` int(1) DEFAULT 0,
  `reply` int(1) DEFAULT 0,
  `tweet_id` varchar(255) NOT NULL,
  `screen_name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
") or die(mysql_error());

mysql_query("
	TRUNCATE TABLE `".DB_TABLE."`
") or die(mysql_error());

// pull users from cookie

if (!empty($_COOKIE['tf_tw_users'])) {
	$tw_users = json_decode(stripslashes($_COOKIE['tf_tw_users']), true);
}
if (!is_array($tw_users) || !count($tw_users)) {
	die('Error finding users, try Step 1.');
}

$requests = 0;
$request_limit = 100;
$user_limit = 10;
$user_count = 0;

foreach ($tw_users as $tw_user) {
	$user_count++;
	$forced = false;
	for ($i = 1; $i <= $user_limit; $i++) { // pages
		$args = array(
			'screen_name' => $tw_user,
			'count' => '200',
			'page' => $i,
		);
		$url = 'http://api.twitter.com/1/statuses/user_timeline.json?'.http_build_query($args);
		sleep(1);
		if ($data = file_get_contents($url)) {
			if ($tweets = json_decode($data)) {
				if (!is_array($tweets) || !count($tweets)) {
					echo $tw_user.' failed to return tweets'.PHP_EOL;
					$i = $user_limit;
					$forced = true;
					break;
				}
				else {
					$insert = array();
					foreach ($tweets as $tweet) {
// limit by date
						if (strtotime($tweet->created_at) < strtotime('-'.LIMIT_DAYS.' days')) {
							echo $tw_user.' maxed out'.PHP_EOL;
							$i = $user_limit;
							$forced = true;
							break;
						}
						$data = array();
						$data['timestamp'] = mysql_real_escape_string(date('Y-m-d H:i:s', strtotime($tweet->created_at)));
						$data['date'] = mysql_real_escape_string(date('Y-m-d 00:00:00', strtotime($tweet->created_at)));
						$data['retweet'] = (int) !empty($tweet->retweeted_status);
						$data['reply'] = (int) !empty($tweet->in_reply_to_screen_name);
						$data['tweet_id'] = mysql_real_escape_string($tweet->id);
						$data['screen_name'] = mysql_real_escape_string($tweet->user->screen_name);
						$insert[] = '("'.implode('", "', $data).'")';
					}
					mysql_query("
						INSERT INTO `".DB_TABLE."` (
							`timestamp`,
							`day`,
							`retweet`,
							`reply`,
							`tweet_id`,
							`screen_name`
						)
						VALUES ".implode(', '.PHP_EOL, $insert)."
					");
					echo '  inserted '.count($insert).' tweets'.PHP_EOL;
				}
			}
		}
		if ($i == $user_limit && !$forced) {
			echo $tw_user.' hit limit'.PHP_EOL;
		}
		$requests++;
		flush();
	}
	if ($requests > $request_limit) {
		break;
	}
}

echo PHP_EOL.PHP_EOL.'---'.PHP_EOL.PHP_EOL.'Fetched '.$user_count.' users.'.PHP_EOL.'Last fetched: '.$tw_user;
