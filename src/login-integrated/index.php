<?php
require("[login functions]");
if (loggedIn()) {
	$userInfo = userInfo();
} else {
	denyAccess();
}
$conn = new mysqli("[db creds]");
$chat = $conn->query("SELECT * FROM `chat_log` ORDER BY `id` DESC LIMIT 25");
$textContent = [];
$emojiState = false;
$status = null;
$_POST['function']   =   filter_var($_POST['function'], FILTER_SANITIZE_STRING);
$_POST['content']    =   filter_var($_POST['content'], FILTER_SANITIZE_STRING);
$_POST['textID']     =   filter_var($_POST['textID'], FILTER_SANITIZE_STRING);
$_POST['resetChat']  =   filter_var($_POST['resetChat'], FILTER_SANITIZE_STRING);
$_POST['name']       =   filter_var($_POST['name'], FILTER_SANITIZE_STRING);
$_POST['id']         =   filter_var($_POST['id'], FILTER_SANITIZE_STRING);
while ($row = $chat->fetch_assoc()) {
	$chatData = (object) [
		'id'        =>   $row['id'],
		'username'  =>   $row['username'],
		'content'   =>   $row['content']
	];
	array_push($textContent, $chatData);
}
$textContent = array_reverse($textContent);
$emojiTable = emojiTable();
$emojiName = emojiArray();
$contentData = contentArray();

for ($i=0;$i<count($contentData);$i++) {
	for ($x=0;$x<count($emojiName);$x++) {
		if (strpos($contentData[$i], $emojiName[$x]) !== false) {
			$img = emojiHTML(($emojiTable[$x]->file),($emojiTable[$x]->name));
			$contentData[$i] = str_replace(":" . $emojiName[$x] . ":", $img, $contentData[$i]);
			$textContent[$i]->content = $contentData[$i];
		}
	}
}
if ($_POST['function'] == "sendChat") { sendChat(); }        // Add new chat to DB
if ($_POST['function'] == "refreshChat") { refreshChat(); }  // Refresh user chat
if ($_POST['function'] == "resetChat") { goto chat; }        // Reload the chat
if ($_POST['function'] == "erase") { eraseChat(); }          // Clear all chat logs
if ($_POST['function'] == "ip") { revealIP(); }              // Return user IP per msg
if ($_POST['function'] == "addEmoji") { addEmoji(); }        // Upload new emoji
if ($_POST['function'] == "removeEmoji") { removeEmoji(); }  // Deletes emoji
if ($_POST['function'] == "listEmoji") { listEmoji(); }      // List all emojis

function revealIP() {
	global $conn, $userInfo;
	$identifier = $_POST['id'];
	if (isRole('administrator')) {
		if (is_numeric($identifier)) {
			$ip = $conn->query("SELECT * FROM `chat_log` WHERE `id`='$identifier'");
			$ip = $ip->fetch_assoc();
			die($ip['ip']);
		} else {
			$ip = $conn->query("SELECT * FROM `chat_log` WHERE `username`='$identifier' ORDER BY `id` DESC LIMIT 1");
			$ip = $ip->fetch_assoc();
			die($ip['ip']);
		}
	} else {
	die(403);
	}
}

function addEmoji() {
	if (isRole('administrator') == true) {
		global $conn, $userInfo, $emojiState, $status;
		$emojiName = $_POST['name'];
		$result = $conn->query("SELECT * FROM `chat_emoji` WHERE `name`='$emojiName'");
		if ($result->num_rows < 1) {
			$file = $_FILES["emojiFile"];
			$dir = "emoji/";
			$fileName = substr(md5(uniqid(mt_rand(), true)) , 0, 10) . substr(basename($file["name"]), strripos(basename($file["name"]), '.'));
			$targetFile = $dir . $fileName;
			$state = true;
			$dataTypes = array(
				'image/jpeg',
				'image/jpg',
				'image/gif',
				'image/png'
			);
			f:
			if (file_exists($targetFile)) {
				$fileName = substr(md5(uniqid(mt_rand(), true)) , 0, 10) . substr(basename($file["name"]), strripos(basename($file["name"]), '.'));
				goto f;
			}
			if ($file['size'] > 4194304) {
				$status = "Upload failed, file is too big!";
				$state = false;
				goto end;
			}
			if ($file['size'] < 10) {
				$status = "No file selected!";
				$state = false;
				goto end;
			}
			if (!in_array($file['type'], $dataTypes) && !empty($file['type'])) {
				$status = "Upload failed, invalid file type!";
				$state = false;
				goto end;
			}
			if ($state = true) {
				$conn->query("INSERT INTO `chat_emoji` (`name`,`file`) VALUES ('$emojiName','$fileName')");
				move_uploaded_file($file['tmp_name'], $targetFile);
				$status =  "Emoji uploaded! (" . $emojiName . ")";
			}
		} else {
			$status =  "Upload Failed, emoji already exists!";
		}
		end:
		$status;
		$emojiState = true;
	} else {
		die(403);
	}
}

function removeEmoji() {
	if (isRole('administrator')) {
		global $conn;
		$name = $_POST['name'];
		$erow = $conn->query("SELECT * FROM `chat_emoji` WHERE `name`='$name'");
		if ($erow->num_rows > 0) {
			$erow = $erow->fetch_assoc();
			$conn->query("DELETE FROM `chat_emoji` WHERE `name`='$name'");
			$file = "emoji/" . $erow['file'];
			unlink($file);
			die("Emoji Deleted!");
		} else {
			die("Invalid Emoji!");
		}
	} else {
		die(403);
	}
	die();
}

function emojiTable() {
	global $conn;
	$emojiContent = [];
	$returnvar = null;
	$result = $conn->query("SELECT * FROM `chat_emoji`");
	while ($row = $result->fetch_assoc()) {
		$emojiData = (object) [
			'id'    =>   $row['id'],
			'name'  =>   $row['name'],
			'file'  =>   $row['file']
		];
		array_push($emojiContent, $emojiData);
	}
	return $emojiContent;
}

function emojiArray() {
	$emoji = emojiTable();
	$data = array();
	for ($i=0;$i<count($emoji);$i++) {
		$data[$i] = $emoji[$i]->name;
	}
	return $data;
}

function contentArray() {
	global $textContent;
	$data = array();
	for ($i=0;$i<count($textContent);$i++) {
		$data[$i] = $textContent[$i]->content;
	}
	return $data;
}

function listEmoji() {
	global $conn;
	$emojiContent = emojiTable();
	for ($i=0;$i<count($emojiContent);$i++) {
		$s = $i + 1;
		$file = "emoji/" . $emojiContent[$i]->file;
		$returnvar = $returnvar . $s . ". <img src='$file' height='24' width='24' class='emoji-img' /> :" . $emojiContent[$i]->name . ":<br>";
	}
	die($returnvar);
}

function emojiHTML($file, $name) {
	return "
	<div class='emoji-box-item'>
		<img src='emoji/$file' height='24' width='24' class='emoji-box-img' data-name='$name'>
	</div>";
}

function sendChat() {
	global $conn, $userInfo;
	$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	if (isset($_POST['content']) && strlen($_POST['content'])>0) {
		$username = $userInfo['username'];
		$content = $_POST['content'];
		$result = $conn->query("SELECT * FROM `chat_log` ORDER BY `id` DESC LIMIT 1");
		$result = $result->fetch_assoc();
		if ($result['content'] != $content) {
			$conn->query("INSERT INTO `chat_log` (`username`,`content`,`ip`) VALUES ('$username', '$content','$ip')");
		}
	}
	die();
}

function refreshChat() {
	global $conn, $textContent, $userInfo, $emojiName, $emojiTable, $contentData;
	echo $id;
	$id = $_POST['textID'];
	if ($id < $textContent[count($textContent) - 1]->id) {
		$id = $id + 1;
		$result = $conn->query("SELECT * FROM `chat_log` WHERE `id`='$id'");
		$result = $result->fetch_assoc();
		for ($i=0;$i<count($emojiName);$i++) {
			if (strpos($result['content'], $emojiName[$i]) !== false) {
				$img = emojiHTML(($emojiTable[$i]->file), ($emojiTable[$i]->name));
				$result['content'] = str_replace(":" . $emojiName[$i] . ":", $img, $result['content']);
			}
		}
		?>
		<table class="text-table" style="opacity:0">
			<?php if ($result['username'] == $userInfo['username']) { ?>
				<tr>
					<td class="text-content-self">
						<div class="text-content-textarea-self" <?php if (isRole('administrator')) {?>data-id="<?php echo $result['id'] . '"'; } echo ">" . $result['content'] ?></div>
					</td>
				</tr>
			<?php } else { ?>
				<tr>
					<td class="text-username" valign="top">
						<span class='text-username-content'><?php echo $result['username'] ?></span>
					</td>
					<td class="text-content">
						<div class="text-content-textarea" <?php if (isRole('administrator')) {?>data-id="<?php echo $result['id'] . '"'; } echo ">" . $result['content'] ?></div>
					</td>
				</tr>
			<?php } ?>
		</table>
<?php } else {
		echo "0";
	}
	die();
}

function eraseChat() {
	global $conn;
	if (isRole('administrator')) {
		$id = $_POST['id'];
		if ($id != null || $id != "") {
			$conn->query("DELETE FROM `chat_log` WHERE `id`='$id'");
			echo "success";
			die();
		} else {
			$conn->query("DELETE FROM `chat_log`");
			echo "success";
			die();
		}
	} else {
		echo 403;
		die();
	}
}

?>
<html>
	<head>
        <title>Chat Box</title>
        <meta name="description" content="A fancy chat box!">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="author" content="A-K">
		<link rel='preload' href='loading.svg' as='image'>
		<style>
			.preloader {
				position: absolute;
				width: 100%;
				height: 100%;
				overflow: hidden;
				background: linear-gradient(135deg, #3023ae 0%,#c86dd7 100%);;
				z-index: 5;
				margin-top: -8px;
				margin-left: -8px;
			}
			.preloader svg {
				position: absolute;
				top: 50%;
				left: 50%;
				transform: translateX(-50%) translateY(-50%);
			}
		</style>
        <link rel="stylesheet" type="text/css" href="main.css">
        <link href="https://fonts.googleapis.com/css?family=Raleway" rel="stylesheet">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/all.css" integrity="sha384-hWVjflwFxL6sNzntih27bfxkr27PmbbK/iSvJ+a4+0owXq79v+lsFkW54bOGbiDQ" crossorigin="anonymous">
		<script src="jquery-3.3.1.js"></script>
		<script src="main.js"></script>
		<script>
			$(document).ready(()=>{
				function refreshChat(textID) {
					"use strict";
					if (navigator.onLine === false) {
						connError(true);
					} else if (navigator.onLine === true) {
						connError(false);
						$.ajax({
							type: "POST",
							url: "index.php",
							data: { function: "refreshChat", textID: textID },
							success: (content)=>{
								if (content !== "0" && content !== null) {
									$(".chat-area-spacer").before(content);
									a = a + 1;
									tableFade();
									makeHover();
								}
							}
						});
					}
				}
				setTimeout(()=>{
					$("div.preloader").fadeTo(500, 0, ()=>{
						$("div.preloader").remove();
					})
				},1500);
				var a = <?php echo $textContent[(count($textContent)-1)]->id ?>;
				setInterval(()=>{refreshChat(a)},1000);
			});
		</script>
	</head>
	<body>
		<div class="preloader">
			<svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 44 44" stroke="#fff">
				<g fill="none" fill-rule="evenodd" stroke-width="2">
					<circle cx="22" cy="22" r="16.3968">
						<animate attributeName="r" begin="0s" dur="1.8s" values="1; 20" calcMode="spline" keyTimes="0; 1" keySplines="0.165, 0.84, 0.44, 1" repeatCount="indefinite"/>
						<animate attributeName="stroke-opacity" begin="0s" dur="1.8s" values="1; 0" calcMode="spline" keyTimes="0; 1" keySplines="0.3, 0.61, 0.355, 1" repeatCount="indefinite"/>
					</circle>
					<circle cx="22" cy="22" r="19.9112">
						<animate attributeName="r" begin="-0.9s" dur="1.8s" values="1; 20" calcMode="spline" keyTimes="0; 1" keySplines="0.165, 0.84, 0.44, 1" repeatCount="indefinite"/>
						<animate attributeName="stroke-opacity" begin="-0.9s" dur="1.8s" values="1; 0" calcMode="spline" keyTimes="0; 1" keySplines="0.3, 0.61, 0.355, 1" repeatCount="indefinite"/>
					</circle>
				</g>
			</svg>
		</div>
		<div class="emoji-popup" style="opacity:0"></div>
		<div class="chat-container">
			<div class="chat-header">
				Shoutbox
			</div>
			<div class="chat-textarea">
				<?php chat: ?>
				<?php for ($i=0;$i<count($textContent);$i++) {?>
					<table class="text-table" style="opacity:1">
						<?php if ($textContent[$i]->username == $userInfo['username']) { ?>
							<tr>
								<td class="text-content-self">
									<div class="text-content-textarea-self" <?php if (isRole('administrator')) {?>data-id="<?php echo $textContent[$i]->id . '"'; } echo ">" . $textContent[$i]->content ?></div>
								</td>
							</tr>
						<?php } else { ?>
							<tr>
								<td class="text-username" valign="top">
									<span class='text-username-content'><?php echo $textContent[$i]->username ?></span>
								</td>
								<td class="text-content">
									<div class="text-content-textarea" <?php if (isRole('administrator')) {?>data-id="<?php echo $textContent[$i]->id . '"'; } echo ">" . $textContent[$i]->content ?></div>
								</td>
							</tr>
						<?php } ?>
					</table>
				<?php } 
				if ($emojiState == true) { ?>
					<table class="text-table" style="opacity:1">
						<tr>
							<td class="text-username" valign="top">
								<span class='text-username-content'>INFO</span>
							</td>
							<td class="text-content">
								<div class="text-content-textarea"><?php echo $status; ?></div>
							</td>
						</tr>
					</table>
				<?php } ?>
				<div class="chat-area-spacer"></div>
				<?php if ($_POST['function'] == "resetChat") { die(); } ?>
			</div>
			<div class="chat-submit-area">  
				<div class="chat-text-holder">
					<div class="emoji-box" style="opacity: 0; display: none;">
						<div class="emoji-box-holder">
							<?php
								$emojiContent = emojiTable();
								for ($i=0;$i<count($emojiContent);$i++) { ?>
									<div class="emoji-box-item">
										<img src='<?php echo "emoji/" . $emojiContent[$i]->file ?>' height='24' width='24' class='emoji-box-img' data-name="<?php echo $emojiContent[$i]->name ?>">
									</div>
							<?php } ?>
						</div>
					</div>
					<button class="chat-emoji-holder"><i class="far fa-smile"></i></button>
					<input type="text" placeholder="Message" class="chat-input" maxlength="256">
				</div>
				<button class="chat-submit">Submit</button>
			</div>
		</div>
	</body>
</html>
				
				
				
				
