// small chat UI helpers: auto-scroll to bottom and mark active chat
window.addEventListener('load', function(){
	var msgs = document.querySelector('.chat-main .messages');
	if (msgs) { msgs.scrollTop = msgs.scrollHeight; }

	document.querySelectorAll('.chat-list .chat-item').forEach(function(it){
		it.addEventListener('click', function(){
			document.querySelectorAll('.chat-list .chat-item').forEach(function(x){ x.classList.remove('active') });
			it.classList.add('active');
		});
	});
});