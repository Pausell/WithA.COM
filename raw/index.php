<?php
/**
 * =========================================================================
 * witha.com/raw
 * raw/index.php
 * =========================================================================
 */



/* =_SESSION_=============================================================*/

session_start();
require_once __DIR__.'/../auth/db.php';
require_once __DIR__.'/../auth/schema.php';

global $pdo, $pdoSites;

$account   = $_SESSION['user'] ?? 0;
$img      = null;
$username = '';
if ($account) {
    $stmt = $pdo->prepare("
        SELECT profile_image, username
          FROM users
         WHERE id = ?
         LIMIT 1
    ");
    $stmt->execute([$account]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $img      = $row['profile_image'] ?? null;
    $username = $row['username']      ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
  <title>R A\W</title>
  <meta name="description" content="Merged username + profile, forcing .html storage">
  <link rel="canonical" href="https://witha.com">
  <link rel="shortcut icon" type="image/png" href="aw.png">
  <link rel="apple-touch-icon" sizes="180x180" href="aw.png">
  <link rel="icon" type="image/png" sizes="32x32" href="aw32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="aw16.png">
  <meta name="theme-color" content="#000000">
  <meta name="msapplication-TileColor" content="#003700">
  <meta name="referrer" content="origin">
  <link rel="stylesheet" href="../style.css">
  <style>
#navigate{flex-direction:row-reverse}
#navigate a:last-child {text-align:left}
#navigate a:last-child{font-size:20px}

  
  
  
  
  form label{opacity:.5}
  .rscheme{border:1px solid var(--o10);padding:1rem;margin-bottom:1.3rem}
    .subm {
      font-size: 40%;
      line-height: 100%;
      display: inline-block;
      padding: 0 .5em;
      letter-spacing: .5px;
    }
    .subs {
      font-weight: 300;
      opacity: .7;
    }
    .login-form input {
      padding: .4em;
      background-color: transparent;
      color: #fff;
      outline: none;
    }
    .login-form input:first-of-type {
      flex: 1 1 auto;
    }
    .login-form input:last-of-type {
      display: none;
    }
    .login-form button {
      padding: 0.4em 0.6em;
      background-color: transparent;
      color: #ffffff;
      border: 2px inset rgb(118, 118, 118);
    }
  </style>
</head>
<body>
<?php if ($account): //user session check: if logged in ?>
 <nav id="navigate">
  <?php $ref = $_SERVER['HTTP_REFERER'] ?? ''; ?><a href="<?= htmlspecialchars($ref ?: "javascript:(document.referrer.startsWith(location.origin)?history.back():location.href='/')") ?>"            title="who we are: WithA"><h1>A\W</h1></a>
  <a href="/instantiate" title="" style="text-align:center"></a>
  <a href="/truth"       title="" style="text-align:center"></a>
  <a href="#" id="account" title="tap to replace image">

    <?php if ($img): ?>
     <img id="profile"
      src="<?= htmlspecialchars($img) ?>"
      alt="Profile"
      style="width:27.5px;height:27.5px;border-radius:50%;object-fit:cover;cursor:pointer">
    <?php else: ?>
     <h1 id="profile" style="cursor:pointer">&#174;</h1>
    <?php endif; ?>

  </a>
   <?php //hidden uploader (form) ?>
   <form id="upload-form" action="/auth/upload-profile.php"
    method="POST" enctype="multipart/form-data" style="display:none">
    <input type="file" id="profile-upload" name="profile_image" accept="image/*">
   </form>
 </nav>
<?php else: //user session check: if guest ?>
<nav id="navigate">
  <?php $ref = $_SERVER['HTTP_REFERER'] ?? ''; ?><a href="<?= htmlspecialchars($ref ?: "javascript:(document.referrer.startsWith(location.origin)?history.back():location.href='/')") ?>"            title="who we are: WithA"><h1>A\W</h1></a>
  <a href="/instantiate" title="" style="text-align:center"></a>
  <a href="/truth"       title="" style="text-align:center"></a>
  <a href="#" id="account" title="Enter your account here">

    <h1>&#174;<span class="subm">Connect<br><span class="subs">Tap Here</span></span></h1>

  </a>
</nav>
<?php endif; //end user session check ?>

<section id="beginning" class="contained">
  <main class="z1">
    <hr style="opacity:.2">
  </main>
</section>

<footer title="With All Respect"></footer>

<?php if (!$account): /* Not logged in => show login flow */ ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.querySelector('#navigate');
  const accountLink = document.getElementById('account');
  if (accountLink) {
    accountLink.onclick = e => {
      e.preventDefault();
      if (document.getElementById('login-form')) return;
      const subm = accountLink.querySelector('.subm');if (subm) subm.remove();
      ctx.insertAdjacentHTML('beforeend', `
  <form id="login-form" class="login-form" style="display:flex;gap:.5rem;align-items:center;">
    <input type="email" id="login-email" placeholder="Enter email" autocomplete="email" style="height:5ch" required>
    <input type="text"  id="login-otp"  placeholder="Waiting for code…" autocomplete="one-time-code" style="height:5ch">
    <button type="submit" id="login-submit" style="height:4ch;height:100%">continue</button>
  </form>
      `);
      bindLogin();
    };
  }

  function bindLogin(){
    const f  = document.getElementById('login-form'),
          em = document.getElementById('login-email'),
          ot = document.getElementById('login-otp'),
          bt = document.getElementById('login-submit');
    let poll;
    f.onsubmit = async ev => {
      ev.preventDefault();
      bt.disabled = true;
      bt.textContent = 'Sending…';
      const ok = await fetch('/auth/send-otp.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        credentials:'same-origin',
        body: JSON.stringify({email: em.value.trim()})
      });
      if(!ok.ok){
        alert('Send failed');
        location.reload();
      }
      ot.style.display='inline-block';
      ot.focus();
      bt.style.display='none';

      poll = setInterval(async ()=>{
        const j=await fetch('/auth/check-otp.php', {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          credentials:'same-origin',
          body:JSON.stringify({email: em.value.trim()})
        }).then(r=>r.json());
        if(j.code){
          clearInterval(poll);
          finish(j.code);
        }
      },1000);
    };
    async function finish(code){
      const r = await fetch('/auth/login.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        credentials:'same-origin',
        body: JSON.stringify({email: em.value.trim(), otp: code})
      });
      r.ok ? location.reload() : alert('Login failed');
    }
  }
});
</script>

<?php else: /* LOGGED IN => show combined Profile form + normal site manager */ ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // handle avatar uploading
  const avatar = document.getElementById('profile');
  const file   = document.getElementById('profile-upload');
  const form   = document.getElementById('upload-form');
  if (avatar && file && form) {
    let overlay;
    function showOverlay(msg){
      if(!overlay){
        overlay = document.createElement('div');
        Object.assign(overlay.style, {
          position:'fixed',
          top:'10px',
          right:'10px',
          padding:'6px 12px',
          background:'#000',
          color:'#fff',
          borderRadius:'4px',
          fontSize:'12px',
          zIndex:9999
        });
        document.body.appendChild(overlay);
      }
      overlay.textContent = msg;
      overlay.style.display = 'block';
    }
    function hideOverlay(delay=0){
      if(overlay){
        setTimeout(() => {
          overlay.style.display='none';
        }, delay);
      }
    }
    avatar.onclick = () => file.click();
    file.onchange = async () => {
      const MAX = 2*1024*1024; // 2MB
      if (!file.files.length) return;
      if (file.files[0].size > MAX) {
        alert('Image is larger than 2 MB.');
        file.value = '';
        return;
      }
      showOverlay('Uploading…');
      try {
        const res  = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          credentials: 'same-origin'
        });
        const json = await res.json();
        if (json.success && json.url) {
          const url = json.url + '?t=' + Date.now();
          if (avatar.tagName === 'IMG') {
            avatar.src = url;
          } else {
            avatar.outerHTML = `<img id="profile"
                                     src="${url}"
                                     style="width:2em;height:2em;border-radius:50%;object-fit:cover;cursor:pointer">`;
          }
          const newAvatar = document.getElementById('profile');
          if (newAvatar) {
            newAvatar.onclick = () => file.click();
          }
          showOverlay('Uploaded ✓');
          hideOverlay(1000);
        } else {
          hideOverlay();
          alert(json.error || 'Upload failed');
        }
      } catch (err) {
        console.error(err);
        hideOverlay();
        alert('Network / server error');
      } finally {
        file.value = '';
      }
    };
  }
});
</script>

<!-- 
     SINGLE “UPDATE PROFILE” FORM
     Combines the old "username" + "profile" logic into one action.
-->
<form id="profile-form" class="rscheme">
  <h1 style="margin-top:0;color:var(--d)">Record</h1>
  <label>Username</label><br>
    <input class="scheme" name="username" id="profile-username"
           maxlength="255"
           value="<?= htmlspecialchars($username) ?>"
           required><br>
  <label>Record</label><br>
    <textarea class="scheme" name="body" id="profile-body"
              rows="9"
              placeholder="<html>
<head>
<link rel='canonical' href='https://witha.com'>
<title>WithA.com Search</title>
</head>
</html>
"></textarea><br>
  <button class="button0" style="margin-top:.4rem">Update profile</button>
  <span id="profile-msg" style="margin-left:.5rem;font-size:.9rem"></span>
</form>

<!-- 
     TWO-FIELD FORM for other sites
     We keep your original site form for normal sites
-->
<form id="site-form" class="rscheme">
  <label>Domain</label><br>
  <input class="scheme" name="title"
         placeholder="witha.com (site address)"
         required><br>
  <label>File</label><br>
  <textarea class="scheme" name="body"
            rows="9"
            placeholder="<html>
<head>
<link rel='canonical' href='https://witha.com'>
<title>WithA.com Search</title>
</head>
</html>
"></textarea><br>
  <button class="button0">Publish / Save</button>
  <span id="site-msg" style="font-size:.9em;margin-left:.5em"></span>
</form>

<div class="container">
  <ul id="my-sites" style="list-style:none;padding:0"></ul>
</div>

<script>
/* 
  Forcing .html physically, while showing user just the base name 
  (No separate "username form" now. It's inside #profile-form.)
*/
function stripExtension(slug) {
  return slug.replace(/\.html$/i, '');
}
function addExtensionIfNeeded(name) {
  if (!/\.html$/i.test(name)) {
    return name + '.html';
  }
  return name;
}

const API_PROFILE = '/auth/update-username.php'; // or a new endpoint that handles both username+body
const API_SITE    = '/auth/site.php';

let currentUsername = <?= json_encode($username) ?>;

// references to the single merged profile form
const profForm = document.getElementById('profile-form');
const profMsg  = document.getElementById('profile-msg');
const siteForm = document.getElementById('site-form');
const siteMsg  = document.getElementById('site-msg');
const ul       = document.getElementById('my-sites');

/* ========== MERGED PROFILE FORM HANDLER ========== */
if (profForm) {
  // If there's an existing profile, load it (server might do that above)
  // We'll do it again in code if you want. But let's assume it's done.

  // or we can do a client-side fetch:
  if (currentUsername) {
    fetchProfileHTML(currentUsername);
  }

  profForm.addEventListener('submit', async ev => {
    ev.preventDefault();
    const user = profForm.username.value.trim();
    const body = profForm.body.value;
    if (!user) {
      profMsg.textContent = 'Username is required';
      return;
    }
    profMsg.textContent='Saving…';
    try {
      // We pass {username, body} in one request
      // The server must rename oldUsername.html -> newUsername.html if needed
      // and store 'body' in newUsername.html
      const payload = { username: user, body };
      const r = await fetch(API_PROFILE, {
        method:'POST',
        credentials:'same-origin',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      });
      const j = await r.json();
      if (j.ok) {
        profMsg.textContent = 'Saved ✓';
        currentUsername = user; // keep in sync
        listSites();
      } else {
        profMsg.textContent = j.error || 'Error';
      }
    } catch(e) {
      profMsg.textContent='Network error';
    }
  });
}

// optionally define a function to fetch the user’s profile HTML
async function fetchProfileHTML(user) {
  // physically user + '.html'
  const slug = addExtensionIfNeeded(user);
  const r = await fetch(`${API_SITE}?action=get&slug=${encodeURIComponent(slug)}`, {
    credentials:'same-origin'
  });
  const j = await r.json();
  if (j.ok && profForm) {
    profForm.body.value = j.body || '';
  }
}

/* ========== LIST ALL SITES (Including Profile) ========== */
async function listSites() {
  ul.innerHTML = '';
  const j = await fetch(`${API_SITE}?action=list`, {
    credentials:'same-origin'
  }).then(r=>r.json());
  if (!j.ok) return;
  const profileSlug = addExtensionIfNeeded(currentUsername);

  j.data.forEach(siteRow => {
    const slug = siteRow.filename; // e.g. "witha.com.html"
    const base = stripExtension(slug);
    const isProfile = (slug.toLowerCase() === profileSlug.toLowerCase());

    ul.insertAdjacentHTML('beforeend', `
      <li>
        <a href="/connectere/${encodeURIComponent(slug)}" target="_blank">
          ${base}
        </a>
        ${
          isProfile
            ? ''  // no delete for profile
            : `<button class="btn" data-slug="${slug}" data-act="edit">edit</button><button class="btn" data-slug="${slug}" data-act="del">✖</button>`
        }
      </li>
    `);
  });
}
listSites();

/* ========== EDIT / DELETE for normal sites ========== */
ul.addEventListener('click', async e => {
  const slug = e.target.dataset.slug;
  if (!slug) return;
  const act  = e.target.dataset.act;

  if (act === 'del') {
    if (!confirm(`Delete ${stripExtension(slug)}?`)) return;
    const delRes = await fetch(`${API_SITE}?action=delete`, {
      method:'POST',
      credentials:'same-origin',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ slug })
    });
    const j = await delRes.json();
    if (!j.ok) {
      alert(j.error || 'Delete failed');
    }
    listSites();

  } else if (act === 'edit') {
    const getRes = await fetch(`${API_SITE}?action=get&slug=${encodeURIComponent(slug)}`, {
      credentials:'same-origin'
    }).then(r=>r.json());
    if (!getRes.ok) {
      alert(getRes.error || 'Error loading site');
      return;
    }
    siteForm.title.value = stripExtension(getRes.title);
    siteForm.body.value  = getRes.body;
  }
});

/* ========== PUBLISH / SAVE normal site ========== */
siteForm.addEventListener('submit', async ev => {
  ev.preventDefault();
  const data = Object.fromEntries(new FormData(siteForm).entries());
  let theTitle = data.title.trim();
  const theBody = data.body || '';

  // physically store with .html
  theTitle = addExtensionIfNeeded(theTitle);

  siteMsg.textContent = 'Saving…';
  try {
    const r = await fetch(`${API_SITE}?action=publish`, {
      method:'POST',
      credentials:'same-origin',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ title: theTitle, body: theBody })
    });
    const j = await r.json();
    siteMsg.textContent = j.ok ? 'Saved ✓' : (j.error || 'Error');
    if (j.ok) {
      siteForm.reset();
      listSites();
    }
  } catch(e){
    siteMsg.textContent = 'Network error';
  }
});
</script>

<!-- LOGOUT BUTTONS -->
<div class="container">
  <button class="button0" onclick="location.href='/auth/logout.php?mode=current'">
    Log&nbsp;out&nbsp;(this&nbsp;device)
  </button>
  <button class="button0" onclick="if(confirm('Log out everywhere?'))
                   location.href='/auth/logout.php?mode=all'">
    Log&nbsp;out&nbsp;EVERYWHERE
  </button>
</div>
<?php endif; /* end if($account) */ ?>
</body>
</html>