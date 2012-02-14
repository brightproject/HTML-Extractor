<?php

header('Content-type: text/plain');

define('RSS_DATE_FORMAT', 'r');

$user = isset($_GET['user']) ? (int)$_GET['user'] : 890274;

//$url = 'http://drupal.org/user/890274/track'; // rudiedirkx
//$url = 'http://drupal.org/user/806366/track'; // chertzog

$url = 'http://drupal.org/user/' . $user . '/track';

require '../HTMLExtractor.php';

// Step 1: download HTML page
$html = file_get_contents($url);

// Step 2: get <table>
$steps = array(
	array(
		'type' => 'split',
		'pattern' => '/id="tracker"/',
		'slice' => array(1),
		'next' => array(
			array(
				'type' => 'match one',
				'pattern' => '#(<table[^>]*>.+?</table>)#is',
				'save' => 'tmp',
			),
		),
	),
);

$process = new HTMLExtractionProcess($html, $steps);
$process->start();

// Step 3: parse <table> and retrieve rows
$xml = simplexml_load_string($process->output['tmp'][0]);

$lastUpdate = null;

$issues = array();
foreach ( $xml->tbody->tr AS $tr ) {
	$uri = (string)$tr->td[1]->a['href'];
	$link = 'http://drupal.org' . $uri;

	$title = (string)$tr->td[1]->a;

	$timeAgo = substr((string)$tr->td[4], 0, -4);
	$timeAgo = preg_replace('/(\d+)/', '-$1', $timeAgo);
	$pubDate = date(RSS_DATE_FORMAT, strtotime($timeAgo));
	if ( !$lastUpdate ) {
		$lastUpdate = $pubDate;
	}

	$issues[$link] = array(
		'title' => $title,
		'pubDate' => $pubDate,
	);
}

date_default_timezone_set('UTC');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

?>
<rss version="2.0">
	<channel>
		<title>Drupal posts feed for user # <?=$user?></title>
		<description>Drupal posts feed for user # <?=$user?></description>
		<link><?=$url?></link>
		<lastBuildDate><?=$lastUpdate?></lastBuildDate>
		<pubDate><?=$lastUpdate?></pubDate>
		<ttl>3600</ttl>

		<?foreach ( $issues AS $link => $info ):?>
			<item>
				<title><?=$info['title']?></title>
				<description><?=$info['title']?></description>
				<link><?=$link?></link>
				<guid><?=$link?></guid>
				<pubDate><?=$info['pubDate']?></pubDate>
			</item>

		<?endforeach?>
	</channel>
</rss>
