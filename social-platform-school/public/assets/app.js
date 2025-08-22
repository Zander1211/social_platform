// Minimal UI helpers: emoji picker, reaction AJAX, file button styling
// Long-press / click behavior for reaction trigger (Like button)
;(() => {
  const holdTime = 400; // ms to trigger picker
  let timer = null;
  document.addEventListener('pointerdown', function(e){
    const btn = e.target.closest && e.target.closest('.reaction-trigger');
    if (!btn) return;
    const pid = btn.getAttribute('data-post-id');
    timer = setTimeout(function(){
      const picker = document.querySelector('.emoji-picker[data-post-id="'+pid+'"]');
      if (picker) picker.style.display = 'block';
    }, holdTime);
  });
  document.addEventListener('pointerup', function(e){
    const btn = e.target.closest && e.target.closest('.reaction-trigger');
    if (timer) { clearTimeout(timer); timer = null; }
    // quick click fallback: send a simple like
    if (btn) {
      const pid = btn.getAttribute('data-post-id');
      const picker = document.querySelector('.emoji-picker[data-post-id="'+pid+'"]');
      if (!picker || picker.style.display !== 'block') {
        // send like
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function(){ try { var res = JSON.parse(xhr.responseText); if (res.ok) { location.reload(); } } catch(e) {} };
        xhr.send('action=react&post_id='+encodeURIComponent(pid)+'&type=like');
      }
    }
  });
})();
// handle emoji clicks from the per-post picker
document.addEventListener('click', function(e){
  var emoji = e.target.closest && e.target.closest('.emoji');
  if (emoji) {
    var type = emoji.getAttribute('data-type');
    var pid = emoji.closest('.emoji-picker').getAttribute('data-post-id');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'index.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function(){ try { var res = JSON.parse(xhr.responseText); if (res.ok) { location.reload(); } } catch(e) { console.error(e); } };
    xhr.send('action=react&post_id='+encodeURIComponent(pid)+'&type='+encodeURIComponent(type));
  }
});
// send a simple 'like' when the Like button (reaction-trigger) is clicked (if no emoji picker present)
// removed redundant click handler — handled by long-press logic above

// comment button scrolls to the comments area in the post
document.addEventListener('click', function(e){
  var cb = e.target.closest && e.target.closest('.comment-btn');
  if (cb) {
    var pid = cb.getAttribute('data-post-id');
    var post = document.querySelector('.post.card input[data-post-id="'+pid+'"], .post.card[data-post-id="'+pid+'"], .post.card');
    // naive: scroll to the first .comments element inside nearest .post
    var p = cb.closest('.post');
    if (p) {
      var comments = p.querySelector('.comments');
      if (comments) comments.scrollIntoView({behavior:'smooth', block:'center'});
    }
  }
});
document.addEventListener('change', function(e){
  var f = e.target;
  if (f.type === 'file') {
    var label = f.closest('.file-btn');
    if (label) label.classList.add('has-file');
  }
});

// handle icon-only reaction buttons (click to react) and show list of reactors on right-click (or click when holding alt)
document.addEventListener('click', function(e){
  var btn = e.target.closest && e.target.closest('.reaction-icon');
  if (btn) {
    var postId = btn.getAttribute('data-post-id');
    var type = btn.getAttribute('data-type');
    // send reaction via POST
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'index.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function(){ try { var res = JSON.parse(xhr.responseText); if (res.ok) { location.reload(); } } catch(e) {} };
    xhr.send('action=react&post_id='+encodeURIComponent(postId)+'&type='+encodeURIComponent(type));
  }
});

// AJAX comment submit handler
document.addEventListener('submit', function(e){
  var form = e.target.closest && e.target.closest('.comment-form');
  if (!form) return;
  e.preventDefault();
  var postId = form.getAttribute('data-post-id');
  var content = (form.querySelector('textarea[name="content"]') || {}).value || '';
  if (!content.trim()) return;
  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'index.php', true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  xhr.onload = function(){
    try {
      var res = JSON.parse(xhr.responseText);
      if (res.status === 'success') {
        // append comment to UI
        var comments = form.previousElementSibling; // the .comments div is before the form
        if (comments && comments.classList.contains('comments')) {
          var div = document.createElement('div'); div.className='comment'; div.innerHTML = '<strong>'+(res.author||'You')+':</strong> '+(content);
          comments.appendChild(div);
        }
        // update comments count
        var cc = document.querySelector('.comments-count[data-post-id="'+postId+'"]');
        if (cc) {
          var n = parseInt(cc.getAttribute('data-count')||cc.textContent)||0; n = n+1; cc.textContent = n+' comments'; cc.setAttribute('data-count', n);
        }
        form.querySelector('textarea[name="content"]').value = '';
      }
    } catch(e) { console.error(e); }
  };
  xhr.send('action=add_comment&post_id='+encodeURIComponent(postId)+'&content='+encodeURIComponent(content));
});

// show reactors list when clicking the reaction total element
document.addEventListener('click', function(e){
  var rc = e.target.closest && e.target.closest('.reaction-total');
  if (rc) {
    var pid = rc.id.replace('reactions-','');
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'index.php?action=reactions&post_id='+encodeURIComponent(pid), true);
    xhr.onload = function(){
      try {
        var data = JSON.parse(xhr.responseText) || [];
        var html = '<div class="reactors-popup">';
        if (!data || data.length === 0) html += '<div class="kv">No reactions yet</div>';
        data.forEach(function(r){
          html += '<div class="reactor-row">';
          html += '<div class="reactor-avatar">';
          if (r.avatar) html += '<img src="'+r.avatar+'" alt="avatar">'; else html += '<div class="placeholder"></div>';
          html += '</div>';
          html += '<div class="reactor-info"><div class="name"><a href="profile.php?id='+encodeURIComponent(r.id)+'">'+(r.name||'')+'</a></div><div class="kv">'+(r.type||'')+'</div></div>';
          html += '<div class="reactor-action"><a class="btn small" href="profile.php?id='+encodeURIComponent(r.id)+'">Add friend</a></div>';
          html += '</div>';
        });
        html += '</div>';
        // temporary modal
        var div = document.createElement('div'); div.className='reactors-modal'; div.innerHTML = html;
        document.body.appendChild(div);
        // clicking anywhere on the modal removes it
        setTimeout(function(){ div.addEventListener('click', function(){ div.remove(); }) }, 10);
      } catch(e) { console.error(e); }
    };
    xhr.send();
  }
});
