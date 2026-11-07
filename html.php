<?php
// Expects: $error (string|null) and $settings (sanitized, see sanitize_settings())
?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>2srt2ass</title>
		<link href="https://fonts.googleapis.com/css?family=Noto+Sans" rel="stylesheet">
<style>
html { width: 100%; height: 100%; background: url("background.svg") no-repeat center center fixed; background-size: cover; }
body { font-family: 'Noto Sans', sans-serif; margin: 0px; padding: 0px; color: #595959; }
.page { max-width: 800px; margin: 25px auto; background-color: rgb(220, 220, 220); background-color: rgba(220, 220, 220, 0.75); }
h1 { background-color: #595959; color: #999999; padding: 3px 15px; }
h2 { background-color: #909090; color: #CCCCCC; padding: 3px 15px; }
h3 { padding: 3px 15px; }
p { padding: 0; margin: 20px 30px; }
.error { margin: 20px; border: 1px solid #909090; }
.error h2 { margin: 0; }
.error p { font-weight: bold; margin: 10px 15px; color: #451C1C; }

.donate { border: 1px solid grey; padding: 3px 12px; background-color: #EEEEEE; display: inline-block; border-radius: 15px; }
.donate a { color: red; text-decoration: none; }

.main-form { padding: 0 30px; }
.main-form form .form-row { clear: both; margin: 0 0 7px 0; }
.main-form form .form-row .form-label { width: 135px; float: left; }
.main-form form .form-row .form-input input { border: 1px solid #595959; }
.main-form form .form-row .form-input input[type=file] { width: 600px; max-width: 100%; }
.main-form form .form-row .form-input input[type=color] { width: 60px; height: 26px; padding: 0 2px; vertical-align: middle; }
.hint { font-size: 0.85em; }
</style>
	</head>
	<body>
		<div class="page">
			<h1 class="main-title">2srt2ass</h1>
<?php if ($error !== null) : ?>
			<div class="error">
				<h2>Error</h2>
				<p><?= h($error) ?></p>
			</div>
<?php endif; ?>
			<h3>Merge two .srt files, to one .ass file</h3>
			<div class="main-form">
				<form action="." method="post" enctype="multipart/form-data" name="form">
					<input type="hidden" name="send" value="yes">
					<input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) MAX_UPLOAD_BYTES ?>">

					<div class="form-row">
						<span class="form-label"><label for="top">Top subtitle</label> :</span>
						<span class="form-input"><input type="file" id="top" name="top" accept=".srt,text/plain" required></span>
					</div>

					<div class="form-row">
						<span class="form-label"><label for="bot">Bottom subtitle</label> :</span>
						<span class="form-input"><input type="file" id="bot" name="bot" accept=".srt,text/plain" required></span>
					</div>

					<div class="form-row">
						<span class="form-label"><label for="fontsize">Font size</label> :</span>
						<span class="form-input">
							<select id="fontsize" name="fontsize">
<?php foreach (FONT_SIZES as $size) : ?>
								<option value="<?= h($size) ?>"<?= $size === $settings['fontsize'] ? ' selected' : '' ?>><?= h($size) ?></option>
<?php endforeach; ?>
							</select>
						</span>
					</div>

					<div class="form-row">
						<span class="form-label"><label for="fontname">Font name</label> :</span>
						<span class="form-input">
							<select id="fontname" name="fontname">
<?php foreach (FONT_NAMES as $font) : ?>
								<option value="<?= h($font) ?>"<?= $font === $settings['fontname'] ? ' selected' : '' ?>><?= h($font) ?></option>
<?php endforeach; ?>
							</select>
						</span>
					</div>

					<div class="form-row">
						<span class="form-label"><label for="topColor">Top color</label> :</span>
						<span class="form-input"><input type="color" id="topColor" name="topColor" value="<?= h($settings['topColor']) ?>"></span>
					</div>

					<div class="form-row">
						<span class="form-label"><label for="botColor">Bottom color</label> :</span>
						<span class="form-input"><input type="color" id="botColor" name="botColor" value="<?= h($settings['botColor']) ?>"></span>
					</div>

					<div class="form-row">
						<span class="form-label"><label for="forceBottom">Force bottom</label> :</span>
						<span class="form-input"><input type="hidden" name="forceBottom" value="0"><input type="checkbox" id="forceBottom" name="forceBottom" value="1"<?= $settings['forceBottom'] === '1' ? ' checked' : '' ?>><label for="forceBottom"> [EXPERIMENTAL]</label></span>
					</div>

					<p class="hint">Max <?= (int) (MAX_UPLOAD_BYTES / 1024 / 1024) ?> MB per file. UTF-8, UTF-16 and Windows-1252 files are accepted.</p>

					<input type="submit" value="Merge">

				</form>
			</div>
			<div class="about">
				<h2>About</h2>
				<h3>How to use</h3>
				<p>Upload 2 srt files, the script will send you a SSA/ASS subtitle files. This file will have on top the first file's subtitles and on bottom those of the second file.</p>
				<p>If you have any problem, make sure both your files are <a href="https://en.wikipedia.org/wiki/SubRip#SubRip_file_format">formatted in SubRip (srt)</a>.</p>
				<h3>Author</h3>
				<p>2srt2ass developed by Vincent Rémond.</p>
				<p>Feedback or bugs at <a href="mailto:vincent.remond@gmail.com?subject=Feedback%202SRT2ASS">vincent.remond@gmail.com</a>.</p>
				<p>Program under GNU/GPL. <a href="https://github.com/vincentremond/2srt2ass/">Sources here</a></p>
				<p class="donate"><a href="https://www.actionagainsthunger.org/take-action/donate">DONATE</a></p>
				<h3>Changelog</h3>
				<ul>
					<li><b>0.3.0</b> &ndash;  2026-11-07<br>- PHP 8 compatibility<br>- Input validation and security fixes<br>- Automatic encoding conversion to UTF-8<br>- More tolerant SRT parsing<br>- Removed jQuery, jscolor and Google Analytics</li>
					<li><b>0.2.1</b> &ndash;  2017-01-22<br>- Added Helvetica &amp; minor change</li>
					<li><b>0.2.0</b> &ndash;  2016-09-27<br>- Added force subtitles to bottom</li>
					<li><b>0.1.1</b> &ndash;  2015-06-20<br>- Remove leading 0 in timestamps</li>
					<li><b>0.1.0</b> &ndash;  2013-04-07<br>- Improved UI<br>- Customizable font output</li>
					<li><b>0.0.2</b><br>- Fixed : Only Windows' style line returns for output file<br>- Added : Generated file's name based on bottom file<br>- Fixed : only display GIF files when downloading on Firefox</li>
					<li><b>0.0.1</b><br>- Fixed : debug remains<br>- Added : removal of HTML and position tags</li>
					<li><b>0.0.0</b><br>- First release</li>
				</ul>
			</div>
		</div>
	</body>
</html>
