<?php

/**
 * This file is horrible and not Drupal at all. Forgive me, I did not have time to write concise code.
 */

/* Testing for Sassy.
 *  Looks in tests* and compiles any .sass/.scss files
 *  and compares to them to their twin .css files by
 *  filename.
 *
 *  That is, if we have three files:
 *     test.scss
 *     test.sass
 *     test.css
 *
 *  The tester will compile test.scss and test.sass seperately
 *  and compare their outputs both to each other and to test.css
 *
 *  Testing is eased by stripping out all whitespace, which may
 *  introduce bugs of their own.
 */

include 'SassParser.php';

$test_dir = './tests';

$files = find_files($test_dir);

$i = 0;

foreach ($files['by_name'] as $name => $test) {
	if (isset($_GET['name']) && $name != $_GET['name']) {
		continue;
	}
	if (count($test) > 1) {
		$result = test_files($test, $test_dir);

		if ($result === TRUE) {
			print "pass $name<br/>\n";
		}
		else {
			print "FAIL $name<br/>\n";
			foreach ($result as $name => $set) {
				$names = array_keys($set);
				$one = current(array_pop($set));
				$two = current(array_pop($set));
				print "\n\n" . $names[0] . "\n";
				print trim($one);
				print "\n\n";
				print "\n\n" . $names[1] . "\n";
				print trim($two);
			}
			die;
		}
		flush();

		if ($i++ == 100) {
			die;
		}
	}
}

function test_files($files, $dir = '.') {
	$return = array();
	$trimmed = array();
	foreach ($files as $i => $file) {
		$name = explode('.', $file);
		$ext = array_pop($name);

		$fn = 'parse_' . $ext;
		if (function_exists($fn)) {
			try {
				$result = $fn($dir . '/' . $file);
				$trim = preg_replace('/[\s;]+/', '', $result);
				$trim = preg_replace('/\/\*.+?\*\//m', '', $trim);
			} catch (Exception $e) {
				$result = $e->__toString();
				$trim = $result;
			}
			$return[$file] = $result;
			$trimmed[$file] = $trim;
		}
	}

	$failures = array();
	foreach ($trimmed as $file_1 => $val_1) {
		foreach ($trimmed as $file_2 => $val_2) {
			if ($file_1 != $file_2 && $val_1 != $val_2) {
				$names = array($file_1, $file_2);
				sort($names);
				$hash = preg_replace('/[^a-z-]+/', '', implode('-', $names));

				if (!isset($failures[$hash])) {
					$failures[$hash] = array(
						$file_1 => array($val_1, $return[$file_1]),
						$file_2 => array($val_2, $return[$file_2]),
					);
				}
			}
		}
	}
	return count($failures) ? $failures : TRUE;
}


function parse_scss($file) {
	return __parse($file, 'scss');
}
function parse_sass($file) {
	return __parse($file, 'sass');
}
function parse_css($file) {
	return file_get_contents($file);
}

function __parse($file, $syntax, $style = 'nested') {
	$options = array(
		'style' => $style,
		'cache' => FALSE,
		'syntax' => $syntax,
		'debug' => FALSE,
	);
	// Execute the compiler.
	$parser = new SassParser($options);
	return $parser->toCss($file);
}

function find_files($dir) {
	$op = opendir($dir);
	$return = array('by_type' => array(), 'by_name' => array());
	if ($op) {
		while (false !== ($file = readdir($op))) {
			if (substr($file, 0, 1) == '.') {
				continue;
			}
			$name = explode('.', $file);
			$ext = array_pop($name);
			$return['by_type'][$ext] = $file;
			$name = implode('.', $name);
			if (!isset($return['by_name'][$name])) {
				$return['by_name'][$name] = array();
			}
			$return['by_name'][$name][] = $name . '.' . $ext;
		}
	}
	asort($return['by_name']);
	asort($return['by_type']);
	return $return;
}
