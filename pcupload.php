<?php

/* Written by Th3-822 */
error_reporting(0);
set_time_limit(0);
ini_set('memory_limit', '1024M');
if (ob_get_level()) ob_end_clean();
ob_implicit_flush(true);
ignore_user_abort(true);

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified:' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');
header('X-Powered-By: Th3-822', false);

$_GET = array_merge($_GET, $_POST);
$T8_UPLOAD = !empty($_GET['do']) && $_GET['do'] == 'upload';
$PHP_SELF = $_SERVER['SCRIPT_NAME'];
define('ROOT_DIR', realpath('./'));
define('CONFIG_DIR', 'configs/');
define('CLASS_DIR', 'classes/');
require_once(CONFIG_DIR . 'setup.php');
$options['default_language'] = basename($options['default_language']);
if (substr($options['download_dir'], -1) != '/') $options['download_dir'] .= '/';
define('DOWNLOAD_DIR', (substr($options['download_dir'], 0, 6) == 'ftp://' ? '' : $options['download_dir']));
define('TEMPLATE_DIR', 'templates/' . $options['template_used'] . '/');
require_once(CLASS_DIR . 'other.php');
login_check();

function dieErr($m, $c = 822) {
	global $T8_UPLOAD, $_save, $_read;
	if (!empty($_save) && is_resource($_save)) {
		flock($_save, LOCK_UN);
		fclose($_save);
	}
	if (!empty($_read) && is_resource($_read)) {
		flock($_read, LOCK_UN);
		fclose($_read);
	}
	if ($T8_UPLOAD) exit('{"jsonrpc" : "2.0", "error" : {"code": ' . (is_numeric($c) ? $c : '"'.addslashes($c).'"') . ', "message": "' . addslashes($m) . '"}, "id" : 7822}');
	else html_error($m);
}
function ini_get_bool($a) { // http://www.php.net/manual/es/function.ini-get.php#93014
	$b = ini_get($a);
	switch (strtolower($b)) {
		case 'on':
		case 'yes':
		case 'true':
			return 'assert.active' !== $a;
		case 'stdout':
		case 'stderr':
			return 'display_errors' === $a;
		default:
			return (bool)(int)$b;
	}
}
function ini_get_in_bytes($str) {
	$val = trim(ini_get($str));
	$lchar = strtolower(substr($val, -1));
	if ($lchar == 'b') $lchar = strtolower(substr($val, -2));
	$val = (int)$val;
	switch ($lchar) {
		default : return $val;
		case 'k': return $val * 1024;
		case 'm': return $val * 1048576;
		case 'g': return $val * 1073741824;
	}
}

$T8Opts = array('phpLimit'=>ini_get_in_bytes('upload_max_filesize'), 'phpPLimit'=>ini_get_in_bytes('post_max_size'));
// Options
$T8Opts['maxFSize'] = 1024 * 1048576; // Max Upload Filesize: 1 GB - Default, overwritten by $options['file_size_limit'] if it's lower. (Values higher than 1.7 GB may fail on many hosts)

// Upload Checks
if (!is_dir(DOWNLOAD_DIR)) dieErr('DOWNLOAD_DIR value isn\'t a directory');
if (!ini_get_bool('file_uploads') || (int)ini_get('max_file_uploads') < 1 || !function_exists('is_uploaded_file')) dieErr('This server doesn\'t support file uploads');
if ($options['file_size_limit'] > 0 && $T8Opts['maxFSize'] > $options['file_size_limit']) $T8Opts['maxFSize'] = $options['file_size_limit'];
if ($T8Opts['phpLimit'] < 1 || ($T8Opts['phpPLimit'] > 0 && $T8Opts['phpPLimit'] < $T8Opts['phpLimit'])) $T8Opts['phpLimit'] = $T8Opts['phpPLimit'] - 2048; // I will save at least 2k for more POST data. - T8
if ($T8Opts['phpLimit'] < 1048576) dieErr('Upload limits too low for upload or upload is disabled');
$T8Opts['chunked'] = ($T8Opts['phpLimit'] < $T8Opts['maxFSize']) ? true : false;

/*// Start *\
//* Written by Th3-822 */
if (!$T8_UPLOAD) {
	if (!is__writable(DOWNLOAD_DIR)) html_error(DOWNLOAD_DIR . lang(305));
	$page_title = 'Th3-822\'s PC-Uploader for Rapidleech v0.1 Beta';
	include(TEMPLATE_DIR . 'header.php'); ?>
<!-- Th3-822's PC Uploader -->
<div style='text-align:center;'>
	<h1 title="T-8's PC Uploader">PC Uploader</h1>
	<script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js'></script> <!-- Th3-822: jQuery >= 1.4.1 for parseJSON funcion -->
	<style type='text/css'>@import url(plupload/js/jquery.plupload.queue/css/jquery.plupload.queue.css);</style>
	<style type='text/css'>.plupload_file_name{text-align: left}</style>
	<script type='text/javascript' src='plupload/js/plupload.js'></script>
	<script type='text/javascript' src='plupload/js/plupload.html5.js'></script>
	<script type='text/javascript' src='plupload/js/plupload.silverlight.js'></script>
	<script type='text/javascript' src='plupload/js/plupload.flash.js'></script>
<?php if ($options['default_language'] != 'en' && file_exists("plupload/js/i18n/{$options['default_language']}.js")) echo "\t<script type='text/javascript' src='plupload/js/i18n/{$options['default_language']}.js'></script><!-- Th3-822 -->\r\n"; ?>
	<script type='text/javascript' src='plupload/js/jquery.plupload.queue/jquery.plupload.queue.js'></script>
	<script type='text/javascript'>/* <![CDATA[ */
	/*Th3-822 was here */
	function T8_checkFileNames(up, files) {
		$.each(files, function() {
			if (!this.name || (new RegExp('[\\x00-\\x1F<>:"/\\x5C\|?\*\\x7F]')).test(this.name) || (new RegExp('^\\s*..\\s*$')).test(this.name)) {
				up.trigger('Error', {
					code : 822,
					message : 'File name error.',
					file : this
				});
				alert('Error: Invalid file name: ' + this.name);
			} else if ((this.name).lastIndexOf('.') === 0 || (new RegExp('\\.(s?php.*|cgi|p[ly]|sh|asp|[dsp]?html?)$', 'i')).test(this.name)) up.trigger('Error', {
				code : plupload.FILE_EXTENSION_ERROR,
				message : plupload.translate('File extension error.'),
				file : this
			});
		});
	}
	function T8_checkUploadError(up, file, res) {
		if (file.status !== plupload.FAILED && res && res.response) {
			var response = $.parseJSON(res.response);
			if (response && response.error) {
				up.trigger('Error', {
					code : response.error.code,
					message : response.error.message,
					file : file
				});
			}
		}
	}
	$(function() {
		var T8Up = $('#T8up_box').pluploadQueue({
			runtimes : 'html5, silverlight, flash', /* T-8: HTML5 works great but Flash was not working when chunking with big files (And it always have to use chunk when its not needed)... I still need to test with silverlight, but i don't wanna install it :D */
			url : '<?php echo basename(__FILE__); ?>?do=upload',
			max_file_size : '<?php echo $T8Opts['maxFSize'] . 'b'; ?>',
<?php if ($T8Opts['chunked']) echo "\t\t\tchunk_size : '{$T8Opts['phpLimit']}b',\r\n"; ?>
			required_features : 'multipart<?php if ($T8Opts['chunked']) echo ',chunks'; ?>',
			multipart: true,
			multiple_queues : true,
			prevent_duplicates: true,
			file_data_name : 'T8_file',
			flash_swf_url : 'plupload/js/plupload.flash.swf',
			silverlight_xap_url : 'plupload/js/plupload.silverlight.xap',
			init : {
				FilesAdded: T8_checkFileNames,
				FileUploaded: T8_checkUploadError,
				ChunkUploaded: T8_checkUploadError
			}
		});
	});
	/* ]]> */</script>
	<div id='T8up_box' style='margin-left: auto; margin-right: auto; width: 500px; height: 350px;color: #000000;'><p style='color: #FF0000;'>Your browser doesn't support the HTML5 uploader and it doesn't have Flash or Silverlight installed.</p><!--T-8 --></div>
	<br /><br /><h6>Written by Th3-822, using plupload.</h6>
</div>
<?php include(TEMPLATE_DIR . 'footer.php');
} else {
	//Uploader

	// Check for common errors
	if (!is__writable(DOWNLOAD_DIR)) dieErr('Save dir not writeable or full', 305);
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES)) dieErr('Empty POST or exceded post_max_size limit'); 
	if (empty($_FILES['T8_file']) || empty($_FILES['T8_file']['tmp_name'])) dieErr('Nothing uploaded');
	if ($_FILES['T8_file']['error'] !== UPLOAD_ERR_OK) {
		switch ($_FILES['T8_file']['error']) {
			case UPLOAD_ERR_INI_SIZE: dieErr('The uploaded file/chunk exceeds the upload_max_filesize directive.', 811);
			case UPLOAD_ERR_FORM_SIZE: dieErr('The uploaded file/chunk exceeds the MAX_FILE_SIZE directive.', 812);
			case UPLOAD_ERR_PARTIAL: dieErr('The uploaded file/chunk was only partially uploaded', 813);
			case UPLOAD_ERR_NO_FILE: dieErr('No file/chunk was uploaded', 814);
			case UPLOAD_ERR_NO_TMP_DIR: dieErr('Temporary folder missing or not writeable', 816);
			case UPLOAD_ERR_CANT_WRITE: dieErr('Failed to write file/chunk to temp-dir', 817);
			case UPLOAD_ERR_EXTENSION: dieErr('File/chunk upload stopped by extension', 818);
			default: dieErr('Unknown upload error', 810);
		}
	}
	// Important check - Do not remove or edit.
	if (!is_uploaded_file($_FILES['T8_file']['tmp_name'])) dieErr('Security error on temp file', 819);

	// Get chunks/filename - Clean and test filename
	$badchars = array_merge(range(chr(0), chr(31)), str_split("<>:\"/|?*\x5C\x7F"));
	$chunk = isset($_GET['chunk']) ? intval($_GET['chunk']) : 0;
	$chunks = isset($_GET['chunks']) ? intval($_GET['chunks']) : 0;
	$fname = str_replace($badchars, '', basename(trim(!empty($_GET['name']) ? $_GET['name'] : (!empty($_FILES['T8_file']['name']) && $_FILES['T8_file']['name'] != 'blob' ? $_FILES['T8_file']['name'] : ''))));
	if (!empty($options['rename_underscore'])) $fname = str_replace(array(' ', '%20'), '_', $fname);
	if (empty($fname)) dieErr('Empty Filename');
	if (preg_match('@\.(s?php.*|cgi|p[ly]|sh|asp|[dsp]?html?)$@i', $fname)) dieErr('File extension error', -601);

	// Directory & file exists checks
	$savedir = realpath(DOWNLOAD_DIR);
	if (!$savedir) $savedir = DOWNLOAD_DIR;
	if ($chunks < 2 && empty($options['bw_save']) && file_exists($savedir . DIRECTORY_SEPARATOR . $fname)) {
		$ext = strrpos($fname, '.');
		$fname_a = substr($fname, 0, $ext);
		$fname_b = substr($fname, $ext);
		$count = 1;
		while (file_exists($savedir . DIRECTORY_SEPARATOR . $fname_a . '_' . $count . $fname_b)) $count++;
		$fname = $fname_a . '_' . $count . $fname_b;
		unset($fname_a, $fname_b, $count, $ext);
	} elseif (file_exists($savedir . DIRECTORY_SEPARATOR . $fname)) dieErr('File already exists on server', -602);
	$savefile = $savedir . DIRECTORY_SEPARATOR . $fname;

	// Filesize checks
	$size = $_FILES['T8_file']['size'];
	if ($size > $T8Opts['maxFSize']) dieErr('File/chunk size uploaded is greater than uploader\'s max filesize limit.', -600);
	elseif ($chunks > 1 && $size > $T8Opts['phpLimit']) dieErr('Chunk size is greater than uploader\'s chunk limit.', -600);
	elseif ($chunks > 2 && $chunk < ($chunks - 1) && ($size * ($chunks - 1)) > $T8Opts['maxFSize']) dieErr('Calculated file-size is greater than uploader\'s max filesize.', -600); // With altered uploader values the file may be 1 chunk bigger... But i will delete the file and show error if the complete file it's greater than limit. (Check next 'if')
	elseif ($chunks > 1 && $chunk == ($chunks - 1)) {
		$size = getSize("$savefile.part") + $size;
		if ($size > $T8Opts['maxFSize']) {
			@unlink("$savefile.part");
			dieErr('Full file-size is greater than uploader\'s max filesize, file deleted', -600);
		}
	}

	if ($chunks < 2 && function_exists('move_uploaded_file')) {
		// If it's not chunked and can use move_uploaded_file, use it.
		if (!@move_uploaded_file($_FILES['T8_file']['tmp_name'], $savefile)) dieErr('Cannot move file to savedir');
	} else {
		// Copy contents from tmp_file to savefile
		// Opening savefile for write/add data and tmp_file for read
		$_save = fopen("$savefile.part", ($chunk == 0 ? 'wb' : 'ab'));
		if (!$_save) dieErr('Cannot open savefile');
		$_read = fopen($_FILES['T8_file']['tmp_name'], 'rb');
		if (!$_read) dieErr('Cannot read uploaded chunk/file');
		// Lock save file for writting and lock tmp_file for reading.
		flock($_save, LOCK_EX);
		flock($_read, LOCK_SH);
		// Read tmp_file and write it's contents to savefile
		$bsize = 8192;
		while (!feof($_read)) {
			$readed = fread($_read, $bsize);
			if ($readed === false) dieErr('Error while reading tmp_file');
			$written = fwrite($_save, $readed);
			if ($written === false || strlen($readed) != $written) dieErr('Error while writting file');
		}
		unset($bsize, $readed, $written);
		// Remove locks from files
		flock($_save, LOCK_UN);
		flock($_read, LOCK_UN);
		// Close handlers
		fclose($_save);
		fclose($_read);
		// Delete tmp_file
		@unlink($_FILES['T8_file']['tmp_name']);

		// If it's not chunked or it's last chunk, remove .part from filename
		if ($chunks < 2 || $chunk == $chunks - 1) rename("$savefile.part", $savefile);
	}

	// Add file to files.lst
	if ($chunks < 2 || $chunk == $chunks - 1) write_file(CONFIG_DIR . 'files.lst', serialize(array('name' => $savefile, 'size' => bytesToKbOrMbOrGb($size), 'date' => time(), 'link' => '', 'comment' => 'T-8\'s PC-Uploader')) . "\r\n", 0);

	exit('{"jsonrpc" : "2.0", "result" : null, "id" : 7822}');
}

// Written by Th3-822, using plupload.

?>