#!/usr/bin/php
<?php
// Preset PHP settings
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('UTC');

// Define root directory
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS);

require 'vendor/autoload.php';

if (!isset($argv)) {
	die('ERROR: Please run this script in command line.');
}

$conversionMode = 'range';
$writeMode = 'replace';

foreach ($argv as $param) {
	if (substr($param, 0, 1) == '-') {
		switch ($param) {
			case '-range':
				$conversionMode = 'range';
				break;

			case '-cidr':
				$conversionMode = 'cidr';
				break;

			case '-replace':
				$writeMode = 'replace';
				break;

			case '-append':
				$writeMode = 'append';
				break;

			default:
				die('ERROR: Invalid parameter "' . $param . '".');
		}
	}
}

if (count($argv) < 3) {
	die('ERORR: Missing parameters.');
}

if (!isset($argv[count($argv) - 2])) {
	die('ERORR: The input CSV is not provided.');
}

if (!isset($argv[count($argv) - 1])) {
	die('ERORR: The output directory is not provided.');
}

$input = $argv[count($argv) - 2];
$output = $argv[count($argv) - 1];

if (!file_exists($input)) {
	die('ERROR: The input CSV file is not found.');
}

if (!is_writable(dirname($output))) {
	die('ERROR: The output directory is not writable.');
}

$file = fopen($input, 'r');

if (!$file) {
	die('ERROR: Failed to read the input CSV.');
}

@file_put_contents($output, '');

if ($conversionMode == 'range') {
	while (!feof($file)) {
		$data = fgetcsv($file);

		if (!preg_match('/^[0-9]+$/', $data[0]) || !preg_match('/^[0-9]+$/', $data[1])) {
			continue;
		}

		$from = intergerToIp($data[0]);
		$to = intergerToIp($data[1]);

		if ($writeMode == 'replace') {
			unset($data[0]);
			unset($data[1]);

			@file_put_contents($output, '"' . $from . '","' . $to . '","' . implode('","', $data) . "\"\n", FILE_APPEND);
		} else {
			@file_put_contents($output, '"' . implode('","', array_merge(array_splice($data, 0, 2), [$from, $to], array_splice($data, 0))) . "\"\n", FILE_APPEND);
		}
	}
} else {
	while (!feof($file)) {
		$data = fgetcsv($file);

		if (!preg_match('/^[0-9]+$/', $data[0]) || !preg_match('/^[0-9]+$/', $data[1])) {
			continue;
		}

		$ranges = \IPLib\Factory::rangesFromBoundaries(intergerToIp($data[0]), intergerToIp($data[1]));

		$rows = explode(' ', implode(' ' , $ranges));

		if ($writeMode == 'replace') {
			unset($data[0]);
			unset($data[1]);

			foreach ($rows as $row) {
				@file_put_contents($output, '"' . implode('","', array_merge([$row], $data)) . "\"\n", FILE_APPEND);
			}
		} else {
			$prefix = array_splice($data, 0, 2);
			$suffix = array_splice($data, 0);

			foreach ($rows as $row) {
				@file_put_contents($output, '"' . implode('","', array_merge($prefix, [$row], $suffix)) . "\"\n", FILE_APPEND);
			}
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
	$parts = explode('.', $ipStart);

	$start = '';
	$dot = '';

	foreach ($parts as $part) {
		$start = sprintf('%s%s%d', $start, $dot, $part);
		$dot = '.';
	}

	$end = '';
	$dot = '';

	$parts = explode('.', $ipEnd);

	foreach ($parts as $part) {
		$end = sprintf('%s%s%d', $end, $dot, $part);
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


function intergerToIp($number) {
	if ($number > 4294967295) {
		return inet_ntop(str_pad(gmp_export($number), 16, "\0", STR_PAD_LEFT));
	}

	return long2ip($number);
}