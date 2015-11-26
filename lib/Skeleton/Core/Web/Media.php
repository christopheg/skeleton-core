<?php
/**
 * Media detection and serving of media files
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Web;

use Skeleton\Core\Application;

class Media {
	/**
	 * Image extensions
	 *
	 * @var array $filetypes
	 * @access private
	 */
	private static $filetypes = [
		'image' => [
			'gif',
			'jpg',
			'jpeg',
			'png',
			'ico',
		],
		'doc' => [
			'pdf',
		],
		'css' => [
			'css',
		],
		'font' => [
			'woff',
			'woff2',
			'ttf',
			'otf',
			'eot'
		],
		'javascript' => [
			'js',
		],
		'tools' => [
			'html',
			'htm'
		],
	];

	/**
	 * Detect if the request is a request for media
	 *
	 * @param $request array
	 * @access public
	 */
	public static function detect($request_uri) {
		// Don't bother looking up /
		if ($request_uri == '/') {
			return;
		}

		$request = explode('/', trim($request_uri, '/'));

		// Find the filename and extension
		$filename = $request[count($request)-1];
		$extension = substr($filename, strrpos($filename, '.'));

		// If the request does not contain an extension, it's not to be handled by media
		if (strpos($extension, '.') !== 0) {
			return;
		}

		// Remove the . from the extension
		$extension = substr($extension, 1);
		$request_string = implode('/', $request);

		// Detect if it is a request for multiple files
		if (strpos($request_string, '&/') !== false) {
			$files = explode('&/', $request_string);

			$mtime = 0;
			foreach ($files as $file) {
				$file_mtime = self::fetch('mtime', $file, $extension);

				if ($file_mtime === false) {
					self::fail();
				}

				if ($file_mtime > $mtime) {
					$mtime = $file_mtime;
				}
			}

			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
				if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == gmdate('D, d M Y H:i:s', $mtime).' GMT') {
					// Cached version
					self::output($extension, '', $mtime);
				}
			}

			$content = '';
			foreach ($files as $file) {
				$content .= self::fetch('content', $file, $extension) . "\n";
			}

			$content = $content;
			$filename = 'compacted.' . $extension;
		} else {
			$mtime = self::fetch('mtime', $request_string, $extension);

			if ($mtime === false) {
				self::fail();
			}

			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
				if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == gmdate('D, d M Y H:i:s', $mtime).' GMT') {
					// Cached version
					self::output($extension, '', $mtime);
				}
			}

			$content = self::fetch('content', $request_string, $extension);

			// If content is null, we don't handle this extension at all. It would be false if the
			// file could not be found.
			if ($content === null) {
				return;
			}
		}

		// .css files can contain URLs and need to be passed through our URL
		// rewrite method
		if (self::get_mime_type($extension) == 'text/css') {
			// FIXME: this has been disabled for now, but it needs to be fixed.
			// Only CSS files that have been fetched from the application's
			// asset directory should be rewritten.
			//$content = \Skeleton\Core\Util::rewrite_reverse_css($content);
		}

		self::output($extension, $content, $mtime);
	}

	/**
	 * Fail
	 *
	 * @access private
	 */
	private static function fail() {
		if (\Skeleton\Core\Hook::exists('media_not_found')) {
			\Skeleton\Core\Hook::call('media_not_found');
		} else {
			HTTP\Status::code_404('media');
		}
	}

	/**
	 * Fetch the contents and mtime of a file
	 *
	 * @access private
	 * @param string $type
	 * @param string $path
	 * @param string $extension
	 * @return mixed $content Returns a string with the content, false if it
	 *  couldn't be found or null if it shouldn't be handled by us anyway
	 */
	private static function fetch($type, $path, $extension) {
		foreach (self::$filetypes as $filetype => $extensions) {
			if (in_array($extension, $extensions)) {
				$filepaths = [
					\Skeleton\Core\Config::$asset_dir . '/' . $path, // Global asset directory
					Application::get()->media_path . '/' . $filetype . '/' . $path, // Application asset directory
				];

				foreach ($filepaths as $filepath) {
					if (file_exists($filepath)) {
						if ($type == 'mtime') {
							return filemtime($filepath);
						} else {
							return file_get_contents($filepath);
						}
					}
				}

				return false;
			}
		}

		return null;
	}

	/**
	 * Ouput the content of the file and cache it
	 *
	 * @param string $path
	 * @param string $extension
	 * @access private
	 */
	private static function output($extension, $content, $mtime) {
		// Send the Etag before potentially replying with 304
		header('Etag: ' . crc32($mtime) . '-' . sha1($content));

		self::cache($mtime);
		header('Content-Type: ' . self::get_mime_type($extension));

		echo $content;
		exit();
	}

	/**
	 * Get the mime type of a file
	 *
	 * @access private
	 * @param string $filename
	 * @return string $mime_type
	 */
	private static function get_mime_type($extension) {
		$mime_type = '';
		switch ($extension) {
			case 'htm' :
			case 'html': $mime_type = 'text/html';
			             break;

			case 'css' : $mime_type = 'text/css';
			             break;

			case 'ico' : $mime_type = 'image/x-icon';
			             break;

			case 'js'  : $mime_type = 'text/javascript';
			             break;

			case 'png' : $mime_type = 'image/png';
			             break;

			case 'gif' : $mime_type = 'image/gif';
			             break;

			case 'jpg' :
			case 'jpeg': $mime_type = 'image/jpeg';
			             break;
			case 'pdf' : $mime_type = 'application/pdf';
						 break;

			default    : $mime_type = 'application/octet-stream';
		}

		return $mime_type;
	}

	/**
	 * Detect if the file should be resent to the client or if it can use its cache
	 *
	 * @param string filename requested
	 * @access private
	 */
	private static function cache($mtime) {
		$gmt_mtime = gmdate('D, d M Y H:i:s', $mtime).' GMT';

		header('Cache-Control: public');
		header('Pragma: public');

		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $gmt_mtime) {
				header('Expires: ');
				HTTP\Status::code_304();
			}
		}

		header('Last-Modified: '. $gmt_mtime);
		header('Expires: '.gmdate('D, d M Y H:i:s', strtotime('+30 minutes')).' GMT');
	}
}
