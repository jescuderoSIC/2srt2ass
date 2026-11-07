# 2srt2ass

Merge two SubRip (`.srt`) subtitle files into a single Advanced SubStation Alpha (`.ass`) file:
the first file is displayed at the top of the screen and the second one at the bottom
(useful for watching a video with subtitles in two languages).

## Requirements

- PHP **7.3 or later** (tested with PHP 8.2)
- PHP extension `mbstring`
- `file_uploads = On` and `upload_max_filesize` / `post_max_size` of at least 2 MB

## Running locally

```sh
php -S localhost:8000
```

Then open <http://localhost:8000/>.

## Deployment

Copy `index.php`, `functions.php`, `html.php` and `background.svg` to any directory served by PHP.
No database or write permissions are needed.

## Features

- Font name, font size and colours for each subtitle track (remembered in cookies)
- Optional "force bottom" mode (both tracks at the bottom)
- Input files in UTF-8 (with or without BOM), UTF-16 (with BOM) or Windows-1252 are converted to UTF-8
- HTML tags and ASS override tags (`{\an8}`, `{\pos(x,y)}`...) are removed from the input

## License

GNU GPL v3 — see [LICENSE](LICENSE). Original author: Vincent Rémond.
