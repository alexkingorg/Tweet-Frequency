<?php

define('DB_USERNAME', 'username');
define('DB_PASSWORD', 'password');
define('DB_NAME', 'tweet_frequency');
define('DB_TABLE', 'tweets_005');

define('LIMIT_DAYS', '30');

set_time_limit(0);

header('Content-type: text/plain');

$db_connect = mysql_connect('localhost', DB_USERNAME, DB_PASSWORD);
mysql_select_db(DB_NAME, $db_connect);

$counts = $limits = array();

$day_from = '2011-01-02';
$day_range = floor(LIMIT_DAYS / 4);

for ($i = 1; $i <= 4; $i++) {
// last $day_range, starting with today
	$from = date('Y-m-d 00:00:00', strtotime('-'.($day_range * $i).' days', strtotime($day_from)));
	$to = date('Y-m-d 00:00:00', strtotime('-'.($day_range * ($i - 1)).' days', strtotime($day_from)));
	$result = mysql_query("
		SELECT COUNT(id) as tweet_count
		FROM `".DB_TABLE."`
		WHERE `day` > '$from'
		AND `day` <= '$to'
	");
	while ($data = mysql_fetch_object($result)) {
		echo $from.' - '.$to.': '.$data->tweet_count.PHP_EOL;
	}
}