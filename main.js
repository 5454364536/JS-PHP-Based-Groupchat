/*jshint multistr: true */
/*jshint esversion: 6 */

function getCookie(cname) {
	"use strict";
	var name = cname + "=";
	var decodedCookie = decodeURIComponent(document.cookie);
	var ca = decodedCookie.split(';');
	for(var i = 0; i <ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) === ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) === 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}
// credit to internet ^^

function resetChat() {
	"use strict";
	$(".chat-textarea").fadeTo(1000, 0, ()=> {
		$(".chat-textarea").html('');
		$.ajax({
			type: "POST",
			url: "index.php",
			data: { function: "resetChat",resetChat: true },
			success: function(content) {
				$(".chat-textarea").html(content);
				$(".chat-textarea").fadeTo(400, 1);
			}
		});
	});
}
function sendChat() {
	"use strict";
	var content = $("input.chat-input").val();
	var username = getCookie("username");
	if (content.substring(0,1) === ".") {
		if (content.substring(1) === "clear") {
			
		} else if (content.substring(1) === "help") {
			// List all commands
		} else if (content.substring(1) === "clear") {
			//Clear alll text
		} else {
			//Invalid command block
		}
	} else {
		if (username !== null && content.length > 0) {
			$.ajax({
				type: "POST",
				url: "index.php",
				data: { function: "sendChat", content: content, username: username},
				success: function(content) {
					$("input.chat-input").val('');
				}
			});
		} else {
			if (username === null) {
				newUser();
			}
		}
	}
}
function newUser() {
	"use strict";
	$("body").prepend("\
		<center>\
			<div class='new-user-container'>\
				<div class='new-user-box'>\
					<div class='new-user-text'><span class='newuser-welcome'>Welcome!</span>Please enter your desired username.</div>\
					<input type='text' class='new-user-input' placeholder='Enter Here'>\
					<button class='new-user-submit' style='opacity: 0' disabled>Submit</button>\
				</div>\
			</div>\
		</center>"
	);
}
$("input.chat-input").keyup( (e) => {
	"use strict";
	if (e.which === 13 || e.keyCode === 13) {
    	sendChat();
	}
});
$("input.new-user-input").keyup(() => {
	"use strict";
	let input = $(".new-user-input").val();
	if (input.length > 2) {
		$("button.new-user-submit").prop("disabled", false).css('opacity', '1');
	}
});

$("button.new-user-submit").click(()=> {
	"use strict";
	let input = $(".new-user-input").val();
	
	if (input.length > 2) {
		document.cookie = "username=" + input + "; expires=Thu, 1 Jan 2020 12:00:00 UTC";
		resetChat();
		$("div.new-user-container").fadeOut(400, ()=> {
			$("div.new-user-container").remove();
		});
	} else {
		$(".new-user-input").append("<div class='error-message'>");
	}
});
