<?php

class HTMLExtractionProcess {
	static public $stepClass = 'HTMLExtractionStep';

	public $steps = array();
	public $source = '';
	public $start; // typeof HTMLExtractionStep
	public $output = array();

	function __construct($source, $steps) {
		$this->source = $source;
		$this->steps = $steps;
	}

	function start() {
		// dummy step
		$class = self::$stepClass;
		$this->start = new $class($this, array(
			'next' => $this->steps
		));
		$this->start->out = array($this->source);

		$this->start->children();
	}

	function save( $name, $elements ) {
		$this->output[$name] = $elements;
	}
}

class HTMLExtractionStep {
	public $info;
	public $sources;
	public $out;
	public $children;

	function __construct($process, $info, $sources = null) {
		$this->process = $process;
		$this->info = $info + array('next' => array());
		$this->sources = (array)$sources;
	}

	function start() {
		$info = $this->info;

		$out = array();

		foreach ( $this->sources AS $source ) {
			switch ( $info['type'] ) {
				case 'match one':
					preg_match($info['pattern'], $source, $elements);
					array_shift($elements);
					break;

				case 'split':
					$elements = preg_split($info['pattern'], $source);
					break;

				case 'match all':
					preg_match_all($info['pattern'], $source, $elements);
					array_shift($elements);
					break;

				case 'alter':
					if ( isset($info['functions']) ) {
						foreach ( $info['functions'] AS $callback => $cbInfo ) {
							$args = $cbInfo[0];
							$args[$cbInfo[1]] = $source;
							$source = call_user_func_array($callback, $args);
						}
					}
					break;
			}

			if ( isset($info['slice']) ) {
				$args = $info['slice'];
				array_unshift($args, $elements);
				$elements = call_user_func_array('array_slice', $args);
			}

			if ( !empty($info['first']) ) {
				$elements = $elements[0];
			}

			if ( isset($elements) ) {
				$out = array_merge($out, $elements);
			}
			else {
				array_push($out, $source);
			}
		}

		$this->out = $out;

		$this->save();
		$this->children();
	}

	function save() {
		if ( isset($this->info['save']) ) {
			$this->process->save($this->info['save'], $this->out);
		}
	}

	function children() {
		$class = get_class($this);
		foreach ( $this->info['next'] AS $step ) {
			$children[] = $step = new $class($this->process, $step, $this->out);
			$step->start();
		}
	}
}


