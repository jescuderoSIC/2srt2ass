<?php

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------

const FONT_NAMES = ['Arial', 'Comic Sans MS', 'Helvetica', 'Verdana'];
const FONT_SIZES = ['12', '14', '16', '18', '20', '22', '24', '26', '28', '80'];

const DEFAULT_SETTINGS = [
	'fontname'    => 'Arial',
	'fontsize'    => '16',
	'topColor'    => '#fffff9',
	'botColor'    => '#f9fff9',
	'forceBottom' => '0',
];

const MAX_UPLOAD_BYTES = 2 * 1024 * 1024; // 2 MB per file
const COOKIE_LIFETIME  = 365 * 24 * 60 * 60; // 1 year

// ---------------------------------------------------------------------------
// Request helpers
// ---------------------------------------------------------------------------

function try_get($search, $key, &$result, $default = null)
{
	if (is_array($search) && array_key_exists($key, $search)) {
		$result = $search[$key];
		return true;
	}
	$result = $default;
	return false;
}

/**
 * Returns a "#rrggbb" colour (lowercase) or null if the value is not valid.
 */
function normalize_color($value)
{
	if (!is_string($value) || !preg_match('/^#?([0-9a-f]{6})$/i', trim($value), $m)) {
		return null;
	}
	return '#' . strtolower($m[1]);
}

/**
 * Validates the user settings against whitelists. Any invalid or missing value
 * is replaced by its default, so the result is always safe to output.
 */
function sanitize_settings($source)
{
	$s = DEFAULT_SETTINGS;

	if (try_get($source, 'fontname', $v) && in_array($v, FONT_NAMES, true)) {
		$s['fontname'] = $v;
	}
	if (try_get($source, 'fontsize', $v) && in_array($v, FONT_SIZES, true)) {
		$s['fontsize'] = $v;
	}
	foreach (['topColor', 'botColor'] as $key) {
		if (try_get($source, $key, $v) && ($c = normalize_color($v)) !== null) {
			$s[$key] = $c;
		}
	}
	if (try_get($source, 'forceBottom', $v)) {
		$s['forceBottom'] = ($v === '1') ? '1' : '0';
	}

	return $s;
}

function is_https()
{
	return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		|| (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
}

function set_settings_cookie($name, $value)
{
	setcookie($name, $value, [
		'expires'  => time() + COOKIE_LIFETIME,
		'path'     => '/',
		'secure'   => is_https(),
		'httponly' => true,
		'samesite' => 'Lax',
	]);
}

function h($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Displays the form (optionally with an error) and stops the script.
 * $settings: sanitized settings used to prefill the form.
 */
function sendHTML($error = null, $settings = null)
{
	if ($settings === null) {
		$settings = sanitize_settings($_COOKIE);
	}
	include __DIR__ . '/html.php';
	die();
}

function upload_error_message($code)
{
	switch ($code) {
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			return 'is too large';
		case UPLOAD_ERR_PARTIAL:
			return 'was only partially uploaded';
		case UPLOAD_ERR_NO_FILE:
			return 'is missing';
		default:
			return 'could not be uploaded (server error ' . (int) $code . ')';
	}
}

/**
 * Validates an uploaded file and returns its content, or calls sendHTML() with
 * an explanatory error.
 */
function read_uploaded_srt($files, $field, $label, $settings)
{
	if (!try_get($files, $field, $file) || !is_array($file) || !isset($file['error'])) {
		sendHTML(ucfirst($label) . ' subtitle file is missing.', $settings);
	}
	if ($file['error'] !== UPLOAD_ERR_OK) {
		sendHTML(ucfirst($label) . ' subtitle file ' . upload_error_message($file['error']) . '.', $settings);
	}
	if ($file['size'] > MAX_UPLOAD_BYTES) {
		sendHTML(ucfirst($label) . ' subtitle file is too large (max ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . ' MB).', $settings);
	}
	if (!is_uploaded_file($file['tmp_name'])) {
		sendHTML('Error while uploading. Try again.', $settings);
	}

	$content = file_get_contents($file['tmp_name']);
	// Null bytes are only acceptable in UTF-16 files (which must start with a BOM)
	if ($content === false || (strpos($content, "\0") !== false && !preg_match('/^(\xFF\xFE|\xFE\xFF)/', $content))) {
		sendHTML(ucfirst($label) . ' subtitle file does not look like a text file.', $settings);
	}

	return $content;
}

/**
 * Builds the output file name from the uploaded (bottom) file name.
 * Returns [ascii_fallback, utf8_name].
 */
function build_output_name($uploadedName)
{
	$name = basename(str_replace('\\', '/', (string) $uploadedName));

	if (preg_match('/\.srt$/i', $name)) {
		// "Movie.en.srt" -> "Movie" (removes optional language code too)
		$name = preg_replace('/(\.[a-zA-Z]{2,3})?\.srt$/i', '', $name);
	} else {
		$name = pathinfo($name, PATHINFO_FILENAME);
	}

	// Remove control characters and characters forbidden in file names
	$name = trim(preg_replace('/[\x00-\x1F\x7F"\\\\\/:*?<>|]+/u', '_', $name));
	if ($name === '' || !mb_check_encoding($name, 'UTF-8')) {
		$name = 'subtitles';
	}

	$ascii = preg_replace('/[^A-Za-z0-9._\- ]+/', '_', $name);

	return [$ascii . '.ass', $name . '.ass'];
}

// ---------------------------------------------------------------------------
// ASS styles
// ---------------------------------------------------------------------------

function getStyles($settings, &$styles, &$keys)
{
	$topColor = $settings['topColor'];
	$botColor = $settings['botColor'];

	$specifics = [
		'Default' => ['PrimaryColour' => getColour('#FFFFFF'), 'SecondaryColour' => getColour('#FFFFFF', null, 1.2), 'Alignment' => '2'],
		'Top'     => ['PrimaryColour' => getColour($topColor), 'SecondaryColour' => getColour($topColor, null, 1.2), 'Alignment' => ($settings['forceBottom'] === '1' ? '2' : '8')],
		'Mid'     => ['PrimaryColour' => getColour('#FFFF00'), 'SecondaryColour' => getColour('#FFFFFF', null, 1.2), 'Alignment' => '5'],
		'Bot'     => ['PrimaryColour' => getColour($botColor), 'SecondaryColour' => getColour($botColor, null, 1.2), 'Alignment' => '2'],
	];

	$defaults = [
		'Fontname'        => $settings['fontname'],
		'Fontsize'        => $settings['fontsize'],
		'PrimaryColour'   => getColour('#FFFFFF'),
		'SecondaryColour' => getColour('#FFFFFF'),
		'OutlineColour'   => getColour('#000000'),
		'BackColour'      => getColour('#000000'),
		'Bold'            => '-1',
		'Italic'          => '0',
		'Underline'       => '0',
		'StrikeOut'       => '0',
		'ScaleX'          => '100',
		'ScaleY'          => '100',
		'Spacing'         => '0',
		'Angle'           => '0',
		'BorderStyle'     => '1',
		'Outline'         => '3',
		'Shadow'          => '0',
		'Alignment'       => '2',
		'MarginL'         => '10',
		'MarginR'         => '10',
		'MarginV'         => '10',
		'Encoding'        => '0',
	];

	$keys = array_keys($defaults);

	$styles = [];
	foreach ($specifics as $key => $values) {
		$tmp = [];
		foreach ($defaults as $dk => $dv) {
			$tmp[$dk] = array_key_exists($dk, $values) ? $values[$dk] : $dv;
		}
		$styles[$key] = $tmp;
	}
}

function getColour($color, $alpha = null, $change = null)
{
	if ($alpha === null) $alpha = 0;
	if ($change === null) $change = 1.0;
	if (!preg_match('/^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $color, $parts)) {
		return '&H00FFFFFF';
	}
	$out = '&H';
	$values = [$alpha];
	for ($i = 3; $i >= 1; $i--) {
		$value = hexdec($parts[$i]);
		$value = intval(min(255, round($value * $change)));
		$values[] = $value;
	}
	foreach ($values as $value) {
		$out .= str_pad(dechex($value), 2, '0', STR_PAD_LEFT);
	}
	return strtoupper($out);
}

// ---------------------------------------------------------------------------
// SRT parsing
// ---------------------------------------------------------------------------

/**
 * Converts the raw file content to UTF-8 without BOM.
 * Handles UTF-8 (with/without BOM), UTF-16 LE/BE with BOM and, as fallback,
 * Windows-1252 / ISO-8859-1.
 */
function toUtf8($text)
{
	if (strncmp($text, "\xEF\xBB\xBF", 3) === 0) {
		return substr($text, 3);
	}
	if (strncmp($text, "\xFF\xFE", 2) === 0) {
		return mb_convert_encoding(substr($text, 2), 'UTF-8', 'UTF-16LE');
	}
	if (strncmp($text, "\xFE\xFF", 2) === 0) {
		return mb_convert_encoding(substr($text, 2), 'UTF-8', 'UTF-16BE');
	}
	if (mb_check_encoding($text, 'UTF-8')) {
		return $text;
	}
	return mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
}

function cleanSRT(&$text)
{
	$text = toUtf8($text);
	$text = strip_tags($text);

	$patterns = [
		'/\r\n?/'       => "\n",  // Windows / old Mac line endings to Unix
		'/[ \t]+\n/'    => "\n",  // trailing spaces
		'/\{\\\\[^}]*\}/' => '',  // ASS override tags ({\pos(..)}, {\an8}...) would break top/bottom placement
		'/\n{3,}/'      => "\n\n", // too many blank lines
	];
	$text = preg_replace(array_keys($patterns), array_values($patterns), $text);

	// Remaining braces would be read as ASS comment blocks and hide the text
	$text = strtr($text, ['{' => '(', '}' => ')']);

	$text = trim($text) . "\n";

	return null;
}

/**
 * Parses an SRT time ("00:01:02,345", "0:01:02.3"...) into centiseconds.
 */
function parseSrtTime($h, $m, $s, $fraction)
{
	$ms = (int) str_pad(substr($fraction, 0, 3), 3, '0');
	return ((int) $h * 3600 + (int) $m * 60 + (int) $s) * 100 + intdiv($ms, 10);
}

/**
 * Formats centiseconds as an ASS timestamp (H:MM:SS.cc).
 */
function getTimeStamp($cs)
{
	$h = intdiv($cs, 360000);
	$m = intdiv($cs % 360000, 6000);
	$s = intdiv($cs % 6000, 100);
	return sprintf('%d:%02d:%02d.%02d', $h, $m, $s, $cs % 100);
}

function parseAndAddSRT($text, &$tree, $type, $settings = null)
{
	$timeRe = '(\d{1,2}):(\d{1,2}):(\d{1,2})(?:[.,](\d{1,3}))?';
	$found = 0;

	foreach (preg_split('/\n[ \t]*\n/', $text) as $block) {
		$lines = explode("\n", trim($block));

		// The timing line is usually the 2nd one (after the index), but accept it first too
		$timeLine = null;
		$t = [];
		foreach ([0, 1] as $idx) {
			if (isset($lines[$idx]) && preg_match('/^\s*' . $timeRe . '\s*-->\s*' . $timeRe . '/', $lines[$idx], $t)) {
				$timeLine = $idx;
				break;
			}
		}
		if ($timeLine === null) {
			continue;
		}

		$textLines = array_slice($lines, $timeLine + 1);
		$content = trim(implode("\n", $textLines));
		if ($content === '') {
			continue;
		}

		$start = parseSrtTime($t[1], $t[2], $t[3], isset($t[4]) ? $t[4] : '0');
		$end   = parseSrtTime($t[5], $t[6], $t[7], isset($t[8]) ? $t[8] : '0');

		$tree[] = [
			'start' => $start,
			'end'   => $end,
			'type'  => $type,
			'text'  => str_replace("\n", '\N', $content),
		];
		$found++;
	}

	if ($found === 0) {
		sendHTML('No subtitles found in SRT file for ' . strtolower($type) . '.', $settings);
	}

	return null;
}

function compare($a, $b)
{
	if ($a['start'] !== $b['start']) {
		return $a['start'] <=> $b['start'];
	}
	return strcmp($a['type'], $b['type']);
}
