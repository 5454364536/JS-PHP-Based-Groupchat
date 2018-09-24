var addName;

function textCommand(content) {
    "use strict";
    return "<table class='text-table'>\
				<tr>\
					<td class='text-username' valign='top'>\
						<span class='text-username-content'>INFO</span>\
					</td>\
					<td class='text-content'>\
						<div disabled class='text-content-textarea'>" + content + "</div>\
					</td>\
				</tr>\
			</table>";
}

function connError(state) {
    "use strict";
    if (!$(".timeout").length && state === true) {
        $("body").prepend("<div class='timeout' style='display:none;opacity:0'><center><div class='timeout-container'><img src='loading.svg' class='timeout-svg' /><div class='timeout-text'>Connection Lost</div></div></center></div>");
        $(".timeout").fadeTo(200, 1);
    } else if (state === false) {
        if ($(".timeout").length) {
            $(".timeout").fadeTo(200, 0, () => {
                $(".timeout").remove();
            });
        }
    }
}

function resetChat() {
    "use strict";
    $(".chat-textarea").fadeTo(1000, 0, () => {
        $(".chat-textarea").html('');
        $.ajax({
            type: "POST",
            url: "index.php",
            data: {
                function: "resetChat",
                resetChat: true
            },
            success: (content) => {
                $(".chat-textarea").html(content);
                $(".chat-textarea").scrollTop($(".chat-textarea").prop("scrollHeight"));
                $(".chat-textarea").fadeTo(400, 1);
            }
        });
    });
}

function tableFade() {
    "use strict";
    $("table.text-table").last().fadeTo(200, 1);
}

function sendChat() {
    "use strict";
    var content = $("input.chat-input").val();
    if (content.substring(0, 1) === ".") {
        if (content.substring(1) === "help") {
            $(".chat-area-spacer").before(
                textCommand('Shoutbox Command List<br>---------------------------------------------------<br><br>\
								.clear - Clear all messages in the current chat.<br><br>\
								.refresh - Refresh all messages in chat.<br><br>\
								Admin Commands<br>---------------------------------------------------<br><br>\
								.reset (id) - Erase all messages, or remove one based on id<br><br>\
								.ip [username/chat id] - Reveal IP of a message / user<br><br>\
								.emoji [list/add/remove] (name) - Manage chat emojis\
							'));
            $("table.text-table").last().fadeTo(300, 1);
        } else if (content.substring(1) === "clear") {
            $(".chat-textarea").html('<div class="chat-area-spacer"></div>');
        } else if (content.substring(1) === "refresh") {
            resetChat();
        } else if (content.substring(1, 6) === "emoji") {
            if (content.substring(7, 10) === "add") {
                if (content.length > 11) {
                    if ($("input.file-upload").length) {
                        $("input.file-upload").closest("table").remove();
                    }
                    addName = content.substring(11);
                    let form = "<form class='emoji-form' action='index.php' enctype='multipart/form-data' method='post' /></form>";
                    let formContent = "\
						<input type='text' name='function' value='addEmoji' style='display:none' hidden />\
						<input type='text' name='name' value='" + addName + "' style='display:none' hidden />\
						<input type='file' name='emojiFile' class='file-upload' />\
						<input type='submit' class='file-submit' value='Submit' />";
                    $(".chat-area-spacer").before(textCommand(form));
                    tableFade();
                    $(".emoji-form").append(formContent);
                } else {
                    $(".chat-area-spacer").before(textCommand('Invalid Name!'));
                    tableFade();
                }
            } else if (content.substring(7, 13) === "remove") {
                let name = content.substring(14);
                $.ajax({
                    type: "POST",
                    url: "index.php",
                    data: {
                        function: "removeEmoji",
                        name: name
                    },
                    success: (content) => {
                        if (content === "403") {
                            $(".chat-area-spacer").before(textCommand('Invalid Permissions!'));
                            tableFade();
                        } else {
                            $(".chat-area-spacer").before(textCommand(content));
                            tableFade();
                        }
                    }
                });
            } else if (content.substring(7) === "list") {
                $.ajax({
                    type: "POST",
                    url: "index.php",
                    data: {
                        function: "listEmoji"
                    },
                    success: (content) => {
                        if (content === "403") {
                            $(".chat-area-spacer").before(textCommand('Invalid Permissions!'));
                            tableFade();
                        } else {
                            $(".chat-area-spacer").before(textCommand(content));
                            tableFade();
                        }
                    }
                });
            } else {
                $(".chat-area-spacer").before(textCommand('Invalid Paramaters! [list/add/remove]'));
                tableFade();
            }
        } else if (content.substring(1, 6) === "reset") {
            var id = content.substring(7);
            $.ajax({
                type: "POST",
                url: "index.php",
                data: {
                    function: "erase",
                    id: id
                },
                success: (content) => {
                    if (content === "success") {
                        resetChat();
                        setTimeout(() => {
                            $(".chat-area-spacer").before(textCommand('Message Deleted!'));
                        }, 1400);
                        tableFade();
                    } else if (content === "403") {
                        $(".chat-area-spacer").before(textCommand('Invalid Permissions!'));
                        tableFade();
                    }
                }
            });
        } else if (content.substring(1, 3) === "ip") {
            if (content.length > 4) {
                content = content.substring(4);
                $.ajax({
                    type: "POST",
                    url: "index.php",
                    data: {
                        function: "ip",
                        id: content
                    },
                    success: (content) => {
                        if (content !== "403") {
                            if (content !== "false") {
                                $(".chat-area-spacer").before(textCommand('IP Returned: ' + content));
                                tableFade();
                            } else {
                                $(".chat-area-spacer").before(textCommand('Invalid Identifier'));
                                tableFade();
                            }
                        } else {
                            $(".chat-area-spacer").before(textCommand('Invalid Permissions!'));
                            tableFade();
                        }
                    }
                });
            } else {
                $(".chat-area-spacer").before(textCommand('Please enter a valid username/id'));
                tableFade();
            }
        } else {
            $(".chat-area-spacer").before(textCommand('Invalid command! View available commands with .help'));
            tableFade();
        }
        $(".chat-input").val('');
        $(".chat-textarea").scrollTop($(".chat-textarea").prop("scrollHeight"));
    } else {
        if (content.length) {
            $.ajax({
                type: "POST",
                url: "index.php",
                data: {
                    function: "sendChat",
                    content: content
                },
                success: () => {
                    $("input.chat-input").val('');
                    $(".chat-textarea").scrollTop($(".chat-textarea").prop("scrollHeight"));
                }
            });
        }
    }
}

function emojiHover(name, state) {
    "use strict";
    if (state === true) {
        $(".emoji-popup").html(name);
        $(".emoji-popup").fadeTo(50, 1, () => {

        });

    } else if (state === false) {
        $(".emoji-popup").fadeTo(50, 0, () => {
            $("emoji-popup").html("");
        });
    }
}

function makeHover() {
	$(".emoji-box-item").hover(
		(e) => {
			let name = e.target.getAttribute("data-name");
			emojiHover(name, true);
		},
		(e) => {
			let name = e.target.getAttribute("data-name");
			emojiHover(name, false);
		}
	);
	$(".emoji-box-item").click((e) => {
		let name = e.target.getAttribute("data-name");
		$("input.chat-input").val(
			$("input.chat-input").val() + ":" + name + ":"
		);
		let a = $(".emoji-box");
		a.fadeTo(100, 0, () => {
			a.css({
				display: "none"
			});
		});
	});
}
$(document).ready(() => {
    "use strict";
    makeHover();
    $("body").click((e) => {
        if (!e.target.classList == "emoji-box" || !$(e.target).parents(".emoji-box").length) {} else {
            emojiHover("0", false);
        }
    });
	$(document).on('mousemove', (e) => {

		$('.emoji-popup').css({
			left: e.pageX,
			top: e.pageY
		});
	});
    $("button.chat-emoji-holder").click(() => {
        let a = $(".emoji-box");
        if (a.css("display") === "block") {
            a.fadeTo(100, 0, () => {
                a.css({
                    display: "none"
                });
            });
        } else if (a.css("display") === "none") {
            a.css({
                display: "block"
            });
            a.fadeTo(100, 1);
        }
    });
    $("input.chat-input").keyup((e) => {
        if (e.which === 13 || e.keyCode === 13) {
            sendChat();
        }
    });
    $("button.chat-submit").click(() => {
        sendChat();
    });
    $(".chat-textarea").scrollTop($(".chat-textarea").prop("scrollHeight"));
    $(".chat-emoji-holder").click(() => {

    });
	$(".chat-text-holder").click(()=>{
		$(".chat-input").focus();
	});
});
