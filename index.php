<?php
require __DIR__ . '/functions.php';

// Display page ?
if (!try_get($_POST, 'send', $send) || $send !== 'yes') {
	sendHTML();
}

// Validate settings (whitelists) and remember them
$settings = sanitize_settings($_POST);
foreach ($settings as $name => $value) {
	set_settings_cookie($name, $value);
}

// Uploaded files
$contentTop = read_uploaded_srt($_FILES, 'top', 'top', $settings);
$contentBot = read_uploaded_srt($_FILES, 'bot', 'bottom', $settings);
list($outputNameAscii, $outputNameUtf8) = build_output_name($_FILES['bot']['name']);

// Process
getStyles($settings, $styles, $stylesKeys);

cleanSRT($contentTop);
cleanSRT($contentBot);

$tree = [];
parseAndAddSRT($contentTop, $tree, 'Top', $settings);
parseAndAddSRT($contentBot, $tree, 'Bot', $settings);

usort($tree, 'compare');

// Everything ok, build the file
$outputData  = "[Script Info]\r\n";
$outputData .= "ScriptType: v4.00+\r\n";
$outputData .= "Collisions: Normal\r\n";
$outputData .= "PlayDepth: 0\r\n";
$outputData .= "Timer: 100,0000\r\n";
$outputData .= "Video Aspect Ratio: 0\r\n";
$outputData .= "WrapStyle: 0\r\n";
$outputData .= "ScaledBorderAndShadow: no\r\n";
$outputData .= "\r\n";
$outputData .= "[V4+ Styles]\r\n";
$outputData .= "Format: Name," . implode(',', $stylesKeys) . "\r\n";
foreach ($styles as $styleName => $styleValues) {
	$outputData .= "Style: " . $styleName . "," . implode(',', $styleValues) . "\r\n";
}
$outputData .= "\r\n";
$outputData .= "[Events]\r\n";
$outputData .= "Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text\r\n";

foreach ($tree as $dialogue) {
	$outputData .= "Dialogue: 0," . getTimeStamp($dialogue['start']) . "," . getTimeStamp($dialogue['end']) . "," . $dialogue['type'] . ",,0000,0000,0000,," . $dialogue['text'] . "\r\n";
}

// Send file
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $outputNameAscii . '"; filename*=UTF-8\'\'' . rawurlencode($outputNameUtf8));
header('Content-Length: ' . strlen($outputData));
header('Expires: 0');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

echo $outputData;
