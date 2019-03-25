#!/usr/bin/php
<?php
// Preset PHP settings
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('UTC');

// Define root directory
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS);

if (!isset($argv)) {
	die('ERROR: Please run this script in command line.');
}

if (!isset($argv[1])) {
	die('ERROR: Please provide coversion mode. (-range or -cidr)');
}

if (!in_array($argv[1], ['-range', '-cidr'])) {
	die('ERROR: Please provide a valid coversion mode. (-range or -cidr)');
}

if (!isset($argv[2])) {
	die('ERROR: Please provide the absolute path to input CSV file.');
}

if (!file_exists($argv[2])) {
	die('ERROR: The input CSV file is not found.');
}

if (!isset($argv[3])) {
	die('ERROR: Please provide the absolute path to output CSV file.');
}

if (!is_writable(dirname($argv[3]))) {
	die('ERROR: The output directory is not writable.');
}

$file = fopen($argv[2], 'r');

if (!$file) {
	die('ERROR: Failed to read the input CSV.');
}

@file_put_contents($argv[3], '');

if ($argv[1] == '-range') {
	while (!feof($file)) {
		$data = fgetcsv($file);

		if (!preg_match('/^[0-9]+$/', $data[0]) || !preg_match('/^[0-9]+$/', $data[1])) {
			continue;
		}

		$from = long2ip($data[0]);
		$to = long2ip($data[1]);

		unset($data[0]);
		unset($data[1]);

		@file_put_contents($argv[3], '"' . $from . '","' . $to . '","' . implode('","', $data) . "\"\n", FILE_APPEND);
	}
} else {
	while (!feof($file)) {
		$data = fgetcsv($file);

		if (!preg_match('/^[0-9]+$/', $data[0]) || !preg_match('/^[0-9]+$/', $data[1])) {
			continue;
		}

		$rows = rangeToCIDR(long2ip($data[0]), long2ip($data[1]));

		unset($data[0]);
		unset($data[1]);

		foreach ($rows as $row) {
			@file_put_contents($argv[3], '"' . implode('","', array_merge([$row], $data)) . "\"\n", FILE_APPEND);
		}
	}
}

fclose($file);

function iMask($s)
{
	return base_convert((pow(2, 32) - pow(2, (32 - $s))), 10, 16);
}

function iMaxBlock($ibase, $tbit)
{
	while ($tbit > 0) {
		$im = hexdec(iMask($tbit - 1));
		$imand = $ibase & $im;
		if ($imand != $ibase) {
			break;
		}
		--$tbit;
	}

	return $tbit;
}

function rangeToCIDR($ipStart, $ipEnd)
{
	$s = explode('.', $ipStart);

	$start = '';
	$dot = '';

	while (list($key, $val) = each($s)) {
		$start = sprintf('%s%s%d', $start, $dot, $val);
		$dot = '.';
	}

	$end = '';
	$dot = '';

	$e = explode('.', $ipEnd);
	while (list($key, $val) = each($e)) {
		$end = sprintf('%s%s%d', $end, $dot, $val);
		$dot = '.';
	}

	$start = ip2long($start);
	$end = ip2long($end);
	$result = [];

	while ($end >= $start) {
		$maxSize = iMaxBlock($start, 32);
		$x = log($end - $start + 1) / log(2);
		$maxDiff = floor(32 - floor($x));

		$ip = long2ip($start);

		if ($maxSize < $maxDiff) {
			$maxSize = $maxDiff;
		}

		array_push($result, "$ip/$maxSize");
		$start += pow(2, (32 - $maxSize));
	}

	return $result;
}
