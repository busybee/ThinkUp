<?php   # invoke this hourly in cron
require_once('config.crawler.inc.php');
ini_set("include_path", ini_get("include_path").":".$INCLUDE_PATH);
require_once("init.php");

// Instantiate and initialize needed objects
$db = new Database();
$conn = $db->getConnection();
$id = new InstanceDAO();
$oid = new OwnerInstanceDAO();

$instances = $id->getAllInstancesStalestFirst();
foreach ($instances as $i) {
	$crawler = new Crawler($i);
	$cfg = new Config($i->twitter_username, $i->twitter_user_id);
	$logger = new Logger($i->twitter_username);
	$tokens = $oid->getOAuthTokens($i->id);
	$api = new CrawlerTwitterAPIAccessorOAuth($tokens['oauth_access_token'], $tokens['oauth_access_token_secret'], $cfg, $i);
	$api -> init($logger);

	if ( $api->available_api_calls_for_crawler > 0 ) {
	
		$crawler->fetchOwnerInfo($cfg, $api, $logger);

		$crawler->fetchOwnerTweets($cfg, $api, $logger);
	
		$crawler->fetchOwnerReplies($cfg, $api, $logger);

		$crawler->fetchOwnerFriends($cfg, $api, $logger);

		$crawler->fetchOwnerFollowers($cfg, $api, $logger);

		$crawler->fetchStrayRepliedToTweets($cfg, $api, $logger);

		$crawler->fetchUnloadedFollowerDetails($cfg, $api, $logger);


		// TODO: Get direct messages
		// TODO: Gather favorites data

		// Save instance
		$id->save($crawler->instance,  $crawler->owner_object->tweet_count, $logger, $api);
	} 
	$logger->close();			# Close logging
}

if ( isset($conn) ) $db->closeConnection($conn); // Clean up
?>