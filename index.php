<?php

define('ROOT_FOLDER', dirname(__FILE__).'/');

require_once ROOT_FOLDER.'inc/init.php'; // Require webide init script

// Protect the page
if(!$sessions->is_valid($sessions->get_id()))
{
	include ROOT_FOLDER.'inc/login.html';
	exit;
}

function sort_dir_files($root, $files)
{
	$sortedData = array();
	foreach($files as $file)
	{
		if(is_file($root.'/'.$file))
		{
			array_push($sortedData, $file);
		}
		else
		{
			array_unshift($sortedData, $file);
		}
	}
	return $sortedData;
}

$allowed_extensions = array('', 'php', 'html', 'css', 'js', 'md', 'txt'); // Add your own file extensions to this whitelist, if you want
$root_directory = ROOT_FOLDER; // You can change this to be a folder on your harddrive, or whatever, but right now it's using files from it's root directory.
$files = scandir($root_directory);

// Detect file extension, and if it's not in the whitelist, remove the file from the file browser.
$i = 0;
foreach($files as $file)
{
	$ext = pathinfo($file, PATHINFO_EXTENSION);
	if(!in_array($ext, $allowed_extensions) or $file == '.' or $file == '..')
	{
		unset($files[$i]);
	}
	$i++;
}

// Sort files and folders
$files = sort_dir_files($root_directory, $files);

// AJAX methods
if(isset($_GET['action']))
{
	if($_GET['action'] == 'getfile' && isset($_GET['fileid']))
	{
		if(!array_key_exists($_GET['fileid'], $files))
		{
			echo json_encode(array('type' => 'error', 'message' => 'Invalid file ID. Either something has gone wrong, or you\'ve tampered with the page source.'));
			exit;
		}

		echo json_encode(array('filename' => $files[$_GET['fileid']], 'content' => file_get_contents($root_directory.'/'.$files[$_GET['fileid']])));
		exit;
	}
	elseif($_GET['action'] == 'savefile' && isset($_GET['fileid'], $_POST['content']))
	{
		// Do something to save the file, and return a JSON response, like above
	}
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="">
	<meta name="author" content="">

	<title>Sublime Text</title>

	<!-- Bootstrap core CSS -->
<?php echo $themes->get_styles('Bootstrap 3'); ?>
<link rel="stylesheet" href="./assets/codemirror/lib/codemirror.css">
<link rel="stylesheet" href="./assets/codemirror/theme/monokai.css">
<link rel="stylesheet" href="./assets/codemirror/addon/fold/foldgutter.css">
<link rel="stylesheet" href="./assets/codemirror/addon/dialog/dialog.css">
	<style>
	/* Custom page CSS
	-------------------------------------------------- */
	html, body {
	  height: 100%;
	  background: #272822;
	}

	.CodeMirror {
	  position: relative;
	  z-index: 2;
	  outline: #3c3d38 solid 1px;
	  height: 500px; /* Ancient browser fallback */
	  height: calc(100% - 35px);
	  height: -moz-calc(100% - 35px);
	  height: -webkit-calc(100% - 35px);
	}
	
	.cm-s-monokai.CodeMirror,
	.cm-s-monokai.CodeMirror pre {
	  font-family: Menlo,Monaco,Consolas,monospace!important;
	  font-size: 13px!important;
	}

	.cm-s-monokai .CodeMirror-linenumber {
	  color: #999!important;
	  padding: 0 15px!important;
	}

	.cm-s-monokai .CodeMirror-activeline .CodeMirror-linenumber {
	  background: #373831!important;
	}

	.cm-s-monokai .CodeMirror-activeline-background {
	  background: transparent!important;
	}

	.cm-s-monokai .cm-operator {
	  color: #f92672!important;
	}

	.cm-s-monokai .cm-property,
	.cm-s-monokai .cm-builtin {
	  color: #66d9ef!important;
	}

	.cm-s-monokai .cm-variable-3,
	.cm-s-monokai .cm-qualifier {
	  color: #a6e22e!important;
	}
	
	.cm-s-monokai .cm-variable,
	.cm-s-monokai .cm-variable-2,
	.cm-s-monokai span.cm-bracket {
	  color: #f8f8f2!important;
	}

	.ide-sidebar {
	  position: relative;
	  width: 200px;
	  height: 100%;
	  z-index: 3;
	  float: left;
	  font-size: 12px;
	  background: #e6e6e6;
	  line-height: 1.5;
	}

	.ide-sidebar nav {
	  margin: 0;
	  padding: 10px 15px;
	}

	.ide-sidebar-item {
	  list-style: none;
	}

	.ide-navbar {
	  font-size: 12px;
	  color: #c1c1bf;
	  background: #171714;
	  height: 35px;
	}

	.ide-navitem {
	  position: relative;
	  width: 180px;
	  float: left;
	  margin-left: 12px;
	  margin-top: 7px;
	  height: 28px;
	  padding: 4px 10px;
	  z-index: 1;

	  text-shadow: 1px 1px 0 #333;
	  background: #3d3d3a; /* Ancient browsers */
	  background: -moz-linear-gradient(top, #50504e 0%, #3e3e3c 43%, #3e3e3c 43%, #3c3c39 83%, #30302e 100%); /* FF3.6+ */
	  background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#50504e), color-stop(43%,#3e3e3c), color-stop(43%,#3e3e3c), color-stop(83%,#3c3c39), color-stop(100%,#30302e)); /* Chrome,Safari4+ */
	  background: -webkit-linear-gradient(top, #50504e 0%,#3e3e3c 43%,#3e3e3c 43%,#3c3c39 83%,#30302e 100%); /* Chrome10+,Safari5.1+ */
	  background: -o-linear-gradient(top, #50504e 0%,#3e3e3c 43%,#3e3e3c 43%,#3c3c39 83%,#30302e 100%); /* Opera 11.10+ */
	  background: -ms-linear-gradient(top, #50504e 0%,#3e3e3c 43%,#3e3e3c 43%,#3c3c39 83%,#30302e 100%); /* IE10+ */
	  background: linear-gradient(to bottom, #50504e 0%,#3e3e3c 43%,#3e3e3c 43%,#3c3c39 83%,#30302e 100%); /* W3C */
	  filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#50504e', endColorstr='#30302e',GradientType=0 ); /* IE6-9 */
	  border: 1px solid #666664;
	  border-bottom: 1px solid #3c3d38;
	  border-top-left-radius: 9px;
	  border-top-right-radius: 9px;
	}

	.ide-navitem:hover {
	  cursor: default;
	}

	.ide-navitem:before,
	.ide-navitem:after {
	  position: absolute;
	  z-index: 1;
	  bottom: -1px;
	  width: 6px;
	  height: 6px;
	  content: " ";
	  border: 1px solid #666664;
	}

	.ide-navitem:before {
	  left: -6px;
	  border-bottom-right-radius: 6px;
	  border-width: 0 1px 1px 0;
	  box-shadow: 2px 2px 0;
	  color: #3d3d3a;
	}

	.ide-navitem:after {
	  right: -6px;
	  border-bottom-left-radius: 6px;
	  border-width: 0 0 1px 1px;
	  box-shadow: -2px 2px 0;
	  color: #3d3d3a;
	}

	.ide-navitem.active {
	  z-index: 3;
	  color: #f2f2f2;
	  text-shadow: 1px 1px 0 #111;
	  background: #272822; /* Ancient browsers */
	  background: -moz-linear-gradient(top,  #363731 0%, #282923 35%, #272822 100%); /* FF3.6+ */
	  background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#363731), color-stop(35%,#282923), color-stop(100%,#272822)); /* Chrome,Safari4+ */
	  background: -webkit-linear-gradient(top,  #363731 0%,#282923 35%,#272822 100%); /* Chrome10+,Safari5.1+ */
	  background: -o-linear-gradient(top,  #363731 0%,#282923 35%,#272822 100%); /* Opera 11.10+ */
	  background: -ms-linear-gradient(top,  #363731 0%,#282923 35%,#272822 100%); /* IE10+ */
	  background: linear-gradient(to bottom,  #363731 0%,#282923 35%,#272822 100%); /* W3C */
	  filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#363731', endColorstr='#272822',GradientType=0 ); /* IE6-9 */
	  border-color: #3c3d38;
	  border-bottom-color: #272822;
	}

	.ide-navitem.active:before {
	  border-color: #3c3d38;
	  color: #272822;
	}

	.ide-navitem.active:after {
	  border-color: #3c3d38;
	  color: #272822;
	}

	.ide-alert {
	  position: fixed;
	  top: 25px;
	  width: 50%;
	  left: 25%;
	  z-index: 4;
	}
	</style>
  </head>

  <body>

	<!-- Begin page content -->
	<div class="ide-sidebar">
	  <nav>
<?php

$i = 0;
foreach($files as $file)
{
  if(is_dir($root_directory.'/'.$file))
  {
	echo '<div class="ide-sidebar-item"><a href="#" data-fileid="' . $i . '"><span class="glyphicon glyphicon-chevron-down"></span> ' . $file . '</a></div>';
  }
  else
  {
	echo '<div class="ide-sidebar-item"><a href="#" data-fileid="' . $i . '">' . $file . '</a></div>';
  }
  $i++;
}

?>
	  </nav>
	</div>

	<div class="ide-navbar">
	  <nav></nav>
	</div>

	<textarea id="codemirror"></textarea>

	<!-- Javascript -->
<?php echo $themes->get_scripts('Bootstrap 3'); ?>
<script src="./assets/js/jquery-ui-1.10.4.custom.min.js"></script>
<script src="./assets/codemirror/lib/codemirror.js"></script>
<script src="./assets/codemirror/addon/search/searchcursor.js"></script>
<script src="./assets/codemirror/addon/search/search.js"></script>
<script src="./assets/codemirror/addon/dialog/dialog.js"></script>
<script src="./assets/codemirror/addon/edit/matchbrackets.js"></script>
<script src="./assets/codemirror/addon/edit/closebrackets.js"></script>
<script src="./assets/codemirror/addon/comment/comment.js"></script>
<script src="./assets/codemirror/addon/wrap/hardwrap.js"></script>
<script src="./assets/codemirror/addon/selection/active-line.js"></script>
<script src="./assets/codemirror/mode/javascript/javascript.js"></script>
<script src="./assets/codemirror/mode/clike/clike.js"></script>
<script src="./assets/codemirror/mode/php/php.js"></script>
<script src="./assets/codemirror/mode/css/css.js"></script>
<script src="./assets/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script src="./assets/codemirror/mode/xml/xml.js"></script>
<script src="./assets/codemirror/keymap/sublime.js"></script>
<script>
  var editor = CodeMirror.fromTextArea(document.getElementById('codemirror'), {
	mode: "application/x-httpd-php",
	theme: "monokai",
	keyMap: "sublime",
	lineNumbers: true,
	styleActiveLine: true
  });
</script>
<script>
  var openFiles = {};

  // open a file from filesystem
  function openFile(id) {
	$.get('index.php', {action: 'getfile', fileid: id}).done(function (data) {
	  var response = JSON.parse(data);

	  if(typeof response.message !== 'undefined') {
		// message handling
		ideAlert(response.type, response.message);
	  }

	  if(typeof response.filename !== 'undefined' && typeof response.content !== 'undefined') {
		// focus newly opened tab
		$('.ide-navbar nav .ide-navitem').removeClass('active');
		$('.ide-navbar nav').append($('<div class="ide-navitem active">' + response.filename + '</div>'));
		editor.setValue(response.content);
	  }
	});
  }

  // save the currently open file to the filesystem
  function saveFile(id) {
	$.post('index.php?action=savefile&fileid='+id, {content: openFiles.id.content}).done(function (data) {
	  var response = JSON.parse(data);

	  if(typeof response.message !== 'undefined') {
		// message handling
		ideAlert(response.type, response.message);
	  }
	});
  }

  // save the currently open file to a MySQL database
  function saveCache() {
	
  }

  // purge the newly saved or discarded file from cache
  function purgeCache() {
	
  }

  // fade in an alert box centered at the top of the screen
  function ideAlert(type, message, duration) {
	if(type != 'error' || type != 'warning' || type != 'info' || type != 'success') return false;
	if(type == 'error') type = 'danger';

	duration = typeof duration !== 'undefined' ? duration : 5000;

	$('body').append($('<div class="alert alert-' + type + ' ide-alert">' + message + '</div>').hide().fadeIn().delay(duration).fadeOut());
  }

  // setup before functions
  var typingTimer;
  var doneTypingInterval = 5000;

  //on keyup, start the countdown
  $('#myInput').keyup(function(){
	  clearTimeout(typingTimer);
	  if ($('#myInput').val) {
		  typingTimer = setTimeout(saveCache, doneTypingInterval);
	  }
  });

  // make sidebar resizable
  $('.ide-sidebar').resizable({handles: "e"});

  $('.ide-navbar nav').on('click', '.ide-navitem', function() {
	$('.ide-navbar nav .ide-navitem').removeClass('active');
	$(this).addClass('active');
  });

  $('.ide-sidebar nav .ide-sidebar-item a').on('click', function() {
	openFile($(this).attr('data-fileid'));
  });
</script>
  </body>
</html>
