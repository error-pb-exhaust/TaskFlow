(function () {
  "use strict";
  var token = new URLSearchParams(location.hash.slice(1)).get("token") || "";
  var csrf = "";
  var mode = "register";
  var invitation = null;
  var user = null;
  var message = document.getElementById("joinMessage");
  var form = document.getElementById("joinAuthForm");
  async function request(url, data) {
    var options = data ? { method: "POST", headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf }, body: JSON.stringify(data) } : {};
    var response = await fetch(url, options);
    var result;
    try { result = await response.json(); } catch (_) { throw new Error("The server could not process this request. Ask the manager to check TaskFlow setup."); }
    if (result.csrf_token) csrf = result.csrf_token;
    if (!response.ok || !result.success) throw new Error(result.message || "Request failed.");
    return result;
  }
  function showAccount() {
    document.getElementById("joinAuth").hidden = !!user;
    document.getElementById("joinAccept").hidden = !user;
    if (user) {
      var matches = user.email.toLowerCase() === invitation.email.toLowerCase();
      document.getElementById("joinAccount").textContent = "Signed in as " + user.email + (matches ? ". Accept to join this project." : ". Please switch to " + invitation.email + ".");
      document.getElementById("acceptInvitation").disabled = !matches;
    }
  }
  function setMode(value) {
    mode = value;
    document.getElementById("joinNameRow").hidden = mode !== "register";
    form.elements.name.required = mode === "register";
    form.elements.password.minLength = mode === "register" ? 8 : 1;
    form.elements.password.autocomplete = mode === "register" ? "new-password" : "current-password";
    document.getElementById("joinAuthSubmit").textContent = mode === "register" ? "Create account" : "Sign in";
    message.textContent = "";
  }
  document.getElementById("showSignUp").onclick = function () { setMode("register"); };
  document.getElementById("showSignIn").onclick = function () { setMode("login"); };
  form.onsubmit = async function (event) {
    event.preventDefault();
    var button = document.getElementById("joinAuthSubmit"); button.disabled = true;
    message.textContent = "";
    try {
      var result = await request("api/auth/" + mode + ".php", { name: form.elements.name.value, email: invitation.email, password: form.elements.password.value, account_type: "member" });
      user = result.user; form.elements.password.value = ""; showAccount();
    } catch (error) { message.textContent = error.message; }
    finally { button.disabled = false; }
  };
  document.getElementById("switchJoinAccount").onclick = async function () {
    try {
      await request("api/auth/logout.php", {});
      await request("api/auth/me.php"); user = null; showAccount(); setMode("login");
    } catch (error) { message.textContent = error.message; }
  };
  document.getElementById("acceptInvitation").onclick = async function () {
    var button = this; button.disabled = true;
    try {
      var result = await request("api/projects/accept-invite.php", { token: token, action: "accept" });
      message.textContent = result.message;
      document.getElementById("joinAccept").hidden = true;
      document.getElementById("openJoinedProject").hidden = false;
      history.replaceState(null, "", location.pathname);
    } catch (error) { message.textContent = error.message; button.disabled = false; }
  };
  async function load() {
    try {
      var auth = await request("api/auth/me.php"); user = auth.authenticated ? auth.user : null;
      var result = await request("api/projects/accept-invite.php", { token: token, action: "preview" });
      invitation = result.invitation;
      document.getElementById("inviteHeading").textContent = "Join " + invitation.project_name;
      document.getElementById("inviteSummary").textContent = invitation.manager_name + " invited you to their project. This link expires on " + invitation.expires_at + ".";
      document.getElementById("invitedEmail").textContent = invitation.email;
      form.elements.email.value = invitation.email;
      setMode("register"); showAccount();
    } catch (error) { document.getElementById("inviteSummary").textContent = "Invitation unavailable"; message.textContent = error.message; }
  }
  load();
})();
