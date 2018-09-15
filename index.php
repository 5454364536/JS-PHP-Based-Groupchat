<?php
$conn = new mysqli("localhost", "root", "3WwkXdNLn7uNps4k", "main");
$chat = $conn->query("SELECT * FROM `chat_log` ORDER BY `id` DESC LIMIT 25");
$textContent = [];
$_COOKIE['username'] =   filter_var($_COOKIE['username'], FILTER_SANITIZE_STRING);
$_POST['content']    =   filter_var($_POST['content'], FILTER_SANITIZE_STRING);
$_POST['textID']     =   filter_var($_POST['textID'], FILTER_SANITIZE_STRING);
$_POST['resetChat']  =   filter_var($_POST['resetChat'], FILTER_SANITIZE_STRING);
while ($row = $chat->fetch_assoc()) {
	$chatData = (object) [
		'id'        =>   $row['id'],
		'username'  =>   $row['username'],
		'content'   =>   $row['content']
	];
	array_push($textContent, $chatData);
}
$textContent = array_reverse($textContent);
if ($_POST['function'] == "sendChat") { sendChat(); }
if ($_POST['function'] == "refreshChat") { refreshChat(); }
if ($_POST['function'] == "resetChat") { resetChat(); }



// chat send func
function sendChat() {
	global $conn, $_POST, $_COOKIE;
	if (isset($_COOKIE['username']) && !empty($_COOKIE['username']) && isset($_POST['content']) && strlen($_POST['content'])>0) {
		$username = $_COOKIE['username'];
		$content = $_POST['content'];
		$conn->query("INSERT INTO `chat_log` (`username`,`content`) VALUES ('$username', '$content')");
	}
	die();
}

// chat updater func
function refreshChat() {
	global $conn, $textContent, $_POST, $_COOKIE;
	echo $id;
	$id = $_POST['textID'];
	if ($id < $textContent[count($textContent) - 1]->id) {
		$id = $id + 1;
		$result = $conn->query("SELECT * FROM `chat_log` WHERE `id`='$id'");
		$result = $result->fetch_assoc(); ?>
		<table class="text-table">
			<?php if ($result['username'] === $_COOKIE['username']) { ?>
				<tr>
					<td class="text-content-self">
						<div disabled class="text-content-textarea-self"><?php echo $result['content'] ?></div>
					</td>
				</tr>
			<?php } else { ?>
				<tr>
					<td class="text-username" valign="top">
						<span class='text-username-content'><?php echo $result['username'] ?></span>
					</td>
					<td class="text-content">
						<div disabled class="text-content-textarea"><?php echo $result['content'] ?></div>
					</td>
				</tr>
			<?php } ?>
		</table>
<?php } else {
		echo "0";
	}
	die();
}



// reset chat - working
function resetChat() {
	global $conn, $textContent, $_POST, $_COOKIE;
	if (isset($_POST['resetChat']) && !empty($_POST['resetChat'])) {
		for ($i=0;$i < count($textContent);$i++) { ?>
			<table class="text-table">
				<?php if ($textContent[$i]->username === $_COOKIE['username']) { ?>
					<tr>
						<td class="text-content-self">
							<div disabled class="text-content-textarea-self"><?php echo $textContent[$i]->content ?></div>
						</td>
					</tr>
				<?php } else { ?>
					<tr>
						<td class="text-username" valign="top">
							<span class='text-username-content'><?php echo $textContent[$i]->username ?></span>
						</td>
						<td class="text-content">
							<div disabled class="text-content-textarea"><?php echo $textContent[$i]->content ?></div>
						</td>
					</tr>
				<?php } ?>
			</table><?php 
		}
		?><div class="chat-area-spacer"></div><?php
	}
	die();
}


?>






<html><head>
        <title>Chat Box</title>
        <meta name="description" content="A fancy chat box!">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="author" content="A-K">
        <link rel="stylesheet" type="text/css" href="main.css">
        <link href="https://fonts.googleapis.com/css?family=Raleway" rel="stylesheet">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/all.css" integrity="sha384-hWVjflwFxL6sNzntih27bfxkr27PmbbK/iSvJ+a4+0owXq79v+lsFkW54bOGbiDQ" crossorigin="anonymous">
		<script src="jquery-3.3.1.js"></script>
        <script src="main.js"></script>
		<script>
		$(document).ready(
			function() {
				function refreshChat(textID) {
					"use strict";
					var username = getCookie("username");
					if (username !== null) {		
						$.ajax({
							type: "POST",
							url: "index.php",
							data: { function: "refreshChat", textID: textID },
							success: function(content) {
								if (content !== "0" && content !== null) {
									$(".chat-area-spacer").before(content);
									a = a + 1;
								}
							}
						});
					}
				}
				var a = <?php echo $textContent[(count($textContent)-1)]->id ?>;
				if (getCookie("username") == "") {
					newUser();
				} else {
					var username = getCookie('username');
				}
				setInterval( ()=>{ refreshChat(a) }, 2000);
			}
		);
			
		</script>
    </head>
	<body>
		<div class="chat-container">
			<div class="chat-header">
				Shoutbox
			</div>
			<div class="chat-textarea">
			<?php for ($i=0;$i<count($textContent);$i++) {?>
				<table class="text-table">
					<?php if ($textContent[$i]->username == $_COOKIE['username']) { ?>
						<tr>
							<td class="text-content-self">
								<div disabled class="text-content-textarea-self"><?php echo $textContent[$i]->content ?></div>
							</td>
						</tr>
					<?php } else { ?>
						<tr>
							<td class="text-username" valign="top">
								<span class='text-username-content'><?php echo $textContent[$i]->username ?></span>
							</td>
							<td class="text-content">
								<div disabled class="text-content-textarea"><?php echo $textContent[$i]->content ?></div>
							</td>
						</tr>
					<?php } ?>
				</table>
			<?php } ?>
				<div class="chat-area-spacer"></div>
			</div>
			<div class="chat-submit-area">  
				<input type="text" placeholder='Message' class="chat-input" maxlength="256">
				<button class="chat-submit" onclick="sendChat()">Submit</button>
			</div>
		</div>
	</body>
</html>