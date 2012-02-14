<meta name="viewport" content="width=device-width" />

<style>
* { margin: 0; padding: 0; }
ul, li { display: block; }
a { border: 0; }
</style>

<p>3 bj pages:</p>
<?php

require '../HTMLExtractor.php';

$steps = steps();
$base = 'http://boobsjournal.com';
$pages = 3;
$P = isset($_GET['page']) ? (int)$_GET['page'] : 0; 
$offset = $pages * $P;

echo '<ul>';
for ( $p=0; $p<$pages; $p++ ) {
	$page = 1 + $p + $offset;
	$url = 1 < $page ? '/page/' . $page . '/' : '/';
	$source = file_get_contents($base . $url);
//var_dump(strlen($source));

	$process = new HTMLExtractionProcess($source, $steps);
	$process->start();

	foreach ( $process->output['thumbnail_srcs'] AS $i => $src ) {
		$href = $process->output['thumbnail_hrefs'][$i];
		echo '<li><a href="' . $href . '"><img src="' . $src . '" /></a></li>';
	}
}
echo '<ul>';

echo '<a href="?page=' . ( $P+1 ) . '">Next page</a>';


function steps() {
	return array(
		// split: one gallery per element
		array(
			'type' => 'split',
			'pattern' => '/gallery\-size\-thumbnail/',
			'slice' => array(1),
			'next' => array(
				// split: one thumb per element
				array(
					'type' => 'split',
					'pattern' => '/<dt/',
					'slice' => array(1),
					'next' => array(
						// match one: the thumbnail
						array(
							'type' => 'match one',
							'pattern' => '/src=(?:\'|")([^\'"]+)/',
							'save' => 'thumbnail_srcs',
						),
						// match one: the first link
						array(
							'type' => 'match one',
							'pattern' => '/href=(?:\'|")([^\'"]+)/',
							'save' => 'thumbnail_hrefs',
						),
					),
				),
			),
		),
	);
}


