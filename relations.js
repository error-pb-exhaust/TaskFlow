(function () {
  "use strict";
  var state = null, user = null;
  var el = function (id) { return document.getElementById(id); };
  var esc = function (value) { var n = document.createElement('span'); n.textContent = value == null ? '' : String(value); return n.innerHTML.replace(/"/g,'&quot;').replace(/'/g,'&#39;'); };
  function fill(select, rows, placeholder) {
    var old = select.value;
    select.innerHTML = rows.map(function (row) { return '<option value="' + Number(row.id) + '">' + esc(row.name) + '</option>'; }).join('') || '<option value="">' + esc(placeholder) + '</option>';
    if (rows.some(function (row) { return String(row.id) === old; })) select.value = old;
  }
  async function mutate(endpoint, data, button) {
    if (button) button.disabled = true;
    try {
      var result = await window.taskflow.api(endpoint, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
      await window.taskflow.refresh();
      window.taskflow.toast('Updated', result.message);
      return true;
    } catch (error) { window.taskflow.toast('Could not update',error.message); return false; }
    finally { if (button) button.disabled = false; }
  }
  function selectedProject() { return state.projects.find(function(p){return String(p.id)===el('relationsProjectSelect').value;}); }
  function selectedTeam() { return state.teams.find(function(t){return String(t.id)===el('reusableTeamSelect').value;}); }
  function renderProject() {
    var p=selectedProject(); var list=el('relationsMembers');
    if (!p) { list.textContent='No projects yet. Create a project or accept an invitation.'; el('leaveProjectButton').hidden=true; el('relationsProjectSummary').textContent='';return; }
    el('relationsProjectSummary').textContent=p.is_owner ? 'You own this project. You can appoint managers or transfer ownership.' : p.can_manage ? 'You are a project manager. You can assign work and invite teammates.' : 'You are a project member. Only visible project tasks are shared with you.';
    el('leaveProjectButton').hidden=!!p.is_owner;
    list.innerHTML=state.memberships.filter(function(m){return Number(m.project_id)===Number(p.id);}).map(function(link){
      var person=state.members.find(function(m){return Number(m.id)===Number(link.user_id);}); if(!person)return '';
      var role=link.project_role; var controls='';
      if(p.is_owner && role!=='owner' && person.status==='active' && person.role!=='guest') {
        controls+='<button type="button" class="secondary-button" data-person="'+Number(person.id)+'" data-project-action="role" data-role="'+(role==='manager'?'member':'manager')+'">'+(role==='manager'?'Make member':'Make manager')+'</button> ';
        controls+='<button type="button" class="secondary-button" data-person="'+Number(person.id)+'" data-project-action="transfer">Transfer ownership</button> ';
      }
      if(p.can_manage && role!=='owner' && Number(person.id)!==Number(user.id) && (role!=='manager'||p.is_owner)) controls+='<button type="button" class="secondary-button" data-person="'+Number(person.id)+'" data-project-action="remove">Remove</button>';
      return '<div class="project-team-person"><span><b>'+esc(person.name)+'</b> <small>'+esc(role)+' · '+esc(person.status)+'</small></span><span>'+controls+'</span></div>';
    }).join('');
    list.querySelectorAll('[data-project-action]').forEach(function(button){button.onclick=async function(){
      var action=button.dataset.projectAction;
      if(action==='transfer'&&!confirm('Transfer ownership? You will remain a project manager; the new owner will control manager roles.'))return;
      if(action==='remove'&&!confirm('Remove this person from the project? Open tasks must be reassigned first.'))return;
      await mutate('api/projects/relations.php',{project_id:Number(p.id),action:action,user_id:Number(button.dataset.person),project_role:button.dataset.role},button);
    };});
  }
  function renderTeam() {
    var team=selectedTeam(), owner=!!team&&Number(team.owner_id)===Number(user.id);
    el('addReusableMemberForm').hidden=!owner;el('attachReusableTeamForm').hidden=!owner;
    var mine=team&&state.team_members.some(function(link){return Number(link.team_id)===Number(team.id)&&Number(link.user_id)===Number(user.id);});
    el('leaveReusableTeam').hidden=!mine||owner;
    if(!team){el('reusableTeamPeople').textContent='Create your first reusable team.';return;}
    el('reusableTeamPeople').innerHTML=state.team_members.filter(function(link){return Number(link.team_id)===Number(team.id);}).map(function(link){
      var person=state.members.find(function(m){return Number(m.id)===Number(link.user_id);}); if(!person)return '';
      return '<div class="project-team-person"><span>'+esc(person.name)+(Number(person.id)===Number(team.owner_id)?' · Team owner':'')+'</span>'+(owner&&Number(person.id)!==Number(user.id)?'<button type="button" class="secondary-button" data-remove-team-person="'+Number(person.id)+'">Remove from reusable team</button>':'')+'</div>';
    }).join('');
    el('reusableTeamPeople').querySelectorAll('[data-remove-team-person]').forEach(function(button){button.onclick=function(){mutate('api/teams/manage.php',{action:'remove',team_id:Number(team.id),user_id:Number(button.dataset.removeTeamPerson)},button);};});
  }
  window.renderRelations=function(data,account){
    state=data;user=account;if(!user)return;
    fill(el('relationsProjectSelect'),state.projects,'No projects');
    fill(el('reusableTeamSelect'),state.teams,'No reusable teams');
    fill(el('attachTeamProject'),state.projects.filter(function(p){return p.can_manage&&p.status!=='archived';}),'No managed projects');
    renderProject();renderTeam();
  };
  el('relationsProjectSelect').onchange=renderProject;el('reusableTeamSelect').onchange=renderTeam;
  el('leaveProjectButton').onclick=function(){var p=selectedProject();if(p&&confirm('Leave this project? Reassign your open tasks first.'))mutate('api/projects/relations.php',{action:'leave',project_id:Number(p.id)},this);};
  el('leaveReusableTeam').onclick=function(){var t=selectedTeam();if(t)mutate('api/teams/manage.php',{action:'leave',team_id:Number(t.id)},this);};
  el('createReusableTeamForm').onsubmit=async function(event){event.preventDefault();var form=this;if(await mutate('api/teams/manage.php',{action:'create',name:form.elements.name.value},form.querySelector('button')))form.reset();};
  el('addReusableMemberForm').onsubmit=async function(event){event.preventDefault();var t=selectedTeam();if(!t)return;var form=this;if(await mutate('api/teams/manage.php',{action:'add',team_id:Number(t.id),email:form.elements.email.value},form.querySelector('button')))form.reset();};
  el('attachReusableTeamForm').onsubmit=function(event){event.preventDefault();var t=selectedTeam();if(t)mutate('api/teams/manage.php',{action:'attach',team_id:Number(t.id),project_id:Number(this.elements.project_id.value)},this.querySelector('button'));};
})();
