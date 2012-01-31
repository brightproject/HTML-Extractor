<?php

require 'HTMLExtractor.php';
header('Content-type: text/plain');

$url = './source1.html.txt';
$source = file_get_contents($url);

$steps = array(
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
	// split: category dropdown
	array(
		'type' => 'split',
		'pattern' => '/Select Category/',
		'slice' => array(1),
		'next' => array(
			array(
				'type' => 'match all',
				'pattern' => '#<option[^<]*>([^<]+)</#',
				'first' => true,
				'next' => array(
					array(
						'type' => 'alter',
						'functions' => array(
							'str_replace' => array(array('&nbsp;', ' ', null), 2),
							'trim' => array(array(null), 0),
						),
						'save' => 'categories',
					),
				),
			),
		),
	),
);

$process = new HTMLExtractionProcess($source, $steps);

$process->start();

print_r($process->output);


