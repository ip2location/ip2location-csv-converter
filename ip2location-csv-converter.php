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
	exit('ERROR: Please run this script in command line.');
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

			case '-hex6':
				$conversionMode = 'hex6';
				break;

			case '-hex4':
				$conversionMode = 'hex4';
				break;

			case '-hex':
				$conversionMode = 'hex';
				break;

			case '-replace':
				$writeMode = 'replace';
				break;

			case '-append':
				$writeMode = 'append';
				break;

			default:
				exit('ERROR: Invalid parameter "' . $param . '".');
		}
	}
}

if (count($argv) < 3) {
	exit('ERORR: Missing parameters.');
}

if (!isset($argv[count($argv) - 2])) {
	exit('ERORR: The input CSV is not provided.');
}

if (!isset($argv[count($argv) - 1])) {
	exit('ERORR: The output directory is not provided.');
}

$input = $argv[count($argv) - 2];
$output = $argv[count($argv) - 1];

if (!file_exists($input)) {
	exit('ERROR: The input CSV file is not found.');
}

if (!is_writable(dirname($output))) {
	exit('ERROR: The output directory is not writable.');
}

$file = fopen($input, 'r');

if (!$file) {
	exit('ERROR: Failed to read the input CSV.');
}

@file_put_contents($output, '');

switch ($conversionMode) {
	case 'hex6':
	case 'hex4':
	case 'hex':
		$temprows = [];
		while (!feof($file)) {
			$data = fgetcsv($file);

			if (!preg_match('/^[0-9]+$/', (string) $data[0]) || !preg_match('/^[0-9]+$/', (string) $data[1])) {
				continue;
			}

			if ($conversionMode == 'hex6') {
				$from = str_pad(bcdechex($data[0]), 32, '0', STR_PAD_LEFT);
			} else if ($conversionMode == 'hex4') {
				$from = str_pad(bcdechex($data[0]), 16, '0', STR_PAD_LEFT);
			} else if (bccomp($data[0], '4294967295') === 1) {
				$from = str_pad(bcdechex($data[0]), 32, '0', STR_PAD_LEFT);
			} else {
				$from = str_pad(dechex($data[0]), 16, '0', STR_PAD_LEFT);
			}

			if ($conversionMode == 'hex6') {
				$to = str_pad(bcdechex($data[1]), 32, '0', STR_PAD_LEFT);
			} else if ($conversionMode == 'hex4') {
				$to = str_pad(bcdechex($data[1]), 16, '0', STR_PAD_LEFT);
			} else if (bccomp($data[1], '4294967295') === 1) {
				$to = str_pad(bcdechex($data[1]), 32, '0', STR_PAD_LEFT);
			} else {
				$to = str_pad(dechex($data[1]), 16, '0', STR_PAD_LEFT);
			}

			if ($writeMode == 'replace') {
				unset($data[0], $data[1]);

				$temprows[] = '"' . $from . '","' . $to . '","' . implode('","', $data) . "\"\n";
			} else {
				$temprows[] = '"' . implode('","', array_merge(array_splice($data, 0, 2), [$from, $to], array_splice($data, 0))) . "\"\n";
			}
			
			if (count($temprows) == 1000) {
				$tempfile = @fopen($output, 'a');
				foreach ($temprows as $temprow) {
					@fwrite($tempfile, $temprow);
				}
				@fclose($tempfile);
				$temprows = [];
			}
		}
		break;

	case 'range':
		$temprows = [];
		while (!feof($file)) {
			$data = fgetcsv($file);

			if (!preg_match('/^[0-9]+$/', (string) $data[0]) || !preg_match('/^[0-9]+$/', (string) $data[1])) {
				continue;
			}

			$from = intergerToIp($data[0]);
			$to = intergerToIp($data[1]);

			if ($writeMode == 'replace') {
				unset($data[0], $data[1]);

				$temprows[] = '"' . $from . '","' . $to . '","' . implode('","', $data) . "\"\n";
			} else {
				$temprows[] = '"' . implode('","', array_merge(array_splice($data, 0, 2), [$from, $to], array_splice($data, 0))) . "\"\n";
			}
			
			if (count($temprows) == 1000) {
				$tempfile = @fopen($output, 'a');
				foreach ($temprows as $temprow) {
					@fwrite($tempfile, $temprow);
				}
				@fclose($tempfile);
				$temprows = [];
			}
		}
		break;

	default:
		$temprows = [];
		while (!feof($file)) {
			$data = fgetcsv($file);

			if (!preg_match('/^[0-9]+$/', (string) $data[0]) || !preg_match('/^[0-9]+$/', (string) $data[1])) {
				continue;
			}

			$ranges = \IPLib\Factory::rangesFromBoundaries(intergerToIp($data[0]), intergerToIp($data[1]));

			$rows = explode(' ', implode(' ', $ranges));

			if ($writeMode == 'replace') {
				unset($data[0], $data[1]);

				foreach ($rows as $row) {
					$temprows[] = '"' . implode('","', array_merge([$row], $data)) . "\"\n";
				}
			} else {
				$prefix = array_splice($data, 0, 2);
				$suffix = array_splice($data, 0);

				foreach ($rows as $row) {
					$temprows[] = '"' . implode('","', array_merge($prefix, [$row], $suffix)) . "\"\n";
				}
			}
			
			if (count($temprows) == 1000) {
				$tempfile = @fopen($output, 'a');
				foreach ($temprows as $temprow) {
					@fwrite($tempfile, $temprow);
				}
				@fclose($tempfile);
				$temprows = [];
			}
		}
}

fclose($file);

function iMask($s)
{
	return base_convert(pow(2, 32) - pow(2, 32 - $s), 10, 16);
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
		$start += pow(2, 32 - $maxSize);
	}

	return $result;
}

function intergerToIp($number)
{
	if ($number > 4294967295) {
		return inet_ntop(str_pad(gmp_export($number), 16, "\0", STR_PAD_LEFT));
	}

	return long2ip($number);
}

function bcdechex($dec)
{
	$hex = '';
	do {
		$last = bcmod($dec, 16);
		$hex = dechex($last) . $hex;
		$dec = bcdiv(bcsub($dec, $last), 16);
	} while ($dec > 0);

	return $hex;
}
