(function () {
  "use strict";

  var body = document.body;
  var sidebar = document.getElementById("sidebar");
  var sidebarScrim = document.querySelector(".sidebar-scrim");
  var drawer = document.getElementById("taskDrawer");
  var drawerScrim = document.querySelector(".drawer-scrim");
  var modal = document.getElementById("taskModal");
  var inviteModal = document.getElementById("inviteModal");
  var projectModal = document.getElementById("projectModal");
  var profileModal = document.getElementById("profileModal");
  var notificationPanel = document.getElementById("notificationPanel");
  var login = document.getElementById("loginScreen");
  var register = document.getElementById("registerScreen");
  var pageTitle = document.getElementById("pageTitle");
  var breadcrumb = document.getElementById("breadcrumb");
  var search = document.getElementById("globalSearch");
  var searchButton = document.getElementById("searchButton");
  var toast = document.getElementById("toast");
  var profilePopover = document.getElementById("profilePopover");
  var lastView = "dashboard";
  var allDatabaseTasks = [];
  var activeTaskFilter = "all";
  var signedInUser = null;
  var taskRequestNumber = 0;
  var adminAuditRows = [];
  var workspaceData = { tasks: [], projects: [], members: [], activity: [] };
  var activeProjectFilter = "all";
  var selectedBoardProject = "all";
  var calendarCursor = new Date();
  var draggedTaskId = null;
  var currentOpenTask = null;
  var currentTaskDetails = { comments: [], checklist: [] };

  async function api(url, options) {
    var response = await fetch(url, options || {});
    var result;
    try {
      result = await response.json();
    } catch (error) {
      throw new Error("The server returned an invalid response.");
    }
    if (!response.ok || !result.success) {
      throw new Error(result.message || "The request failed.");
    }
    return result;
  }

  function showAuthMessage(element, message) {
    if (!element) return;
    element.textContent = message || "";
    element.classList.toggle("show", Boolean(message));
  }

  function escapeHtml(value) {
    var div = document.createElement("div");
    div.textContent = value == null ? "" : String(value);
    return div.innerHTML;
  }

  function formatStatus(status) {
    return ({ backlog: "Backlog", todo: "To do", progress: "In progress", review: "In review", done: "Done" })[status] || status;
  }

  function localDateKey(date) {
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, "0");
    var day = String(date.getDate()).padStart(2, "0");
    return year + "-" + month + "-" + day;
  }

  function taskMatchesFilter(task, filter) {
    var today = localDateKey(new Date());
    if (filter === "today") return task.due_date === today;
    if (filter === "upcoming") return Boolean(task.due_date && task.due_date > today);
    if (filter === "overdue") return Boolean(task.due_date && task.due_date < today && task.status !== "done");
    return true;
  }

  function updateTaskCounts() {
    var countMap = {
      allTaskCount: allDatabaseTasks.length,
      todayTaskCount: allDatabaseTasks.filter(function (task) { return taskMatchesFilter(task, "today"); }).length,
      upcomingTaskCount: allDatabaseTasks.filter(function (task) { return taskMatchesFilter(task, "upcoming"); }).length,
      overdueTaskCount: allDatabaseTasks.filter(function (task) { return taskMatchesFilter(task, "overdue"); }).length
    };
    Object.keys(countMap).forEach(function (id) {
      var element = document.getElementById(id);
      if (element) element.textContent = countMap[id];
    });
  }

  function displayFilteredTasks() {
    var filtered = allDatabaseTasks.filter(function (task) {
      return taskMatchesFilter(task, activeTaskFilter);
    });
    renderDatabaseTasks(filtered);
  }

  function updateClock() {
    var now = new Date();
    var dateNode = document.getElementById("currentDate");
    var timeNode = document.getElementById("currentTime");
    if (dateNode) {
      dateNode.textContent = now.toLocaleDateString(undefined, { weekday: "long", month: "long", day: "numeric", year: "numeric" }).toUpperCase();
    }
    if (timeNode) {
      timeNode.textContent = now.toLocaleTimeString(undefined, { hour: "2-digit", minute: "2-digit", second: "2-digit" });
    }
  }

  function updateWelcomeGreeting() {
    var greetingNode = document.getElementById("welcomeGreeting");
    if (!greetingNode) return;
    var hour = new Date().getHours();
    var greeting = hour < 12 ? "Good morning" : hour < 18 ? "Good afternoon" : "Good evening";
    var firstName = signedInUser && signedInUser.name ? signedInUser.name.trim().split(/\s+/)[0] : "there";
    greetingNode.textContent = greeting + ", " + firstName + "!";
  }

  function updateUserProfile(user) {
    signedInUser = user;
    var name = user && user.name ? user.name : "TaskFlow User";
    var email = user && user.email ? user.email : "";
    var roleLabels = { admin: "Administrator", manager: "Project Manager", member: "Team Member", guest: "Guest" };
    var role = roleLabels[user && user.role] || "Team Member";
    var initial = name.trim().charAt(0).toUpperCase() || "U";
    document.querySelectorAll("[data-user-name]").forEach(function (node) { node.textContent = name; });
    document.querySelectorAll("[data-user-email]").forEach(function (node) { node.textContent = email; });
    document.querySelectorAll("[data-user-role]").forEach(function (node) { node.textContent = role; });
    document.querySelectorAll("[data-user-avatar]").forEach(function (node) { node.textContent = initial; });
    document.querySelectorAll("[data-admin-navigation]").forEach(function (node) {
      node.style.display = user && user.role === "admin" ? "grid" : "none";
    });
    updateWelcomeGreeting();
  }

  function setAdminText(id, value) {
    var node = document.getElementById(id);
    if (node) node.textContent = value;
  }

  function adminRoleLabel(role) {
    return ({ admin: "Workspace Admin", manager: "Project Manager", member: "Member", guest: "Guest" })[role] || role;
  }

  function formatAdminDate(value) {
    if (!value) return "Never";
    var date = new Date(String(value).replace(" ", "T"));
    return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
  }

  function showAdminMessage(message, isError) {
    var node = document.getElementById("adminMessage");
    if (!node) return;
    node.textContent = message || "";
    node.classList.toggle("show", Boolean(message));
    node.classList.toggle("error", Boolean(isError));
  }

  async function loadAdminOverview() {
    try {
      var result = await api("api/admin/overview.php");
      setAdminText("adminActiveUsers", result.metrics.active_users);
      setAdminText("adminPendingInvites", result.metrics.pending_invites);
      setAdminText("adminSecurityAlerts", result.metrics.security_alerts);
      setAdminText("adminSystemHealth", result.metrics.system_health);
      setAdminText("admin2faAttention", result.attention.two_factor_disabled + " members");
      setAdminText("adminInviteAttention", result.attention.expiring_invites + " invites");
      setAdminText("adminOverdueAttention", result.attention.overdue_tasks + " tasks");

      var chart = document.getElementById("adminActivityChart");
      var maximum = Math.max.apply(null, result.activity.map(function (item) { return Number(item.total); }).concat([1]));
      chart.innerHTML = result.activity.map(function (item) {
        var height = Math.max(6, Math.round((Number(item.total) / maximum) * 92));
        return '<span title="' + escapeHtml(item.date + ": " + item.total + " actions") + '"><b>' + Number(item.total) + '</b><i style="height:' + height + '%"></i><small>' + escapeHtml(item.day.charAt(0)) + '</small></span>';
      }).join("");
      showAdminMessage("Live administration data loaded.", false);
    } catch (error) {
      showAdminMessage(error.message, true);
    }
  }

  function roleOptions(selected) {
    return ["admin", "manager", "member", "guest"].map(function (role) {
      return '<option value="' + role + '"' + (selected === role ? " selected" : "") + '>' + adminRoleLabel(role) + '</option>';
    }).join("");
  }

  async function loadAdminMembers() {
    var list = document.getElementById("adminMemberList");
    try {
      var result = await api("api/admin/members.php");
      var userHtml = result.users.map(function (user) {
        var isCurrent = signedInUser && Number(signedInUser.id) === Number(user.id);
        return '<div class="member-row admin-user-row" data-admin-user="' + Number(user.id) + '">' +
          '<span><i class="avatar blue-bg">' + escapeHtml(user.name.charAt(0).toUpperCase()) + '</i><b>' + escapeHtml(user.name) + '<small>' + escapeHtml(user.email) + '</small></b></span>' +
          '<select class="admin-role-select"' + (isCurrent ? " disabled" : "") + '>' + roleOptions(user.role) + '</select>' +
          '<select class="admin-status-select"' + (isCurrent ? " disabled" : "") + '><option value="active"' + (user.status === "active" ? " selected" : "") + '>Active</option><option value="disabled"' + (user.status === "disabled" ? " selected" : "") + '>Disabled</option></select>' +
          '<span class="admin-date">' + escapeHtml(formatAdminDate(user.last_login_at)) + '</span>' +
          (isCurrent ? '<strong class="current-user-label">You</strong>' : '<button class="secondary-button admin-save-user" type="button">Save</button>') +
        '</div>';
      }).join("");
      var inviteHtml = result.invitations.map(function (invitation) {
        return '<div class="member-row admin-invite-row" data-admin-invitation="' + Number(invitation.id) + '"><span><i class="avatar yellow-bg">' + escapeHtml(invitation.name.charAt(0).toUpperCase()) + '</i><b>' + escapeHtml(invitation.name) + '<small>' + escapeHtml(invitation.email) + '</small></b></span><span>' + escapeHtml(adminRoleLabel(invitation.role)) + '</span><em class="availability meeting">Pending</em><span class="admin-date">Expires ' + escapeHtml(formatAdminDate(invitation.expires_at)) + '</span><button class="secondary-button admin-cancel-invite" type="button">Cancel</button></div>';
      }).join("");
      list.innerHTML = userHtml + inviteHtml || '<p class="admin-loading">No members found.</p>';

      list.querySelectorAll(".admin-save-user").forEach(function (button) {
        button.addEventListener("click", async function () {
          var row = button.closest("[data-admin-user]");
          button.disabled = true;
          try {
            await api("api/admin/update-user.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ user_id: Number(row.dataset.adminUser), role: row.querySelector(".admin-role-select").value, status: row.querySelector(".admin-status-select").value })
            });
            showToast("Member updated", "Role and account status were saved.");
            await Promise.all([loadAdminMembers(), loadAdminOverview(), loadAdminAudit()]);
          } catch (error) {
            showToast("Update failed", error.message);
            button.disabled = false;
          }
        });
      });
      list.querySelectorAll(".admin-cancel-invite").forEach(function (button) {
        button.addEventListener("click", async function () {
          var row = button.closest("[data-admin-invitation]");
          if (!window.confirm("Cancel this pending invitation?")) return;
          button.disabled = true;
          try {
            await api("api/admin/cancel-invite.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ invitation_id: Number(row.dataset.adminInvitation) })
            });
            showToast("Invitation cancelled", "The invitation is no longer active.");
            await Promise.all([loadAdminMembers(), loadAdminOverview(), loadAdminAudit()]);
          } catch (error) {
            showToast("Cancel failed", error.message);
            button.disabled = false;
          }
        });
      });
    } catch (error) {
      list.innerHTML = '<p class="admin-loading error-text">' + escapeHtml(error.message) + '</p>';
    }
  }

  async function loadAdminSettings() {
    try {
      var result = await api("api/admin/settings.php");
      document.getElementById("require2faToggle").checked = result.settings.require_2fa === "1";
      document.getElementById("allowGuestToggle").checked = result.settings.allow_guest_access === "1";
      document.getElementById("retentionDays").value = result.settings.retention_days || "365";
    } catch (error) {
      showToast("Settings unavailable", error.message);
    }
  }

  async function loadAdminAudit() {
    var list = document.getElementById("adminAuditList");
    try {
      var result = await api("api/admin/audit.php");
      adminAuditRows = result.logs || [];
      list.innerHTML = adminAuditRows.map(function (log) {
        return '<div><time>' + escapeHtml(formatAdminDate(log.created_at)) + '</time><i class="dot purple"></i><span><b>' + escapeHtml(log.action) + '</b><small>' + escapeHtml(log.details) + '</small></span><em>' + escapeHtml(log.user_email || "System") + '</em></div>';
      }).join("") || '<p class="admin-loading">No audit activity yet.</p>';
    } catch (error) {
      list.innerHTML = '<p class="admin-loading error-text">' + escapeHtml(error.message) + '</p>';
    }
  }

  function loadAdminTab(tab) {
    if (tab === "overview") loadAdminOverview();
    if (tab === "roles") loadAdminMembers();
    if (tab === "governance") {
      loadAdminSettings();
      loadAdminAudit();
    }
  }

  function setText(id, value) {
    var node = document.getElementById(id);
    if (node) node.textContent = value;
  }

  function percent(value, total) {
    return total ? Math.round((Number(value) / Number(total)) * 100) : 0;
  }

  function memberCapacity(member) {
    return Math.min(100, Number(member.open_tasks || 0) * 10);
  }

  function shortDate(value) {
    if (!value) return "No deadline";
    var date = new Date(value + "T12:00:00");
    return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString(undefined, { month: "short", day: "numeric", year: date.getFullYear() !== new Date().getFullYear() ? "numeric" : undefined });
  }

  function populateProjectSelectors() {
    var taskSelect = document.getElementById("taskProjectSelect");
    var boardSelect = document.getElementById("boardProjectSelect");
    var activeProjects = workspaceData.projects.filter(function (project) { return project.status !== "archived"; });
    var taskOptions = activeProjects.map(function (project) {
      return '<option value="' + Number(project.id) + '">' + escapeHtml(project.name) + '</option>';
    }).join("");
    var boardOptions = activeProjects.map(function (project) {
      return '<option value="' + Number(project.id) + '">' + escapeHtml(project.name) + '</option>';
    }).join("");
    if (taskSelect) {
      var previous = taskSelect.value;
      taskSelect.innerHTML = taskOptions || '<option value="">Create a project first</option>';
      if (previous && taskSelect.querySelector('option[value="' + previous + '"]')) taskSelect.value = previous;
    }
    if (boardSelect) {
      boardSelect.innerHTML = '<option value="all">All projects</option>' + boardOptions;
      boardSelect.value = String(selectedBoardProject);
    }
    var assigneeSelect = document.getElementById("taskAssigneeSelect");
    if (assigneeSelect) {
      var selectedAssignee = assigneeSelect.value || (signedInUser ? String(signedInUser.id) : "");
      assigneeSelect.innerHTML = workspaceData.members.filter(function (member) { return member.status === "active"; }).map(function (member) {
        return '<option value="' + Number(member.id) + '">' + escapeHtml(member.name) + ' — ' + escapeHtml(adminRoleLabel(member.role)) + '</option>';
      }).join("") || '<option value="">No active members</option>';
      if (assigneeSelect.querySelector('option[value="' + selectedAssignee + '"]')) assigneeSelect.value = selectedAssignee;
    }
  }

  function renderSidebarProjects() {
    var node = document.getElementById("sidebarProjects");
    if (!node) return;
    node.innerHTML = '<span class="nav-label">Projects</span>' + workspaceData.projects.filter(function (project) { return project.status !== "archived"; }).slice(0, 5).map(function (project) {
      return '<button type="button" data-sidebar-project="' + Number(project.id) + '"><i class="project-dot" style="background:' + escapeHtml(project.color || "#4776e6") + '"></i>' + escapeHtml(project.name) + '</button>';
    }).join("");
    node.querySelectorAll("[data-sidebar-project]").forEach(function (button) {
      button.addEventListener("click", function () {
        selectedBoardProject = button.dataset.sidebarProject;
        populateProjectSelectors();
        renderBoard();
        activateView("board");
      });
    });
  }

  function renderDashboard() {
    var tasks = workspaceData.tasks;
    var projects = workspaceData.projects.filter(function (project) { return project.status === "active"; });
    var members = workspaceData.members.filter(function (member) { return member.status === "active"; });
    var today = localDateKey(new Date());
    var completed = tasks.filter(function (task) { return task.status === "done"; }).length;
    var overdue = tasks.filter(function (task) { return task.due_date && task.due_date < today && task.status !== "done"; }).length;
    setText("dashboardTotalTasks", tasks.length);
    setText("dashboardProgressTasks", tasks.filter(function (task) { return task.status === "progress" || task.status === "review"; }).length);
    setText("dashboardOverdueTasks", overdue);
    setText("dashboardCompletion", percent(completed, tasks.length) + "%");

    var mini = document.getElementById("dashboardMiniBoard");
    if (mini) mini.innerHTML = projects.slice(0, 4).map(function (project) {
      var progress = percent(project.completed_count, project.task_count);
      return '<button type="button" class="mini-project" data-dashboard-project="' + Number(project.id) + '"><span style="background:' + escapeHtml(project.color || "#4776e6") + '"></span><b>' + escapeHtml(project.name) + '</b><small>' + Number(project.task_count) + ' tasks · ' + progress + '%</small></button>';
    }).join("") || '<p class="database-empty">Create a project to begin.</p>';
    if (mini) mini.querySelectorAll("[data-dashboard-project]").forEach(function (button) {
      button.addEventListener("click", function () { selectedBoardProject = button.dataset.dashboardProject; populateProjectSelectors(); renderBoard(); activateView("board"); });
    });

    var activity = document.getElementById("dashboardActivityFeed");
    if (activity) activity.innerHTML = workspaceData.activity.slice(0, 5).map(function (log) {
      return '<div><span class="avatar tiny blue-bg">' + escapeHtml((log.user_name || "S").charAt(0).toUpperCase()) + '</span><p><strong>' + escapeHtml(log.user_name || "System") + '</strong> ' + escapeHtml(log.details || log.action) + '<small>' + escapeHtml(formatAdminDate(log.created_at)) + '</small></p></div>';
    }).join("") || '<p class="database-empty">No activity yet.</p>';

    var workload = document.getElementById("dashboardWorkload");
    if (workload) workload.innerHTML = members.slice(0, 5).map(function (member) {
      var capacity = memberCapacity(member);
      return '<div><span><i class="avatar tiny purple-bg">' + escapeHtml(member.name.charAt(0).toUpperCase()) + '</i><b>' + escapeHtml(member.name) + '</b></span><label><i><b style="width:' + capacity + '%"></b></i><strong>' + capacity + '%</strong></label></div>';
    }).join("") || '<p class="database-empty">No active members.</p>';

    var statuses = ["backlog", "todo", "progress", "review", "done"];
    var overview = document.getElementById("dashboardTaskOverview");
    if (overview) overview.innerHTML = '<div class="donut" style="--progress:' + percent(completed, tasks.length) + '"><strong>' + percent(completed, tasks.length) + '%</strong><small>complete</small></div><ul>' + statuses.map(function (status) { return '<li><i class="dot ' + status + '"></i>' + formatStatus(status) + '<b>' + tasks.filter(function (task) { return task.status === status; }).length + '</b></li>'; }).join("") + '</ul>';

    var priorities = ["high", "medium", "low"];
    var priorityChart = document.getElementById("dashboardPriorityChart");
    var maxPriority = Math.max.apply(null, priorities.map(function (priority) { return tasks.filter(function (task) { return task.priority === priority && task.status !== "done"; }).length; }).concat([1]));
    if (priorityChart) priorityChart.innerHTML = priorities.map(function (priority) {
      var count = tasks.filter(function (task) { return task.priority === priority && task.status !== "done"; }).length;
      return '<span><b>' + count + '</b><i class="' + priority + '" style="height:' + Math.max(8, percent(count, maxPriority)) + '%"></i><small>' + priority.charAt(0).toUpperCase() + priority.slice(1) + '</small></span>';
    }).join("");

    var sevenDays = new Date(); sevenDays.setDate(sevenDays.getDate() + 7);
    var deadlines = tasks.filter(function (task) { return task.due_date && task.due_date >= today && new Date(task.due_date + "T12:00:00") <= sevenDays && task.status !== "done"; }).slice(0, 5);
    var deadlineNode = document.getElementById("dashboardDeadlines");
    if (deadlineNode) deadlineNode.innerHTML = deadlines.map(function (task) { return '<li><span><b>' + escapeHtml(task.title) + '</b><small>' + escapeHtml(task.project_name) + '</small></span><time>' + escapeHtml(shortDate(task.due_date)) + '</time></li>'; }).join("") || '<li class="database-empty">No deadlines in the next seven days.</li>';
  }

  function boardTaskCard(task) {
    return '<button type="button" draggable="true" class="kanban-card" data-board-task="' + Number(task.id) + '"><em class="priority ' + escapeHtml(task.priority) + '">' + escapeHtml(task.priority) + '</em><strong>' + escapeHtml(task.title) + '</strong><p>' + escapeHtml(task.description || task.project_name) + '</p><footer><span>' + escapeHtml(task.due_date ? shortDate(task.due_date) : "No deadline") + '</span><i class="avatar tiny blue-bg">' + escapeHtml((task.assignee_name || "U").charAt(0).toUpperCase()) + '</i></footer></button>';
  }

  function renderBoard() {
    var node = document.getElementById("databaseKanbanBoard");
    if (!node) return;
    var tasks = workspaceData.tasks.filter(function (task) { return selectedBoardProject === "all" || String(task.project_id) === String(selectedBoardProject); });
    var selectedProject = workspaceData.projects.find(function (project) { return String(project.id) === String(selectedBoardProject); });
    setText("boardProjectTitle", selectedProject ? selectedProject.name : "All Projects");
    setText("boardProjectSummary", tasks.length + " live tasks · drag cards between columns to update status");
    var statuses = [{ key: "backlog", label: "Backlog" }, { key: "todo", label: "To do" }, { key: "progress", label: "In progress" }, { key: "review", label: "In review" }, { key: "done", label: "Done" }];
    node.innerHTML = statuses.map(function (status) {
      var rows = tasks.filter(function (task) { return task.status === status.key; });
      return '<article class="kanban-column ' + status.key + '" data-board-status="' + status.key + '"><header><span>' + status.label + '</span><b>' + rows.length + '</b></header>' + rows.map(boardTaskCard).join("") + (rows.length ? "" : '<p class="kanban-empty">Drop a task here</p>') + '</article>';
    }).join("");
    node.querySelectorAll("[data-board-task]").forEach(function (card) {
      card.addEventListener("click", function () { var task = workspaceData.tasks.find(function (item) { return Number(item.id) === Number(card.dataset.boardTask); }); openDrawer(task || card.querySelector("strong").textContent); });
      card.addEventListener("dragstart", function () { draggedTaskId = Number(card.dataset.boardTask); });
    });
    node.querySelectorAll("[data-board-status]").forEach(function (column) {
      column.addEventListener("dragover", function (event) { event.preventDefault(); column.classList.add("drag-over"); });
      column.addEventListener("dragleave", function () { column.classList.remove("drag-over"); });
      column.addEventListener("drop", async function (event) {
        event.preventDefault(); column.classList.remove("drag-over");
        var task = workspaceData.tasks.find(function (item) { return Number(item.id) === draggedTaskId; });
        if (!task || task.status === column.dataset.boardStatus) return;
        try {
          await api("api/tasks/update.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ id: task.id, title: task.title, status: column.dataset.boardStatus, priority: task.priority, due_date: task.due_date, description: task.description || "" }) });
          await refreshWorkspace();
          showToast("Task moved", task.title + " is now " + formatStatus(column.dataset.boardStatus) + ".");
        } catch (error) { showToast("Move failed", error.message); }
      });
    });
  }

  function renderCalendar() {
    var grid = document.getElementById("databaseCalendarGrid");
    if (!grid) return;
    var year = calendarCursor.getFullYear();
    var month = calendarCursor.getMonth();
    setText("calendarMonthTitle", calendarCursor.toLocaleDateString(undefined, { month: "long", year: "numeric" }));
    var first = new Date(year, month, 1);
    var start = new Date(year, month, 1 - first.getDay());
    var today = localDateKey(new Date());
    var cells = [];
    for (var i = 0; i < 42; i += 1) {
      var day = new Date(start); day.setDate(start.getDate() + i);
      var key = localDateKey(day);
      var dayTasks = workspaceData.tasks.filter(function (task) { return task.due_date === key; });
      cells.push('<span class="' + (day.getMonth() !== month ? "muted-day " : "") + (key === today ? "today" : "") + '"><b>' + day.getDate() + '</b>' + dayTasks.slice(0, 3).map(function (task) { return '<button type="button" class="event blue-bg" data-calendar-task="' + Number(task.id) + '" title="' + escapeHtml(task.title) + '">' + escapeHtml(task.title) + '</button>'; }).join("") + (dayTasks.length > 3 ? '<small>+' + (dayTasks.length - 3) + ' more</small>' : "") + '</span>');
    }
    grid.innerHTML = cells.join("");
    grid.querySelectorAll("[data-calendar-task]").forEach(function (button) { button.addEventListener("click", function () { var task = workspaceData.tasks.find(function (item) { return Number(item.id) === Number(button.dataset.calendarTask); }); openDrawer(task || button.title); }); });
    var upcoming = workspaceData.tasks.filter(function (task) { return task.due_date && task.due_date >= today && task.status !== "done"; }).slice(0, 8);
    var upcomingNode = document.getElementById("calendarUpcoming");
    if (upcomingNode) upcomingNode.innerHTML = upcoming.map(function (task) { return '<button type="button" class="upcoming-task" data-upcoming-task="' + Number(task.id) + '"><time>' + escapeHtml(shortDate(task.due_date)) + '</time><span><b>' + escapeHtml(task.title) + '</b><small>' + escapeHtml(task.project_name) + '</small></span></button>'; }).join("") || '<p class="database-empty">No upcoming deadlines.</p>';
    if (upcomingNode) upcomingNode.querySelectorAll("[data-upcoming-task]").forEach(function (button) { button.addEventListener("click", function () { var task = workspaceData.tasks.find(function (item) { return Number(item.id) === Number(button.dataset.upcomingTask); }); if (task) openDrawer(task); }); });
  }

  function renderProjects() {
    var projects = workspaceData.projects;
    var visible = projects.filter(function (project) { return activeProjectFilter === "all" || project.status === activeProjectFilter; });
    var active = projects.filter(function (project) { return project.status === "active"; });
    var currentMonth = localDateKey(new Date()).slice(0, 7);
    setText("projectActiveCount", active.length);
    setText("projectAverageCompletion", (projects.length ? Math.round(projects.reduce(function (sum, project) { return sum + percent(project.completed_count, project.task_count); }, 0) / projects.length) : 0) + "%");
    setText("projectDueCount", projects.filter(function (project) { return project.deadline && project.deadline.slice(0, 7) === currentMonth; }).length);
    setText("projectMemberCount", workspaceData.members.length);
    var grid = document.getElementById("databaseProjectGrid");
    if (!grid) return;
    grid.innerHTML = visible.map(function (project) {
      var progress = percent(project.completed_count, project.task_count);
      return '<article class="project-card blue-card"><header><span class="project-icon" style="background:' + escapeHtml(project.color || "#4776e6") + '">' + escapeHtml(project.name.charAt(0).toUpperCase()) + '</span><div><h3>' + escapeHtml(project.name) + '</h3><em>' + escapeHtml(project.status) + '</em></div></header><p>' + escapeHtml(project.description || "No description yet.") + '</p><label>Progress <b>' + progress + '%</b><i><span style="width:' + progress + '%;background:' + escapeHtml(project.color || "#4776e6") + '"></span></i></label><footer><span><small>Deadline</small><b>' + escapeHtml(shortDate(project.deadline)) + '</b></span><span><small>Tasks</small><b>' + Number(project.task_count) + ' tasks</b></span><span><small>Owner</small><b>' + escapeHtml(project.owner_name) + '</b></span></footer><div class="project-controls"><select aria-label="Project status"><option value="active"' + (project.status === "active" ? " selected" : "") + '>Active</option><option value="complete"' + (project.status === "complete" ? " selected" : "") + '>Complete</option><option value="archived"' + (project.status === "archived" ? " selected" : "") + '>Archived</option></select><button class="secondary-button project-update-status" type="button" data-project-id="' + Number(project.id) + '">Save status</button><button class="secondary-button project-open-board" type="button" data-project-id="' + Number(project.id) + '">Open board</button></div></article>';
    }).join("") || '<p class="database-empty">No projects match this filter.</p>';
    grid.querySelectorAll(".project-update-status").forEach(function (button) {
      button.addEventListener("click", async function () {
        var status = button.parentElement.querySelector("select").value;
        if (status === "archived" && !window.confirm("Archive this project? Its tasks will remain stored and can be restored by setting the project to Active.")) return;
        try { await api("api/projects/update.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ project_id: Number(button.dataset.projectId), status: status }) }); await loadWorkspaceData(); showToast("Project updated", "The project status is now " + status + "."); } catch (error) { showToast("Update failed", error.message); }
      });
    });
    grid.querySelectorAll(".project-open-board").forEach(function (button) { button.addEventListener("click", function () { selectedBoardProject = button.dataset.projectId; populateProjectSelectors(); renderBoard(); activateView("board"); }); });
  }

  function renderTeam(query) {
    var members = workspaceData.members;
    var active = members.filter(function (member) { return member.status === "active"; });
    var average = active.length ? Math.round(active.reduce(function (sum, member) { return sum + memberCapacity(member); }, 0) / active.length) : 0;
    setText("teamActiveCount", active.length);
    setText("teamAverageCapacity", average + "%");
    setText("teamAvailableCount", active.filter(function (member) { return memberCapacity(member) < 70; }).length);
    setText("teamRoleCount", new Set(active.map(function (member) { return member.role; })).size);
    var needle = String(query || "").toLowerCase();
    var filtered = members.filter(function (member) { return !needle || (member.name + " " + member.email + " " + member.role).toLowerCase().indexOf(needle) !== -1; });
    var list = document.getElementById("databaseTeamList");
    if (list) list.innerHTML = filtered.map(function (member) {
      var capacity = memberCapacity(member);
      var status = member.status !== "active" ? "Disabled" : capacity >= 90 ? "Overloaded" : capacity >= 70 ? "Busy" : "Available";
      return '<div class="member-row"><span><i class="avatar blue-bg">' + escapeHtml(member.name.charAt(0).toUpperCase()) + '</i><b>' + escapeHtml(member.name) + '<small>' + escapeHtml(member.email) + '</small></b></span><span>' + escapeHtml(adminRoleLabel(member.role)) + '</span><em class="availability ' + (capacity >= 90 ? "focus" : capacity >= 70 ? "meeting" : "online") + '">' + status + '</em><label><i><b style="width:' + capacity + '%"></b></i>' + capacity + '%</label><strong>' + Number(member.open_tasks) + '</strong><button type="button" data-view="workload">View</button></div>';
    }).join("") || '<p class="database-empty">No members found.</p>';
    if (list) list.querySelectorAll("[data-view]").forEach(function (button) { button.addEventListener("click", function () { activateView("workload"); }); });
    var roles = ["admin", "manager", "member", "guest"];
    var roleNode = document.getElementById("teamRoleDistribution");
    if (roleNode) roleNode.innerHTML = roles.map(function (role) { var count = members.filter(function (member) { return member.role === role; }).length; return '<label><span>' + escapeHtml(adminRoleLabel(role)) + '<b>' + count + '</b></span><i><b style="width:' + percent(count, Math.max(members.length, 1)) + '%"></b></i></label>'; }).join("");
    var overloaded = active.filter(function (member) { return memberCapacity(member) >= 90; }).length;
    setText("teamInsightNote", overloaded ? overloaded + " member(s) are at 90% capacity or higher. Review assignments in Workload." : "The active team is within a healthy workload range.");
  }

  function renderWorkload() {
    var members = workspaceData.members.filter(function (member) { return member.status === "active"; });
    var average = members.length ? Math.round(members.reduce(function (sum, member) { return sum + memberCapacity(member); }, 0) / members.length) : 0;
    setText("workloadCapacity", average + "%");
    setText("workloadOverloaded", members.filter(function (member) { return memberCapacity(member) >= 90; }).length);
    setText("workloadAvailable", members.filter(function (member) { return memberCapacity(member) < 70; }).length);
    setText("workloadDueWeek", members.reduce(function (sum, member) { return sum + Number(member.due_this_week || 0); }, 0));
    var capacity = document.getElementById("databaseCapacityList");
    if (capacity) capacity.innerHTML = members.map(function (member) { var value = memberCapacity(member); return '<div><span><i class="avatar tiny purple-bg">' + escapeHtml(member.name.charAt(0).toUpperCase()) + '</i><b>' + escapeHtml(member.name) + '<small>' + Number(member.open_tasks) + ' open tasks</small></b></span><label><i><b style="width:' + value + '%"></b></i><strong>' + value + '%</strong></label></div>'; }).join("") || '<p class="database-empty">No active members.</p>';
    var openTasks = workspaceData.tasks.filter(function (task) { return task.status !== "done"; });
    var allocation = document.getElementById("databaseAllocationList");
    if (allocation) allocation.innerHTML = workspaceData.projects.filter(function (project) { return project.status === "active"; }).map(function (project) { var count = openTasks.filter(function (task) { return Number(task.project_id) === Number(project.id); }).length; return '<label><span><i style="background:' + escapeHtml(project.color || "#4776e6") + '"></i>' + escapeHtml(project.name) + '<b>' + count + ' tasks</b></span><i><b style="width:' + percent(count, Math.max(openTasks.length, 1)) + '%;background:' + escapeHtml(project.color || "#4776e6") + '"></b></i></label>'; }).join("") || '<p class="database-empty">No active project allocation.</p>';
    var heatmap = document.getElementById("databaseWorkloadHeatmap");
    if (heatmap) heatmap.innerHTML = '<div class="heatmap-head"><b>Member</b><b>Mon</b><b>Tue</b><b>Wed</b><b>Thu</b><b>Fri</b></div>' + members.map(function (member) { var open = Number(member.open_tasks || 0); return '<div class="heatmap-row"><b>' + escapeHtml(member.name) + '</b>' + [0,1,2,3,4].map(function (day) { var value = Math.floor(open / 5) + (day < open % 5 ? 1 : 0); var level = value >= 3 ? "red-soft" : value === 2 ? "yellow-soft" : value === 1 ? "blue-soft" : "green-soft"; return '<span class="' + level + '">' + value + '</span>'; }).join("") + '</div>'; }).join("");
  }

  function renderReports() {
    var from = document.getElementById("reportFromDate") ? document.getElementById("reportFromDate").value : "";
    var to = document.getElementById("reportToDate") ? document.getElementById("reportToDate").value : "";
    var tasks = workspaceData.tasks.filter(function (task) {
      var date = String(task.created_at || "").slice(0, 10);
      return (!from || date >= from) && (!to || date <= to);
    });
    setText("reportFilterSummary", from || to ? "Filtered by task creation date · " + tasks.length + " matching tasks" : "Showing all task dates");
    var today = localDateKey(new Date());
    var completedTasks = tasks.filter(function (task) { return task.status === "done"; });
    var overdue = tasks.filter(function (task) { return task.due_date && task.due_date < today && task.status !== "done"; });
    var onTime = completedTasks.filter(function (task) { return !task.due_date || String(task.updated_at).slice(0, 10) <= task.due_date; }).length;
    setText("reportCompleted", completedTasks.length);
    setText("reportProductivity", percent(completedTasks.length, tasks.length) + "%");
    setText("reportOverdue", overdue.length);
    setText("reportOnTime", percent(onTime, completedTasks.length) + "%");
    var months = [];
    for (var i = 5; i >= 0; i -= 1) { var date = new Date(); date.setDate(1); date.setMonth(date.getMonth() - i); months.push({ key: date.getFullYear() + "-" + String(date.getMonth() + 1).padStart(2, "0"), label: date.toLocaleDateString(undefined, { month: "short" }) }); }
    var counts = months.map(function (month) { return tasks.filter(function (task) { return String(task.created_at).slice(0, 7) === month.key; }).length; });
    var max = Math.max.apply(null, counts.concat([1]));
    var trend = document.getElementById("reportTrend");
    if (trend) trend.innerHTML = months.map(function (month, index) { return '<span><b>' + counts[index] + '</b><i style="height:' + Math.max(8, percent(counts[index], max)) + '%"></i><small>' + month.label + '</small></span>'; }).join("");
    setText("reportPriorityTotal", tasks.length + " total tasks");
    var priorityNode = document.getElementById("reportPriorityBreakdown");
    if (priorityNode) priorityNode.innerHTML = ["high", "medium", "low"].map(function (priority) { var count = tasks.filter(function (task) { return task.priority === priority; }).length; return '<label><span>' + priority.charAt(0).toUpperCase() + priority.slice(1) + '<b>' + count + '</b></span><i><b class="' + priority + '" style="width:' + percent(count, Math.max(tasks.length, 1)) + '%"></b></i></label>'; }).join("");
    var performance = document.getElementById("reportProjectPerformance");
    if (performance) performance.innerHTML = workspaceData.projects.slice(0, 6).map(function (project) { var value = percent(project.completed_count, project.task_count); return '<div><span><b>' + escapeHtml(project.name) + '</b><small>' + Number(project.overdue_count) + ' overdue</small></span><label><i><b style="width:' + value + '%;background:' + escapeHtml(project.color || "#4776e6") + '"></b></i><strong>' + value + '%</strong></label></div>'; }).join("") || '<p class="database-empty">No project data.</p>';
    var insights = document.getElementById("reportInsights");
    if (insights) insights.innerHTML = '<article><i class="green-bg">✓</i><span><b>' + completedTasks.length + ' tasks completed</b><small>' + percent(completedTasks.length, tasks.length) + '% overall completion</small></span></article><article><i class="red-bg">!</i><span><b>' + overdue.length + ' tasks need attention</b><small>Open tasks past their due date</small></span></article><article><i class="blue-bg">↗</i><span><b>' + workspaceData.projects.filter(function (project) { return project.status === "active"; }).length + ' active projects</b><small>' + workspaceData.members.length + ' registered members</small></span></article>';
  }

  function renderActivity() {
    var timeline = document.getElementById("databaseActivityTimeline");
    if (timeline) timeline.innerHTML = '<header class="panel-header"><div><h3>Recent events</h3><p>Newest database records first</p></div></header>' + workspaceData.activity.map(function (log) { return '<div class="activity-entry"><time>' + escapeHtml(formatAdminDate(log.created_at)) + '</time><i class="dot purple"></i><span><b>' + escapeHtml(log.action) + '</b><small>' + escapeHtml(log.details) + '</small></span><em>' + escapeHtml(log.user_name || "System") + '</em></div>'; }).join("") || '<p class="database-empty">No activity recorded.</p>';
    var weekAgo = new Date(); weekAgo.setDate(weekAgo.getDate() - 7);
    var recent = workspaceData.activity.filter(function (log) { return new Date(String(log.created_at).replace(" ", "T")) >= weekAgo; });
    var contributors = new Set(recent.map(function (log) { return log.user_name; }).filter(Boolean)).size;
    var summary = document.getElementById("databaseActivitySummary");
    if (summary) summary.innerHTML = '<h3>This week</h3><div class="summary-number"><strong>' + recent.length + '</strong><span>workspace updates</span></div><dl><dt>Task actions</dt><dd>' + recent.filter(function (log) { return /task/i.test(log.action); }).length + '</dd><dt>Contributors</dt><dd>' + contributors + '</dd><dt>Total retained</dt><dd>' + workspaceData.activity.length + '</dd></dl>';
  }

  function renderWorkspace() {
    populateProjectSelectors();
    renderSidebarProjects();
    renderDashboard();
    renderBoard();
    renderCalendar();
    renderProjects();
    renderTeam(document.getElementById("teamSearch") ? document.getElementById("teamSearch").value : "");
    renderWorkload();
    renderReports();
    renderActivity();
  }

  async function loadWorkspaceData() {
    try {
      var result = await api("api/workspace/data.php");
      workspaceData = { tasks: result.tasks || [], projects: result.projects || [], members: result.members || [], activity: result.activity || [] };
      renderWorkspace();
    } catch (error) {
      showToast("Workspace data unavailable", error.message);
    }
  }

  async function refreshWorkspace() {
    await Promise.all([loadTasks(search.value.trim()), loadWorkspaceData()]);
  }

  function renderTaskDetails(result) {
    var task = result.task;
    currentOpenTask = task;
    currentTaskDetails = { comments: result.comments || [], checklist: result.checklist || [] };
    setText("drawerTaskProject", String(task.project_name).toUpperCase());
    setText("drawerTaskTitle", task.title);
    setText("drawerTaskStatus", formatStatus(task.status));
    setText("drawerTaskDescription", task.description || "No description has been added.");
    document.getElementById("drawerTaskAssignee").innerHTML = '<i class="avatar tiny purple-bg">' + escapeHtml((task.assignee_name || "U").charAt(0).toUpperCase()) + '</i>' + escapeHtml(task.assignee_name || "Unassigned");
    setText("drawerTaskDue", shortDate(task.due_date));
    setText("fullTaskTitle", task.title);
    setText("fullTaskDescription", task.description || "No description has been added.");
    setText("detailReporter", task.creator_name || "—");
    setText("detailProject", task.project_name || "—");
    setText("detailCreated", formatAdminDate(task.created_at));
    setText("detailDue", shortDate(task.due_date));
    var detailAssignee = document.getElementById("detailAssigneeSelect");
    detailAssignee.innerHTML = workspaceData.members.filter(function (member) { return member.status === "active"; }).map(function (member) { return '<option value="' + Number(member.id) + '">' + escapeHtml(member.name) + '</option>'; }).join("");
    detailAssignee.value = String(task.assignee_id || "");

    var done = currentTaskDetails.checklist.filter(function (item) { return Number(item.is_completed) === 1; }).length;
    var total = currentTaskDetails.checklist.length;
    var progress = percent(done, total);
    setText("drawerChecklistCount", done + "/" + total);
    setText("taskChecklistCount", done + " of " + total + " complete");
    document.getElementById("drawerChecklistProgress").style.width = progress + "%";
    document.getElementById("taskChecklistProgress").style.width = progress + "%";
    var checklistHtml = currentTaskDetails.checklist.map(function (item) {
      return '<label><input class="live-checklist-item" type="checkbox" data-checklist-id="' + Number(item.id) + '"' + (Number(item.is_completed) === 1 ? " checked" : "") + ' />' + escapeHtml(item.item_text) + '</label>';
    }).join("") || '<p class="database-empty">No checklist items yet.</p>';
    document.getElementById("drawerChecklistList").innerHTML = checklistHtml;
    document.getElementById("taskChecklistList").innerHTML = checklistHtml;
    document.querySelectorAll(".live-checklist-item").forEach(function (input) {
      input.addEventListener("change", async function () {
        try {
          await api("api/tasks/checklist.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ task_id: task.id, action: "toggle", item_id: Number(input.dataset.checklistId), is_completed: input.checked }) });
          await loadTaskDetails(task.id);
        } catch (error) { showToast("Checklist update failed", error.message); }
      });
    });

    var commentsHtml = currentTaskDetails.comments.map(function (comment) {
      return '<div class="drawer-comment"><span class="avatar yellow-bg">' + escapeHtml(comment.user_name.charAt(0).toUpperCase()) + '</span><p><b>' + escapeHtml(comment.user_name) + '</b><br />' + escapeHtml(comment.comment) + '<small>' + escapeHtml(formatAdminDate(comment.created_at)) + '</small></p></div>';
    }).join("") || '<p class="database-empty">No comments yet.</p>';
    document.getElementById("drawerCommentList").innerHTML = commentsHtml;
    document.getElementById("taskCommentList").innerHTML = commentsHtml;
  }

  async function loadTaskDetails(taskId) {
    if (!taskId) return;
    try {
      var result = await api("api/tasks/details.php?id=" + encodeURIComponent(taskId));
      renderTaskDetails(result);
    } catch (error) { showToast("Task details unavailable", error.message); }
  }

  async function loadNotifications() {
    try {
      var result = await api("api/notifications/list.php");
      setText("notificationCount", result.unread || 0);
      document.getElementById("notificationDot").style.display = result.unread ? "block" : "none";
      var list = document.getElementById("notificationList");
      list.innerHTML = (result.notifications || []).map(function (item) {
        return '<button type="button" class="notification-item' + (Number(item.is_read) ? "" : " unread") + '" data-notification-id="' + Number(item.id) + '" data-notification-view="' + escapeHtml(item.link_view || "tasks") + '" data-notification-task="' + Number(item.related_task_id || 0) + '"><span>' + escapeHtml(item.message) + '</span><small>' + escapeHtml(formatAdminDate(item.created_at)) + '</small></button>';
      }).join("") || '<p class="database-empty">No notifications yet.</p>';
      list.querySelectorAll("[data-notification-id]").forEach(function (button) {
        button.addEventListener("click", async function () {
          await api("api/notifications/read.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ id: Number(button.dataset.notificationId) }) });
          setExpanded(notificationPanel, false);
          activateView(button.dataset.notificationView || "tasks");
          var task = workspaceData.tasks.find(function (item) { return Number(item.id) === Number(button.dataset.notificationTask); });
          if (task) openDrawer(task);
          loadNotifications();
        });
      });
    } catch (error) {
      document.getElementById("notificationList").innerHTML = '<p class="database-empty">' + escapeHtml(error.message) + '</p>';
    }
  }

  function renderDatabaseTasks(tasks) {
    var list = document.getElementById("databaseTaskList");
    var count = document.getElementById("databaseTaskCount");
    if (!list || !count) return;
    count.textContent = tasks.length + (tasks.length === 1 ? " task" : " tasks");
    if (!tasks.length) {
      var emptyText = activeTaskFilter === "all" ? "No saved tasks yet. Use Create task to add one." : "No tasks match this filter.";
      list.innerHTML = '<p class="database-empty">' + emptyText + '</p>';
      return;
    }
    list.innerHTML = tasks.map(function (task) {
      var due = task.due_date || "No date";
      var initial = (task.assignee_name || "U").charAt(0).toUpperCase();
      return '<div class="task-row database-task-row" data-database-task="' + Number(task.id) + '">' +
        '<button class="check database-complete" type="button" title="Mark complete">' + (task.status === "done" ? "✓" : "") + '</button>' +
        '<span><strong>' + escapeHtml(task.title) + '</strong><small>' + escapeHtml(task.project_name) + ' • ' + escapeHtml(formatStatus(task.status)) + '</small></span>' +
        '<em class="priority ' + escapeHtml(task.priority) + '">' + escapeHtml(task.priority.charAt(0).toUpperCase() + task.priority.slice(1)) + '</em>' +
        '<span class="owner"><i class="avatar tiny blue-bg">' + escapeHtml(initial) + '</i>' + escapeHtml(task.assignee_name || "Unassigned") + '</span>' +
        '<time>' + escapeHtml(due) + '</time>' +
        '<span class="task-actions"><select class="database-status" aria-label="Change task status"><option value="backlog"' + (task.status === "backlog" ? " selected" : "") + '>Backlog</option><option value="todo"' + (task.status === "todo" ? " selected" : "") + '>To do</option><option value="progress"' + (task.status === "progress" ? " selected" : "") + '>In progress</option><option value="review"' + (task.status === "review" ? " selected" : "") + '>In review</option><option value="done"' + (task.status === "done" ? " selected" : "") + '>Done</option></select><button class="database-delete" type="button" title="Delete task">Delete</button></span>' +
      '</div>';
    }).join("");

    list.querySelectorAll(".database-task-row > span:nth-child(2)").forEach(function (titleCell) {
      titleCell.addEventListener("click", function () {
        var row = titleCell.closest("[data-database-task]");
        var task = tasks.find(function (item) { return Number(item.id) === Number(row.dataset.databaseTask); });
        if (task) openDrawer(task);
      });
    });

    list.querySelectorAll(".database-complete").forEach(function (button) {
      button.addEventListener("click", async function () {
        var row = button.closest("[data-database-task]");
        var task = tasks.find(function (item) { return Number(item.id) === Number(row.dataset.databaseTask); });
        if (!task) return;
        try {
          await api("api/tasks/update.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: task.id, title: task.title, status: "done", priority: task.priority, due_date: task.due_date, description: task.description || "" })
          });
          await refreshWorkspace();
          showToast("Task completed", task.title + " is now done.");
        } catch (error) {
          showToast("Update failed", error.message);
        }
      });
    });

    list.querySelectorAll(".database-status").forEach(function (select) {
      select.addEventListener("change", async function () {
        var row = select.closest("[data-database-task]");
        var task = tasks.find(function (item) { return Number(item.id) === Number(row.dataset.databaseTask); });
        if (!task) return;
        select.disabled = true;
        try {
          await api("api/tasks/update.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ id: task.id, title: task.title, status: select.value, priority: task.priority, due_date: task.due_date, description: task.description || "" }) });
          await refreshWorkspace();
          showToast("Status updated", task.title + " is now " + formatStatus(select.value) + ".");
        } catch (error) { showToast("Update failed", error.message); select.disabled = false; }
      });
    });

    list.querySelectorAll(".database-delete").forEach(function (button) {
      button.addEventListener("click", async function () {
        var row = button.closest("[data-database-task]");
        if (!window.confirm("Delete this task?")) return;
        try {
          await api("api/tasks/delete.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: Number(row.dataset.databaseTask) })
          });
          await refreshWorkspace();
          showToast("Task deleted", "The task was removed from MySQL.");
        } catch (error) {
          showToast("Delete failed", error.message);
        }
      });
    });
  }

  async function loadTasks(searchQuery) {
    var requestNumber = ++taskRequestNumber;
    try {
      var result = await api("api/tasks/list.php?search=" + encodeURIComponent(searchQuery || ""));
      if (requestNumber !== taskRequestNumber) return;
      allDatabaseTasks = result.tasks || [];
      updateTaskCounts();
      displayFilteredTasks();
    } catch (error) {
      if (requestNumber !== taskRequestNumber) return;
      var list = document.getElementById("databaseTaskList");
      if (list) list.innerHTML = '<p class="database-empty">' + escapeHtml(error.message) + '</p>';
    }
  }

  async function checkAuthentication() {
    try {
      var result = await api("api/auth/me.php");
      setExpanded(login, !result.authenticated);
      body.style.overflow = result.authenticated ? "" : "hidden";
      if (result.authenticated) {
        updateUserProfile(result.user);
        Promise.all([refreshWorkspace(), loadNotifications()]);
      }
    } catch (error) {
      setExpanded(login, true);
      body.style.overflow = "hidden";
      showAuthMessage(document.getElementById("loginMessage"), error.message + " Open install.php to set up TaskFlow.");
    }
  }

  function setExpanded(element, isOpen) {
    if (!element) return;
    element.classList.toggle("open", isOpen);
    element.setAttribute("aria-hidden", String(!isOpen));
  }

  function closeSidebar() {
    setExpanded(sidebar, false);
    if (sidebarScrim) sidebarScrim.classList.remove("open");
  }

  function openSidebar() {
    setExpanded(sidebar, true);
    if (sidebarScrim) sidebarScrim.classList.add("open");
  }

  function closeDrawer() {
    setExpanded(drawer, false);
    if (drawerScrim) drawerScrim.classList.remove("open");
    body.style.overflow = "";
  }

  function openDrawer(taskOrTitle) {
    var task = typeof taskOrTitle === "object" ? taskOrTitle : workspaceData.tasks.find(function (item) { return item.title === taskOrTitle; });
    var title = task ? task.title : taskOrTitle;
    currentOpenTask = task || null;
    var drawerTitle = document.getElementById("drawerTaskTitle");
    if (drawerTitle && title) drawerTitle.textContent = title;
    if (task) {
      setText("drawerTaskProject", task.project_name.toUpperCase());
      setText("drawerTaskStatus", formatStatus(task.status));
      setText("drawerTaskDescription", task.description || "No description has been added.");
      document.getElementById("drawerTaskAssignee").innerHTML = '<i class="avatar tiny purple-bg">' + escapeHtml((task.assignee_name || "U").charAt(0).toUpperCase()) + '</i>' + escapeHtml(task.assignee_name || "Unassigned");
      setText("drawerTaskDue", shortDate(task.due_date));
      loadTaskDetails(task.id);
    }
    setExpanded(drawer, true);
    if (drawerScrim) drawerScrim.classList.add("open");
    body.style.overflow = "hidden";
  }

  function closeModal() {
    setExpanded(modal, false);
    body.style.overflow = "";
  }

  function openModal() {
    populateProjectSelectors();
    var assigneeSelect = document.getElementById("taskAssigneeSelect");
    if (assigneeSelect && signedInUser && assigneeSelect.querySelector('option[value="' + signedInUser.id + '"]')) assigneeSelect.value = String(signedInUser.id);
    setExpanded(modal, true);
    body.style.overflow = "hidden";
    window.setTimeout(function () {
      var input = document.getElementById("newTaskName");
      if (input) input.focus();
    }, 80);
  }

  function showToast(title, message) {
    var titleNode = toast.querySelector("b");
    var messageNode = toast.querySelector("small");
    titleNode.textContent = title;
    messageNode.textContent = message;
    toast.classList.add("show");
    window.setTimeout(function () {
      toast.classList.remove("show");
    }, 2800);
  }

  function activateView(viewName) {
    if (viewName === "admin" && (!signedInUser || signedInUser.role !== "admin")) {
      showToast("Access denied", "Only workspace administrators can open the Admin Console.");
      return;
    }
    var view = document.getElementById("view-" + viewName);
    if (!view) return;
    lastView = viewName;

    document.querySelectorAll(".app-view").forEach(function (item) {
      item.classList.toggle("active", item === view);
    });

    document.querySelectorAll("[data-view]").forEach(function (button) {
      var isMainNav = button.classList.contains("nav-item") || button.closest(".mobile-nav");
      if (isMainNav) button.classList.toggle("active", button.getAttribute("data-view") === viewName);
    });

    pageTitle.textContent = view.getAttribute("data-title") || "TaskFlow";
    breadcrumb.textContent = view.getAttribute("data-crumb") || "WORKSPACE";
    closeSidebar();
    closeDrawer();
    window.scrollTo({ top: 0, behavior: "smooth" });
    if (viewName === "admin") loadAdminTab("overview");
  }

  document.querySelectorAll("[data-view]").forEach(function (button) {
    button.addEventListener("click", function () {
      activateView(button.getAttribute("data-view"));
    });
  });

  document.querySelectorAll("[data-task-title]").forEach(function (button) {
    button.addEventListener("click", function () {
      openDrawer(button.getAttribute("data-task-title"));
    });
  });

  document.querySelectorAll("[data-open-modal]").forEach(function (button) {
    button.addEventListener("click", openModal);
  });

  document.querySelectorAll("[data-close-modal]").forEach(function (button) {
    button.addEventListener("click", closeModal);
  });

  document.querySelectorAll("[data-close-drawer]").forEach(function (button) {
    button.addEventListener("click", closeDrawer);
  });

  document.querySelectorAll("[data-close-sidebar]").forEach(function (button) {
    button.addEventListener("click", closeSidebar);
  });

  document.getElementById("menuButton").addEventListener("click", openSidebar);
  document.getElementById("moreButton").addEventListener("click", openSidebar);

  if (drawerScrim) drawerScrim.addEventListener("click", closeDrawer);
  if (sidebarScrim) sidebarScrim.addEventListener("click", closeSidebar);

  modal.addEventListener("click", function (event) {
    if (event.target === modal) closeModal();
  });

  function closeInviteModal() {
    setExpanded(inviteModal, false);
    body.style.overflow = "";
  }

  document.getElementById("inviteMemberButton").addEventListener("click", function () {
    showAuthMessage(document.getElementById("inviteMessage"), "");
    setExpanded(inviteModal, true);
    body.style.overflow = "hidden";
  });

  document.querySelectorAll("[data-close-invite]").forEach(function (button) {
    button.addEventListener("click", closeInviteModal);
  });

  inviteModal.addEventListener("click", function (event) {
    if (event.target === inviteModal) closeInviteModal();
  });

  function openProjectModal() {
    showAuthMessage(document.getElementById("projectMessage"), "");
    setExpanded(projectModal, true);
    body.style.overflow = "hidden";
    window.setTimeout(function () {
      var input = projectModal.querySelector('input[name="name"]');
      if (input) input.focus();
    }, 80);
  }

  function closeProjectModal() {
    setExpanded(projectModal, false);
    body.style.overflow = "";
  }

  document.getElementById("newProjectButton").addEventListener("click", openProjectModal);
  document.querySelectorAll("[data-close-project]").forEach(function (button) { button.addEventListener("click", closeProjectModal); });
  projectModal.addEventListener("click", function (event) { if (event.target === projectModal) closeProjectModal(); });
  document.getElementById("projectForm").addEventListener("submit", async function (event) {
    event.preventDefault();
    var form = event.currentTarget;
    var submit = form.querySelector('[type="submit"]');
    var name = form.elements.name.value.trim();
    submit.disabled = true;
    try {
      await api("api/projects/create.php", { method: "POST", body: new FormData(form) });
      form.reset();
      closeProjectModal();
      await loadWorkspaceData();
      showToast("Project created", name + " is ready for tasks.");
    } catch (error) {
      showAuthMessage(document.getElementById("projectMessage"), error.message);
    } finally { submit.disabled = false; }
  });

  document.getElementById("boardProjectSelect").addEventListener("change", function () {
    selectedBoardProject = this.value;
    renderBoard();
  });

  document.getElementById("calendarPrevious").addEventListener("click", function () { calendarCursor.setMonth(calendarCursor.getMonth() - 1); renderCalendar(); });
  document.getElementById("calendarNext").addEventListener("click", function () { calendarCursor.setMonth(calendarCursor.getMonth() + 1); renderCalendar(); });
  document.getElementById("calendarToday").addEventListener("click", function () { calendarCursor = new Date(); renderCalendar(); });

  document.querySelectorAll("[data-project-filter]").forEach(function (button) {
    button.addEventListener("click", function () {
      activeProjectFilter = button.dataset.projectFilter || "all";
      document.querySelectorAll("[data-project-filter]").forEach(function (item) { item.classList.toggle("active", item === button); });
      renderProjects();
    });
  });

  document.getElementById("teamSearch").addEventListener("input", function () { renderTeam(this.value); });
  document.getElementById("teamInviteButton").addEventListener("click", function () {
    if (!signedInUser || signedInUser.role !== "admin") { showToast("Administrator required", "Only an administrator can create member invitations."); return; }
    showAuthMessage(document.getElementById("inviteMessage"), "");
    setExpanded(inviteModal, true);
    body.style.overflow = "hidden";
  });
  document.getElementById("refreshActivity").addEventListener("click", async function () { await loadWorkspaceData(); showToast("Activity refreshed", "The newest database activity is visible."); });

  ["reportFromDate", "reportToDate"].forEach(function (id) { document.getElementById(id).addEventListener("change", renderReports); });
  document.getElementById("clearReportDates").addEventListener("click", function () {
    document.getElementById("reportFromDate").value = "";
    document.getElementById("reportToDate").value = "";
    renderReports();
  });

  document.getElementById("exportReportButton").addEventListener("click", function () {
    function csv(value) { return '"' + String(value == null ? "" : value).replace(/"/g, '""') + '"'; }
    var from = document.getElementById("reportFromDate").value;
    var to = document.getElementById("reportToDate").value;
    var reportTasks = workspaceData.tasks.filter(function (task) { var date = String(task.created_at || "").slice(0, 10); return (!from || date >= from) && (!to || date <= to); });
    var rows = [["Project", "Task", "Status", "Priority", "Assignee", "Due date", "Created"]].concat(reportTasks.map(function (task) { return [task.project_name, task.title, formatStatus(task.status), task.priority, task.assignee_name || "", task.due_date || "", String(task.created_at || "").slice(0, 10)]; }));
    var blob = new Blob([rows.map(function (row) { return row.map(csv).join(","); }).join("\r\n")], { type: "text/csv;charset=utf-8" });
    var link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "taskflow-workspace-report.csv";
    link.click();
    URL.revokeObjectURL(link.href);
  });

  document.getElementById("notificationButton").addEventListener("click", function (event) {
    event.stopPropagation();
    setExpanded(notificationPanel, !notificationPanel.classList.contains("open"));
    if (notificationPanel.classList.contains("open")) loadNotifications();
  });
  document.getElementById("closeNotifications").addEventListener("click", function () { setExpanded(notificationPanel, false); });
  document.getElementById("readAllNotifications").addEventListener("click", async function () {
    try { await api("api/notifications/read.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: "{}" }); await loadNotifications(); } catch (error) { showToast("Notification update failed", error.message); }
  });

  function closeProfileModal() { setExpanded(profileModal, false); body.style.overflow = ""; }
  document.getElementById("editProfileButton").addEventListener("click", function () {
    setExpanded(profilePopover, false);
    document.getElementById("profileNameInput").value = signedInUser ? signedInUser.name : "";
    document.getElementById("profileEmailInput").value = signedInUser ? signedInUser.email : "";
    showAuthMessage(document.getElementById("profileMessage"), "");
    setExpanded(profileModal, true);
    body.style.overflow = "hidden";
  });
  document.querySelectorAll("[data-close-profile]").forEach(function (button) { button.addEventListener("click", closeProfileModal); });
  profileModal.addEventListener("click", function (event) { if (event.target === profileModal) closeProfileModal(); });
  document.getElementById("profileForm").addEventListener("submit", async function (event) {
    event.preventDefault();
    var form = event.currentTarget;
    var submit = form.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
      var result = await api("api/profile/update.php", { method: "POST", body: new FormData(form) });
      updateUserProfile(result.user);
      form.elements.current_password.value = "";
      form.elements.new_password.value = "";
      closeProfileModal();
      await loadWorkspaceData();
      showToast("Profile updated", "Your account information was saved.");
    } catch (error) { showAuthMessage(document.getElementById("profileMessage"), error.message); } finally { submit.disabled = false; }
  });

  document.getElementById("commentForm").addEventListener("submit", async function (event) {
    event.preventDefault();
    if (!currentOpenTask) return;
    var input = document.getElementById("commentInput");
    try {
      await api("api/tasks/comment.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ task_id: currentOpenTask.id, comment: input.value.trim() }) });
      input.value = "";
      await Promise.all([loadTaskDetails(currentOpenTask.id), loadNotifications(), loadWorkspaceData()]);
      showToast("Comment added", "Your comment was saved in MySQL.");
    } catch (error) { showToast("Comment failed", error.message); }
  });

  document.getElementById("checklistForm").addEventListener("submit", async function (event) {
    event.preventDefault();
    if (!currentOpenTask) return;
    var input = document.getElementById("checklistInput");
    try {
      await api("api/tasks/checklist.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ task_id: currentOpenTask.id, action: "create", item_text: input.value.trim() }) });
      input.value = "";
      await Promise.all([loadTaskDetails(currentOpenTask.id), loadWorkspaceData()]);
      showToast("Checklist item added", "The task checklist was updated.");
    } catch (error) { showToast("Checklist failed", error.message); }
  });

  document.getElementById("detailAssigneeSelect").addEventListener("change", async function () {
    if (!currentOpenTask) return;
    var select = this;
    select.disabled = true;
    try {
      await api("api/tasks/update.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ id: currentOpenTask.id, title: currentOpenTask.title, status: currentOpenTask.status, priority: currentOpenTask.priority, due_date: currentOpenTask.due_date, description: currentOpenTask.description || "", assignee_id: Number(select.value) }) });
      await Promise.all([refreshWorkspace(), loadNotifications()]);
      await loadTaskDetails(currentOpenTask.id);
      showToast("Assignee updated", "The task assignment was saved.");
    } catch (error) { showToast("Assignment failed", error.message); select.value = String(currentOpenTask.assignee_id || ""); } finally { select.disabled = false; }
  });

  document.getElementById("inviteForm").addEventListener("submit", async function (event) {
    event.preventDefault();
    var form = event.currentTarget;
    var message = document.getElementById("inviteMessage");
    var submit = form.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
      await api("api/admin/invite.php", { method: "POST", body: new FormData(form) });
      form.reset();
      closeInviteModal();
      showToast("Invitation created", "The pending member was added to the workspace.");
      await Promise.all([loadAdminMembers(), loadAdminOverview(), loadAdminAudit()]);
    } catch (error) {
      showAuthMessage(message, error.message);
    } finally {
      submit.disabled = false;
    }
  });

  document.getElementById("openFullDetails").addEventListener("click", function () {
    var title = document.getElementById("drawerTaskTitle").textContent;
    document.getElementById("fullTaskTitle").textContent = title;
    if (currentOpenTask) setText("fullTaskDescription", currentOpenTask.description || "No description has been added.");
    activateView("task-detail");
  });

  document.getElementById("taskForm").addEventListener("submit", async function (event) {
    event.preventDefault();
    var form = event.currentTarget;
    var input = document.getElementById("newTaskName");
    var taskName = input.value.trim();
    var submit = form.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
      await api("api/tasks/create.php", { method: "POST", body: new FormData(form) });
      closeModal();
      form.reset();
      await Promise.all([refreshWorkspace(), loadNotifications()]);
      showToast("Task created", taskName + " was saved in MySQL.");
    } catch (error) {
      showToast("Task creation failed", error.message);
    } finally {
      submit.disabled = false;
    }
  });

  document.getElementById("markComplete").addEventListener("click", async function (event) {
    var button = event.currentTarget;
    if (!currentOpenTask) { showToast("Open a live task", "Select a task from My Tasks, Board, or Calendar first."); return; }
    button.disabled = true;
    try {
      await api("api/tasks/update.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ id: currentOpenTask.id, title: currentOpenTask.title, status: "done", priority: currentOpenTask.priority, due_date: currentOpenTask.due_date, description: currentOpenTask.description || "" }) });
      button.textContent = "Completed ✓";
      button.classList.remove("primary-button");
      button.classList.add("secondary-button");
      await refreshWorkspace();
      showToast("Task completed", "Progress has been updated across TaskFlow.");
    } catch (error) { showToast("Update failed", error.message); } finally { button.disabled = false; }
  });

  document.getElementById("signOutButton").addEventListener("click", async function () {
    try {
      await api("api/auth/logout.php", { method: "POST" });
    } catch (error) {
      showToast("Sign out failed", error.message);
      return;
    }
    setExpanded(profilePopover, false);
    signedInUser = null;
    setExpanded(login, true);
    body.style.overflow = "hidden";
  });

  document.querySelectorAll("[data-profile-trigger]").forEach(function (button) {
    button.addEventListener("click", function (event) {
      event.stopPropagation();
      if (window.innerWidth <= 720 && button.id !== "profileButton") openSidebar();
      profilePopover.classList.toggle("from-top", button.id !== "profileButton" && window.innerWidth > 720);
      setExpanded(profilePopover, !profilePopover.classList.contains("open"));
    });
  });

  document.addEventListener("click", function (event) {
    if (profilePopover && !profilePopover.contains(event.target)) setExpanded(profilePopover, false);
    if (notificationPanel && !notificationPanel.contains(event.target) && !event.target.closest("#notificationButton")) setExpanded(notificationPanel, false);
  });

  document.getElementById("loginForm").addEventListener("submit", async function (event) {
    event.preventDefault();
    var form = event.currentTarget;
    var message = document.getElementById("loginMessage");
    showAuthMessage(message, "");
    try {
      var result = await api("api/auth/login.php", { method: "POST", body: new FormData(form) });
      updateUserProfile(result.user);
      setExpanded(login, false);
      body.style.overflow = "";
      activateView(lastView);
      await Promise.all([refreshWorkspace(), loadNotifications()]);
      showToast("Welcome back, " + result.user.name, "Your workspace is ready.");
    } catch (error) {
      showAuthMessage(message, error.message);
    }
  });

  document.getElementById("startFreeButton").addEventListener("click", function () {
    setExpanded(login, false);
    setExpanded(register, true);
  });

  document.getElementById("backToLoginButton").addEventListener("click", function () {
    setExpanded(register, false);
    setExpanded(login, true);
  });

  document.getElementById("registerForm").addEventListener("submit", async function (event) {
    event.preventDefault();
    var form = event.currentTarget;
    var message = document.getElementById("registerMessage");
    showAuthMessage(message, "");
    try {
      var result = await api("api/auth/register.php", { method: "POST", body: new FormData(form) });
      updateUserProfile(result.user);
      setExpanded(register, false);
      body.style.overflow = "";
      await Promise.all([refreshWorkspace(), loadNotifications()]);
      showToast("Welcome, " + result.user.name, "Your account and first project are ready.");
    } catch (error) {
      showAuthMessage(message, error.message);
    }
  });

  document.querySelectorAll("[data-admin-tab]").forEach(function (button) {
    button.addEventListener("click", function () {
      var tab = button.getAttribute("data-admin-tab");
      document.querySelectorAll(".admin-tabs [data-admin-tab]").forEach(function (item) {
        item.classList.toggle("active", item.getAttribute("data-admin-tab") === tab);
      });
      document.querySelectorAll(".admin-panel").forEach(function (panel) {
        panel.classList.toggle("active", panel.id === "admin-" + tab);
      });
      loadAdminTab(tab);
    });
  });

  document.getElementById("saveAdminSettings").addEventListener("click", async function () {
    var button = this;
    button.disabled = true;
    try {
      await api("api/admin/settings.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          require_2fa: document.getElementById("require2faToggle").checked,
          allow_guest_access: document.getElementById("allowGuestToggle").checked,
          retention_days: Number(document.getElementById("retentionDays").value)
        })
      });
      showToast("Settings saved", "Workspace security policies were updated.");
      await Promise.all([loadAdminOverview(), loadAdminAudit()]);
    } catch (error) {
      showToast("Save failed", error.message);
    } finally {
      button.disabled = false;
    }
  });

  document.getElementById("exportAuditLog").addEventListener("click", function () {
    if (!adminAuditRows.length) {
      showToast("Nothing to export", "No audit records are currently loaded.");
      return;
    }
    function csv(value) { return '"' + String(value == null ? "" : value).replace(/"/g, '""') + '"'; }
    var rows = [["Date", "Action", "Details", "User", "Email", "IP"]].concat(adminAuditRows.map(function (log) {
      return [log.created_at, log.action, log.details, log.user_name || "System", log.user_email || "", log.ip_address || ""];
    }));
    var blob = new Blob([rows.map(function (row) { return row.map(csv).join(","); }).join("\r\n")], { type: "text/csv;charset=utf-8" });
    var link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "taskflow-audit-log.csv";
    link.click();
    URL.revokeObjectURL(link.href);
  });

  document.querySelectorAll(".filter-chip").forEach(function (button) {
    button.addEventListener("click", function () {
      var group = button.parentElement;
      group.querySelectorAll(".filter-chip").forEach(function (item) {
        item.classList.remove("active");
      });
      button.classList.add("active");
      if (button.hasAttribute("data-task-filter")) {
        activeTaskFilter = button.getAttribute("data-task-filter") || "all";
        displayFilteredTasks();
      }
    });
  });

  search.addEventListener("input", function () {
    var query = search.value.trim().toLowerCase();

    if (query.length > 1 && lastView !== "tasks") activateView("tasks");
    loadTasks(query);
  });

  searchButton.addEventListener("click", function () {
    var query = search.value.trim();
    activateView("tasks");
    loadTasks(query);
    search.focus();
  });

  search.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
      event.preventDefault();
      searchButton.click();
    }
    if (event.key === "Escape") {
      search.value = "";
      search.dispatchEvent(new Event("input"));
      search.blur();
    }
  });

  document.addEventListener("keydown", function (event) {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === "k") {
      event.preventDefault();
      search.focus();
    }

    if (event.key === "Escape") {
      closeDrawer();
      closeModal();
      closeInviteModal();
      closeProjectModal();
      closeProfileModal();
      setExpanded(notificationPanel, false);
      closeSidebar();
    }
  });

  window.addEventListener("resize", function () {
    if (window.innerWidth > 720) closeSidebar();
  });

  updateClock();
  window.setInterval(updateClock, 1000);
  window.setInterval(function () { if (signedInUser) loadNotifications(); }, 30000);
  updateWelcomeGreeting();
  checkAuthentication();
})();
