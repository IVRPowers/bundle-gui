<?php
//Check if user is "logged in"
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

?>

<h2>VoiceXML Editor</h2>

<?php

$WEBDIR = trim(shell_exec('grep "^DocumentRoot" /etc/httpd/conf/httpd.conf | cut -d\'"\' -f2'));
$dir = isset($_GET['dir']) ? realpath("$WEBDIR/vxml/".$_GET['dir']) : realpath("$WEBDIR/vxml");
$formurl = "";
$formurlRenameDir = "";
if ($dir != "$WEBDIR/vxml") {
	$formurl = "&dir=".$_GET['dir'];
	$formurlRenameDir = "&dir=".dirname($_GET['dir']);
}

if (isset($_POST['oldname']) && isset($_POST['newname'])) {
	
	//print_r("OLD: ".$_POST['oldname']."<br>");
	//print_r("NEW: ".dirname($_POST['oldname'])."/".$_POST['newname']."<br>");
	exec("mv ".$_POST['oldname']." ".dirname($_POST['oldname'])."/".$_POST['newname']);
	
}

if (isset($_FILES['uploadedfiles'])) {
	
	//print_r($_FILES['uploadedfiles']);
	$i = 0;
	foreach ($_FILES['uploadedfiles']['name'] as $filename) {
		
		exec("mv ".$_FILES['uploadedfiles']['tmp_name'][$i++]." $dir/$filename"); 
		exec("chown asterisk. $dir/$filename");
		
	}
	
}

if (isset($_GET['delete'])) {
	
	exec("rm -rf $dir/".$_GET['delete']);
	
}

if (isset($_POST['newfoldername'])) {
	
	exec("mkdir -p $dir/".$_POST['newfoldername']);
	exec("chown -R asterisk. $dir/".$_POST['newfoldername']);
	
}

if (isset($_POST['newfilename'])) {
	
	exec("echo '' > $dir/".$_POST['newfilename']);
	exec("chown asterisk. $dir/".$_POST['newfilename']);
	
}

if (isset($_POST['editedFile'])) {
	
	file_put_contents("/tmp/tmpEditedFile",ltrim($_POST['code']));
	exec("mv /tmp/tmpEditedFile ".$_POST['editedFile']);
	exec("chown asterisk. ".$_POST['editedFile']);
	
}

//if (isset($_POST['getfile']) || isset($_POST['editedFile'])) {
if (isset($_POST['getfile'])) {
	$exten = substr((isset($_POST['getfile']) ? $_POST['getfile'] : $_POST['editedFile']), strpos((isset($_POST['getfile']) ? $_POST['getfile'] : $_POST['editedFile']),".") + 1);
	switch($exten) {
		case "sh":
			$mode = "shell";
			break;
		case "vxml":
			$mode = "xml";
			break;
		case "js":
			$mode = "javascript";
			break;
		case "php":
			$mode = "application/x-httpd-php";
			break;
		default:
			$mode = $exten;
			break;
	}
}

?>

<?php //if (!isset($_POST['getfile']) && !isset($_POST['editedFile'])) {?>

<script type="text/javascript">

function showRename() {
	$("#rename").toggle();
}

</script>
<?php if (!isset($_POST['getfile']) && !isset($_POST['soundfile'])) {?>
<?php echo load_view(dirname(__FILE__) . '/views/rnav.vxmleditor.php');?>
     
<script src="modules/vxml/assets/sorttable.js"></script>
<br>
<?php if ($dir != "$WEBDIR/vxml") {?>
<span style="font-weight: bold; color: gray;">Directory: </span><span><?php echo str_replace("/"," / ",str_replace("$WEBDIR/vxml/","",$dir)) ?></span><img style="width: 16px; height: 16px; margin-left: 5px; margin-bottom: 6px; cursor: pointer;" onclick="showRename()" onmouseover='' src="modules/vxml/images/edit.png">
<form action="config.php?display=vxmleditor<?php echo $formurlRenameDir?>" style="display: none" method="post" id="rename">
	<br>
	<input type="hidden" name="oldname" value="<?php echo $dir?>">
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Insert new name: <input type="text" name="newname" value="">&nbsp;<button type="button" onclick="rename()">Rename</button>&nbsp;<span style="color: red" id="errRename"></span>
</form>
<br><br>
<?php }?>
<div id="foldercontent">
	<table class="sortable" style="width: 500px">
      	<thead>
        	<tr>
        		<td></td>
          		<th>Filename</th>
          		<th>Type</th>
          		<th>Size <small>(bytes)</small></th>
          		<th>Date Modified</th>
        	</tr>
     	</thead>
     	<tbody>
     		<?php
	        // Opens directory
	        //$dir = isset($_GET['dir']) ? realpath("$WEBDIR/vxml/".$_GET['dir']) : realpath("$WEBDIR/vxml"); 
	        $myDirectory=opendir($dir);
	        //$formurl = "";
	        if ($dir != "$WEBDIR/vxml") {
	        	//$formurl = "&dir=".$_GET['dir'];
	        	$requestedDir = realpath("$WEBDIR/vxml/".$_GET['dir']);
	          	$requestedDir = str_replace(realpath("$WEBDIR/vxml"),"",$requestedDir);
	          	$requestedDir = $requestedDir."/..";
	        	print("
     				<tr class='$class'>
     				<td></td>
     				<td><a href='config.php?display=vxmleditor&dir=$requestedDir'><b>. .</b></a></td>
     				<td><a href='config.php?display=vxmleditor&dir=$requestedDir'><b>&lt;Directory&gt;</b></a></td>
     				<td><a href='config.php?display=vxmleditor&dir=$requestedDir'></a></td>
     				<td sorttable_customkey='$timekey'><a href='config.php?display=vxmleditor&dir=$requestedDir'></a></td>
     			</tr>");
	        }
	        // Gets each entry
	        while($entryName=readdir($myDirectory)) {
	        	$dirArray[]=$entryName;
	        }
	        
	        // Finds extensions of files
	        function findexts ($filename) {
	          	$filename=strtolower($filename);
	          	$exts=split("[/\\.]", $filename);
	          	$n=count($exts)-1;
	          	$exts=$exts[$n];
	          	return $exts;
	        }
	        
	        // Closes directory
	        closedir($myDirectory);
	        
	        // Counts elements in array
	        $indexCount=count($dirArray);
	        
	        // Sorts files
	        function sortDir ($a, $b) {
	        	
	        	$WEBDIR = trim(shell_exec('grep "^DocumentRoot" /etc/httpd/conf/httpd.conf | cut -d\'"\' -f2'));
	        	$dir = isset($_GET['dir']) ? realpath("$WEBDIR/vxml/".$_GET['dir']) : realpath("$WEBDIR/vxml");
	        	
	        	if (is_dir("$dir/$a") && is_dir("$dir/$b")) {
	        		
	        		return strcasecmp($a,$b);
	        		
	        	} elseif (is_file("$dir/$a") && is_dir("$dir/$b")) {
	        		
	        		return 1;
	        	
	        	} elseif (is_file("$dir/$b") && is_dir("$dir/$a")) {
	        		
	        		return -1;
	        		
	        	} elseif (is_file("$dir/$a") && is_file("$dir/$b")) {

	        		return strcasecmp($a,$b);
	        		
	        	}
	        	
	        }
	        
	        
	        usort($dirArray, "sortDir");
	        
	        // Loops through the array of files
	        for($index=0; $index < $indexCount; $index++) {
	        
	          // Allows ./?hidden to show hidden files
	          	if($_SERVER['QUERY_STRING']=="hidden") {
	          		$hide="";
	          		$ahref="./";
	          		$atext="Hide";
	          	} else { 
	          		$hide=".";
	          		$ahref="./?hidden";
	          		$atext="Show";
	          	}
	          	if(substr("$dirArray[$index]", 0, 1) != $hide) {
	          
	          	// Gets File Names
	          	$name=$dirArray[$index];
	          	$namehref=$dirArray[$index];
	          
	          	// Gets Extensions 
	          	$extn=findexts($dirArray[$index]); 
	          
	          	// Gets file size 
	          	$size=number_format(filesize("$dir/".$dirArray[$index]));
	          
	          	// Gets Date Modified Data
	          	$modtime=date("M j Y g:i A", filemtime("$dir/".$dirArray[$index]));
	          	$timekey=date("YmdHis", filemtime("$dir/".$dirArray[$index]));
	          
	          	// Pretifies File Types, add more to suit your needs.
	          	$edit = 0;
	          	$play = 0;
	          	//$video = 0;
	          	switch ($extn){
	            	case "png": $extn="PNG Image"; break;
	            	case "jpg": $extn="JPEG Image"; break;
	            	case "svg": $extn="SVG Image"; break;
	            	case "gif": $extn="GIF Image"; break;
	            	case "ico": $extn="Windows Icon"; break;
	            
		            case "txt": $extn="Text File"; $edit = 1; break;
		            case "log": $extn="Log File"; $edit = 1; break;
	    	        case "html": $extn="HTML File"; $edit = 1; break;
	        	    case "php": $extn="PHP Script"; $edit = 1; break;
	            	case "js": $extn="Javascript"; $edit = 1; break;
	            	case "css": $extn="Stylesheet"; $edit = 1; break;
	            	case "conf": $extn="Configuration File"; $edit = 1; break;
	            	case "xml": $extn="XML File"; $edit = 1; break;
	            	case "vxml": $extn="VXML File"; $edit = 1; break;
	            	case "sh": $extn="Script File"; $edit = 1; break;
	            	case "license": $extn="License File"; $edit = 1; break;
	            	case "readme": $extn="Readme File"; $edit = 1; break;
	            	case "changelog": $extn="Change Log"; $edit = 1; break;
	            	
	            	case "pdf": $extn="PDF Document"; break;
	            
	            	case "zip": $extn="ZIP Archive"; break;
	            	case "bak": $extn="Backup File"; break;
	            	
	            	case "wav": $extn="WAV File"; $play = 1; break; 
	            	case "gsm": $extn="GSM File"; $play = 1; break;
	            	case "mp3": $extn="MP3 File"; $play = 1; break;
	            	
	            	//case "mp4": $extn="MP4 File"; $video = 1; break;	            	
	            	//case "h263": $extn="H263 File"; $video = 1; break;
	            
	            	default: $extn=strtoupper($extn)." File"; break;
	          	}
	          
	          	// Separates directories
	          	if(is_dir("$dir/".$dirArray[$index])) {
	            	$extn="&lt;Directory&gt;"; 
	            	$size="&lt;Directory&gt;"; 
	            	$class="dir";
	          	} else {
	            	$class="file";
	          	}
	          	/*
	          	// Cleans up . and .. directories 
	          	if($name==".") {
					$name=". (Current Directory)"; 
					$extn="&lt;System Dir&gt;";
				}
	          	if($name=="..") {
					$name=".. (Parent Directory)"; 
					$extn="&lt;System Dir&gt;";
				}*/
	          
	          	if (realpath("$WEBDIR/vxml/".$_GET['dir']) == realpath("$WEBDIR/vxml")) {
	          		$currentDir = "";
	          	} else {
	          		$currentDir = realpath("$WEBDIR/vxml/".$_GET['dir']);
	          		$currentDir = str_replace(realpath("$WEBDIR/vxml"),"",$currentDir);
	          		$currentDir = $currentDir."/";
	          	}
				if(is_dir("$dir/".$dirArray[$index])) {
		          	print("
		          		<tr class='$class'>
     					<td><img height='16' width='16' onmouseover='' style='cursor: pointer;' onclick='confirmation(\"$name\",\"directory\")' src='modules/vxml/images/trash.png'></td>
		            	<td><a href='config.php?display=vxmleditor&dir=$currentDir$name'><b>$name</b></a></td>
		            	<td><a href='config.php?display=vxmleditor&dir=$currentDir$name'><b>$extn</b></a></td>
		            	<td><a href='config.php?display=vxmleditor&dir=$currentDir$name'><b></b></a></td>
		            	<td sorttable_customkey='$timekey'><a href='config.php?display=vxmleditor&dir=$currentDir$name'><b>$modtime</b></a></td>
		          	</tr>");
				} else {
					if ($edit) {
						print("
							<tr class='$class'>
     						<td><img height='16' width='16' onmouseover='' style='cursor: pointer;' onclick='confirmation(\"$name\",\"file\")' src='modules/vxml/images/trash.png'></td>
							<td class='edit' edit='$dir/$name' onmouseover='' style='cursor: pointer;'>&nbsp;$name</td>
							<td class='edit' edit='$dir/$name' onmouseover='' style='cursor: pointer;'>&nbsp;$extn</td>
							<td class='edit' edit='$dir/$name' onmouseover='' style='cursor: pointer;'>&nbsp;$size</td>
							<td sorttable_customkey='$timekey' class='edit' edit='$dir/$name' onmouseover='' style='cursor: pointer;'>&nbsp;$modtime</td>
						</tr>");
					} elseif ($play) {
						print("
							<tr class='$class'>
							<td><img height='16' width='16' onmouseover='' style='cursor: pointer;' onclick='confirmation(\"$name\",\"file\")' src='modules/vxml/images/trash.png'></td>
							<td class='play' play='$dir/$name' onmouseover='' style='cursor: pointer;'>&nbsp;$name</td>
							<td class='play' play='$dir/$name' onmouseover='' style='cursor: pointer;'>&nbsp;$extn</td>
							<td class='play' play='$dir/$name' onmouseover='' style='cursor: pointer;'>&nbsp;$size</td>
							<td sorttable_customkey='$timekey' class='play' play='$dir/$name' onmouseover='' style='cursor: pointer;'>&nbsp;$modtime</td>
						</tr>");				
					} else {
						print("
							<tr class='$class'>
     						<td><img height='16' width='16' onmouseover='' style='cursor: pointer;' onclick='confirmation(\"$name\",\"file\")' src='modules/vxml/images/trash.png'></td>
							<td>&nbsp;$name</td>
							<td>&nbsp;$extn</td>
							<td>&nbsp;$size</td>
							<td sorttable_customkey='$timekey'>&nbsp;$modtime</td>
						</tr>");
					}
				}
	         }
	      }
	      ?>
     	</tbody>
     </table>
     <br>
     <form id="newfile" style="display: none" action="config.php?display=vxmleditor<?php echo $formurl?>" method="post">
	     <table style="width: 500px">
	     	<tr><td style="width: 180px;">Name for the new file: </td><td style="width: 150px;"><input type="text" name="newfilename"></td><td><input type="submit" style="float: left" value="create"></td></tr>
	     	<tr><td colspan="3"><hr></td></tr>
	     </table>
     </form>
     <form id="newfolder" style="display: none" action="config.php?display=vxmleditor<?php echo $formurl?>" method="post">
	     <table style="width: 500px">
	     	<tr><td style="width: 180px;">Name for the new folder: </td><td style="width: 150px;"><input type="text" name="newfoldername"></td><td><input type="submit" style="float: left" value="create"></td></tr>
	     	<tr><td colspan="3"><hr></td></tr>
	     </table>
     </form>
     <br>
     <form id="upload" action="config.php?display=vxmleditor<?php echo $formurl?>" method="post" enctype="multipart/form-data">
     	<table>
     		<tr><td colspan="2">Upload files to current directory</td></tr>
     		<tr><td>Select files to upload: </td><td><input type="file" name="uploadedfiles[]" id="uploadedfiles" multiple></td></tr>
     		<tr><td colspan="2"><input type="submit" value="Upload"></td></tr>
     	</table>     
     </form>
</div>

<script type="text/javascript">

	function deploy(id) {

		if (id == "newfile") $("#newfolder").hide();
		else if (id == "newfolder") $("#newfile").hide(); 
		$("#" + id).show();

	}

	function confirmation(item,type) {

		var confirmation = confirm("Do you really want to delete this " + type + "?");
		if (confirmation) {
			window.location.href = "config.php?display=vxmleditor<?php echo $formurl?>&delete=" + item;
		} else {
			//Do nothing
		}

	}
	      
</script>
<?php }?>


	

<br><br>

<?php //if (isset($_POST['getfile']) || isset($_POST['editedFile'])) {?>
<?php if (isset($_POST['getfile']) && !isset($_POST['soundfile'])) {?>

<script type="text/javascript">
	$(function() {
		var link = document.createElement( "link" );
		link.href = "modules/vxml/assets/codemirror/lib/codemirror.css";
		link.type = "text/css";
		link.rel = "stylesheet";
		link.media = "screen,print";
		document.getElementsByTagName( "head" )[0].appendChild( link );
		var link2 = document.createElement( "link" );
		link2.href = "modules/vxml/assets/codemirror/addon/hint/show-hint.css";
		link2.type = "text/css";
		link2.rel = "stylesheet";
		link2.media = "screen,print";
		document.getElementsByTagName( "head" )[0].appendChild( link2 );
	});
</script>
<script src="modules/vxml/assets/codemirror/lib/codemirror.js"></script>
<script src="modules/vxml/assets/codemirror/mode/xml/xml.js"></script>
<script src="modules/vxml/assets/codemirror/addon/hint/show-hint.js"></script>
<?php /*<script src="modules/vxml/assets/codemirror/addon/hint/anyword-hint.js"></script>*/?>
<script src="modules/vxml/assets/codemirror/addon/selection/active-line.js"></script>
<script src="modules/vxml/assets/codemirror/addon/edit/closebrackets.js"></script>
<script src="modules/vxml/assets/codemirror/addon/edit/closetag.js"></script>
<script src="modules/vxml/assets/codemirror/addon/fold/xml-fold.js"></script>
<script src="modules/vxml/assets/codemirror/addon/edit/matchtags.js"></script>
<?php if ($mode == "application/x-httpd-php") {?>
<script src="modules/vxml/assets/codemirror/mode/php/php.js"></script>
<script src="modules/vxml/assets/codemirror/mode/css/css.js"></script>
<script src="modules/vxml/assets/codemirror/addon/edit/matchbrackets.js"></script>
<script src="modules/vxml/assets/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script src="modules/vxml/assets/codemirror/mode/javascript/javascript.js"></script>
<script src="modules/vxml/assets/codemirror/mode/clike/clike.js"></script>
<?php } elseif ($mode == "javascript") {?>
<script src="modules/vxml/assets/codemirror/mode/javascript/javascript.js"></script>
<?php }?>

<div id="editor">
	<?php $fileToEdit = str_replace("/"," / ",str_replace("$WEBDIR/vxml/","",$_POST['getfile']))?>
	<span style="font-weight: bold; color: gray;">Editing file: </span><span><?php echo $fileToEdit ?></span><img style="width: 16px; height: 16px; margin-left: 5px; margin-bottom: 6px; cursor: pointer;" onclick="showRename()" onmouseover='' src="modules/vxml/images/edit.png">
	<form action="config.php?display=vxmleditor<?php echo $formurl?>" style="display: none" method="post" id="rename">
		<br>
		<input type="hidden" name="oldname" value="<?php echo $_POST['getfile']?>">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Insert new name: <input type="text" name="newname" value="">&nbsp;<button type="button" onclick="rename()">Rename</button>&nbsp;<span style="color: red" id="errRename"></span>
	</form>
	<br><br>
	<form action="config.php?display=vxmleditor<?php echo $formurl?>" method="post" id="applyChanges" onsubmit="disableBeforeUnload();">
		<input type="hidden" name="editedFile" value="<?php echo isset($_POST['getfile']) ? $_POST['getfile'] : $_POST['editedFile']?>">
		<textarea id="code" name="code"><?php 
				$f = fopen((isset($_POST['getfile']) ? $_POST['getfile'] : $_POST['editedFile']),'r');
				while(!feof($f)) {
					$line = fgets($f);
					if (strpos($line,"<?xml") !== false) $line = ltrim($line);
					echo "$line";
				}
				fclose($f);
			?></textarea>
		<br>
		<input class="myButtons" type="submit" value="Save"> <button class="myButtons" type="button" onclick="discard()">Discard</button>
	</form>	
	
</div>

<style type="text/css">
			
	.myButtons {
		width: 70px;	
	}
				
    .CodeMirror { 
    	border: 1px solid #eee;
      	margin-left: 0; 
    }
    
    .CodeMirror-sizer {
    	margin-left: 0;
    	width: 80%;
    }
</style>

<form action="config.php?display=vxmleditor<?php echo $formurl?>" method="post" id="discard">
</form>

<script type="text/javascript">
			
$('#body-content').on('change keyup keydown', 'input, textarea, select', function (e) {
    $(this).addClass('changed-input');
});

$(window).on('beforeunload', function () {
    if ($('.changed-input').length) {
        return 'You haven\'t saved your changes.';
    }
});

/*
function enableBeforeUnload() {
	console.log("FUNCION enableBeforeUnload");
	window.onbeforeunload = function (e) {
		return "UNSAVED CHANGES!";
	};
}*/

function disableBeforeUnload() {
	window.onbeforeunload = null;
}

CodeMirror.commands.autocomplete = function(cm) {
	cm.showHint({hint: CodeMirror.hint.anyword});
}

function discard() {

	disableBeforeUnload();
	var form = document.getElementById("discard");
	form.submit();

}

var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
	
    mode: "<?php echo $mode?>",
    styleActiveLine: true,
    lineNumbers: true,
    lineWrapping: true,
    autoCloseBrackets: true,
    autoCloseTags: true,
    matchTags: {bothTags: true},
    indentUnit: 4,
    indentWithTabs: true,
    enterMode: "keep",
    tabMode: "shift",
    extraKeys: {
      "Ctrl-Space": "autocomplete"
    }
});

editor.on("update", function() {
	$(".CodeMirror-gutter.CodeMirror-linenumbers").css("width","29px");
	$(".CodeMirror-gutter-wrapper").css("left","-30px");
	$(".CodeMirror-gutter-wrapper").css("width","30px");
	$(".CodeMirror-linenumber.CodeMirror-gutter-elt").css("width","21px");
	$(".CodeMirror-linenumber.CodeMirror-gutter-elt").css("left","0px");
});

$(".CodeMirror-hscrollbar").css("left","0");
$(".CodeMirror-sizer").css("margin-left","30px");
$(".CodeMirror-gutter.CodeMirror-linenumbers").css("width","29px");
$(".CodeMirror-gutter-wrapper").css("left","-30px");
$(".CodeMirror-gutter-wrapper").css("width","30px");
$(".CodeMirror-linenumber.CodeMirror-gutter-elt").css("width","21px");
$(".CodeMirror-linenumber.CodeMirror-gutter-elt").css("left","0px");




$(document).ready(
		setTimeout(function(){
			editor.on("update",function() {
				console.log("FUNCION enableBeforeUnload");
				window.onbeforeunload = function (e) {
					return "UNSAVED CHANGES!";
				};
			});
		}, 3000)
);

//editor.setSize("100%","400px");

</script>
<?php }?>

<?php if (isset($_POST['soundfile']) && !isset($_POST['getfile'])) {?>

<script type="text/javascript">
	$(function() {
		var link = document.createElement( "link" );
		link.href = "modules/vxml/assets/soundmanager/demo/bar-ui/css/bar-ui.css";
		link.type = "text/css";
		link.rel = "stylesheet";
		link.media = "screen,print";
		document.getElementsByTagName( "head" )[0].appendChild( link );		
	});
</script>
<script src="modules/vxml/assets/soundmanager/script/soundmanager2.js"></script>
<script src="modules/vxml/assets/soundmanager/demo/bar-ui/script/bar-ui.js"></script>

<span style="font-weight: bold; color: gray;">Playing file: </span><span><?php echo str_replace("/"," / ",str_replace("$WEBDIR/vxml/","",$_POST['soundfile']))?></span><img style="width: 16px; height: 16px; margin-left: 5px; margin-bottom: 6px; cursor: pointer;" onclick="showRename()" onmouseover='' src="modules/vxml/images/edit.png">
<form action="config.php?display=vxmleditor<?php echo $formurl?>" style="display: none" method="post" id="rename">
	<br>
	<input type="hidden" name="oldname" value="<?php echo $_POST['soundfile']?>">
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Insert new name: <input type="text" name="newname" value="">&nbsp;<button type="button" onclick="rename()">Rename</button>&nbsp;<span style="color: red" id="errRename"></span>
</form>
<br><br>
<div class="sm2-bar-ui">

 <div class="bd sm2-main-controls">

  <div class="sm2-inline-texture"></div>
  <div class="sm2-inline-gradient"></div>

  <div class="sm2-inline-element sm2-button-element">
   <div class="sm2-button-bd">
    <a href="#play" class="sm2-inline-button play-pause">Play / pause</a>
   </div>
  </div>

  <div class="sm2-inline-element sm2-inline-status">

   <div class="sm2-playlist">
    <div class="sm2-playlist-target">
     <!-- playlist <ul> + <li> markup will be injected here -->
     <!-- if you want default / non-JS content, you can put that here. -->
     <noscript><p>JavaScript is required.</p></noscript>
    </div>
   </div>

   <div class="sm2-progress">
    <div class="sm2-row">
    <div class="sm2-inline-time">0:00</div>
     <div class="sm2-progress-bd">
      <div class="sm2-progress-track">
       <div class="sm2-progress-bar"></div>
       <div class="sm2-progress-ball"><div class="icon-overlay"></div></div>
      </div>
     </div>
     <div class="sm2-inline-duration">0:00</div>
    </div>
   </div>

  </div>

  <div class="sm2-inline-element sm2-button-element sm2-volume">
   <div class="sm2-button-bd">
    <span class="sm2-inline-button sm2-volume-control volume-shade"></span>
    <a href="#volume" class="sm2-inline-button sm2-volume-control">volume</a>
   </div>
  </div>

 </div>

 <div class="bd sm2-playlist-drawer sm2-element">

  <div class="sm2-inline-texture">
   <div class="sm2-box-shadow"></div>
  </div>

  <!-- playlist content is mirrored here -->

  <div class="sm2-playlist-wrapper">
    <ul class="sm2-playlist-bd">
       <li><a href="<?php echo str_replace("$WEBDIR","",$_POST['soundfile'])?>"><?php echo basename($_POST['soundfile'])?></a></li>
    </ul>
  </div>

 </div>

</div>

<style>
.sm2-bar-ui {
	font-size: 16px;
}
.sm2-bar-ui .sm2-main-controls,
.sm2-bar-ui .sm2-playlist-drawer {
	background-color: #2288cc;
}
.sm2-bar-ui .sm2-inline-texture {
	background: transparent;
}
</style>

<br><br>
<form action="config.php?display=vxmleditor<?php echo $formurl?>" method="post" id="close">
	<input type="submit" value="Close player">
</form>

<script>
soundManager.setup({
	url: 'modules/vxml/assets/soundmanager/swf',
 	flashVersion: 9,
 	debugMode: false,
  	onready: function() {
  		var mySound = soundManager.createSound({
  	    	id: 'playfile', 
  	      	url: '<?php echo $_POST['soundfile']?>'
  	    });
  	}
});
</script>

<?php }?>



<form action="config.php?display=vxmleditor<?php echo $formurl?>" method="post" id="edition">
	<input type="hidden" name="getfile" value="">
</form>

<form action="config.php?display=vxmleditor<?php echo $formurl?>" method="post" id="play">
	<input type="hidden" name="soundfile" value="">
</form>

<script type="text/javascript">

		function send(file) {

			var form = document.getElementById("edition");
			form.elements['getfile'].value = file;
			form.submit();	
			
		}

				
		$(document).ready(function() {
			
			$(document).on("click",".edit", function() {

				send($(this).attr("edit"));		

			});

		});

		$(document).ready(function() {

			$(document).on("click",".play", function() {

				var form = document.getElementById("play");
				form.elements['soundfile'].value = $(this).attr("play");
				form.submit();

			});

		});

		function rename() {
			$("#errRename").html("");
			var form = document.getElementById("rename");
			if (form.elements['newname'].value == "" || form.elements['newname'].value == null) $("#errRename").html("You must specify a new name.");
			else {
				<?php if (isset($_POST['getfile']) && !isset($_POST['soundfile'])) {?>
				disableBeforeUnload();
				<?php }?>
				form.submit();
			}  
		}

</script>






