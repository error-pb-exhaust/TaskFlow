<?php
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Join a TaskFlow project</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f6fc;color:#1c2940;font:16px system-ui,sans-serif;display:grid;min-height:100vh;place-items:center;padding:24px}main{width:100%;max-width:480px;background:white;border:1px solid #e2e8f0;border-radius:20px;padding:32px;box-shadow:0 20px 60px #24365912}h1{font-size:28px;margin:12px 0}p{line-height:1.6;color:#57657a}label{display:block;margin:16px 0;font-weight:600}input{display:block;width:100%;padding:12px;margin-top:7px;border:1px solid #bdc8d8;border-radius:8px;font:inherit}button,a.button{padding:12px 16px;border:0;border-radius:8px;font:inherit;cursor:pointer;background:#086bea;color:white;text-decoration:none;display:inline-block}button:disabled{opacity:.6;cursor:wait}.secondary{background:#edf2fa;color:#214675}.row{display:flex;gap:10px;flex-wrap:wrap}.notice{white-space:pre-wrap;color:#92400e;background:#fff7e6;padding:12px;border-radius:8px;margin:16px 0}.notice:empty{display:none}[hidden]{display:none!important}.brand{font-weight:800;color:#086bea}small{color:#64748b}
</style><script src="join.js" defer></script></head><body><main>
<div class="brand">TaskFlow</div><h1 id="inviteHeading">Project invitation</h1><p id="inviteSummary">Checking your invitation…</p>
<div id="joinMessage" class="notice" role="status"></div>
<div id="joinAuth" hidden><p>Create an account or sign in using <strong id="invitedEmail"></strong>.</p>
<div class="row"><button class="secondary" type="button" id="showSignUp">Create account</button>
<button class="secondary" type="button" id="showSignIn">Sign in</button></div>

<form id="joinAuthForm"><label id="joinNameRow">Your name<input name="name" minlength="2" autocomplete="name"></label>
<label>Email<input name="email" type="email" readonly required autocomplete="username"></label>
<label>Password<input name="password" type="password" required autocomplete="new-password"></label>
<button type="submit" id="joinAuthSubmit">Create account</button>
</form>
</div>
<div id="joinAccept" hidden>
    <p id="joinAccount"></p>
    
    <div class="row">
    <button type="button" id="acceptInvitation">Accept invitation</button>
    <button class="secondary" type="button" id="switchJoinAccount">Use another account</button>
</div>
</div>
<a class="button" href="./" id="openJoinedProject" hidden>Open TaskFlow</a>
</main></body></html>
