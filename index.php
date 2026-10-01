<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
      name="description"
      content="TaskFlow responsive product prototype for desktop and mobile."
    />
    <title>TaskFlow Product Experience</title>
    <link rel="stylesheet" href="./styles.css" />
    <script src="./app.js" defer></script>
    <script src="./relations.js" defer></script>
  </head>
  <body>
    <div class="app-shell">
      <aside class="sidebar" id="sidebar" aria-label="Primary navigation">
        <div class="brand-row">
          <span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span>
          <strong>TaskFlow</strong>
          <button class="icon-button sidebar-close" data-close-sidebar aria-label="Close navigation">×</button>
        </div>

        <nav class="nav-groups">
          <section>
            <p class="nav-label">Work</p>
            <button class="nav-item active" data-view="dashboard"><span>▦</span><b>Dashboard</b></button>
            <button class="nav-item" data-view="tasks"><span>✓</span><b>My Tasks</b><em>8</em></button>
            <button class="nav-item" data-view="board"><span>▥</span><b>Board</b></button>
            <button class="nav-item" data-view="projects"><span>◇</span><b>Projects</b></button>
          </section>
          <section>
            <p class="nav-label">Time</p>
            <button class="nav-item" data-view="calendar"><span>□</span><b>Calendar</b></button>
          </section>
          <section>
            <p class="nav-label">People</p>
            <button class="nav-item" data-view="team"><span>●</span><b>Team</b></button>
            <button class="nav-item" data-view="workload"><span>≡</span><b>Workload</b></button>
          </section>
          <section>
            <p class="nav-label">Learning</p>
            <button class="nav-item" data-view="reports"><span>▥</span><b>Reports</b></button>
            <button class="nav-item" data-view="activity"><span>↗</span><b>Activity</b></button>
            <button class="nav-item" data-view="states"><span>◌</span><b>System states</b></button>
          </section>
          <section>
            <p class="nav-label">Control</p>
            <button class="nav-item" data-view="admin" data-admin-navigation><span>⌘</span><b>Admin console</b></button>
          </section>
        </nav>

        <div class="sidebar-projects" id="sidebarProjects">
          <p class="nav-label">Projects</p>
          <button data-view="board"><i class="dot blue"></i>Website Redesign</button>
          <button data-view="projects"><i class="dot purple"></i>Mobile App</button>
          <button data-view="projects"><i class="dot red"></i>Marketing Campaign</button>
          <button data-view="projects"><i class="dot yellow"></i>Product Launch</button>
          <button data-view="projects"><i class="dot green"></i>Client Portal</button>
        </div>

        
        <button class="profile-card" id="profileButton" data-profile-trigger type="button">
          <span class="avatar blue-bg" data-user-avatar>A</span>
          <span><strong data-user-name>Arafat Sarker</strong><small data-user-role>Administrator</small></span>
          <b>•••</b>
        </button>
        <div class="profile-popover" id="profilePopover" aria-hidden="true">
          <div class="profile-popover-head"><span class="avatar blue-bg" data-user-avatar>A</span><span><strong data-user-name>Arafat Sarker</strong><small data-user-email>arafat.sarker@gmail.com</small></span></div>
          <p><span>Account role</span><strong data-user-role>Administrator</strong></p>
          <button class="profile-edit" id="editProfileButton" type="button">Edit profile & password</button>
          <button class="profile-signout" id="signOutButton" type="button">Sign out</button>
        </div>
      </aside>

      <div class="sidebar-scrim" data-close-sidebar></div>

      <main class="main-shell">
        <header class="mobile-header">
          <button class="icon-button" id="menuButton" aria-label="Open navigation">☰</button>
          <span class="brand-mark small" aria-hidden="true"><i></i><i></i><i></i></span>
          <strong>TaskFlow</strong>
          <button class="avatar blue-bg" data-profile-trigger data-user-avatar aria-label="Profile">A</button>
        </header>

        <header class="topbar">
          <div>
            <p class="eyebrow" id="breadcrumb">WORKSPACE / OVERVIEW</p>
            <h1 id="pageTitle">Dashboard</h1>
          </div>
          <div class="topbar-actions">
            <label class="search-box">
              <button id="searchButton" type="button" aria-label="Search tasks">⌕</button>
              <input id="globalSearch" type="search" placeholder="Search tasks, projects..." aria-label="Search tasks and projects" />
              <kbd>⌘ K</kbd>
            </label>
            <button class="icon-button" id="notificationButton" aria-label="Notifications">◔<i class="notification-dot" id="notificationDot"></i><b class="notification-count" id="notificationCount">0</b></button>
            <button class="primary-button" data-open-modal>+ Create task</button>
            <button class="avatar blue-bg desktop-avatar" data-profile-trigger data-user-avatar aria-label="Profile">A</button>
          </div>
        </header>

        <div class="view-container" id="viewContainer">
          <section class="app-view active" id="view-dashboard" data-title="Dashboard" data-crumb="WORKSPACE / OVERVIEW">
            <div class="welcome-row">
              <div>
                <p class="eyebrow"><span id="currentDate">LOADING DATE</span><span class="live-time" id="currentTime">--:--:--</span></p>
                <h2 id="welcomeGreeting">Welcome!</h2>
                <p>Here’s what’s happening with your projects today.</p>
              </div>
              <button class="secondary-button" data-view="calendar">View calendar <span>→</span></button>
            </div>

            <div class="metric-grid four">
              <article class="metric-card accent-blue">
                <div class="metric-icon blue-soft">✓</div>
                <div><span>Total tasks</span><strong id="dashboardTotalTasks">0</strong><small>Across accessible projects</small></div>
              </article>
              <article class="metric-card accent-purple">
                <div class="metric-icon purple-soft">↗</div>
                <div><span>In progress</span><strong id="dashboardProgressTasks">0</strong><small>Currently being worked on</small></div>
              </article>
              <article class="metric-card accent-red">
                <div class="metric-icon red-soft">!</div>
                <div><span>Overdue tasks</span><strong id="dashboardOverdueTasks">0</strong><small>Needs attention</small></div>
              </article>
              <article class="metric-card accent-green">
                <div class="metric-icon green-soft">↗</div>
                <div><span>Completion</span><strong id="dashboardCompletion">0%</strong><small>Completed task percentage</small></div>
              </article>
            </div>

            <div class="dashboard-grid">
              <article class="panel board-overview">
                <header class="panel-header">
                  <div><h3>Project board</h3><p>Website Redesign</p></div>
                  <button class="text-button" data-view="board">Board view →</button>
                </header>
                <div class="mini-board" id="dashboardMiniBoard">
                  <div>
                    <p><i class="dot gray"></i>To do <span>3</span></p>
                    <button class="mini-task" data-task-title="Landing page design"><strong>Landing page design</strong><small>Website Redesign</small><span><i class="avatar tiny blue-bg">A</i> Jun 8</span></button>
                    <button class="mini-task" data-task-title="User research interview"><strong>User research interview</strong><small>Mobile App</small><span><i class="avatar tiny purple-bg">T</i> Jun 10</span></button>
                  </div>
                  <div>
                    <p><i class="dot blue"></i>In progress <span>3</span></p>
                    <button class="mini-task" data-task-title="Homepage development"><strong>Homepage development</strong><small>Website Redesign</small><span><i class="avatar tiny purple-bg">T</i> Jun 8</span></button>
                    <button class="mini-task" data-task-title="API integration"><strong>API integration</strong><small>Mobile App</small><span><i class="avatar tiny blue-bg">A</i> Jun 12</span></button>
                  </div>
                  <div>
                    <p><i class="dot yellow"></i>Review <span>3</span></p>
                    <button class="mini-task" data-task-title="Pricing page design"><strong>Pricing page design</strong><small>Website Redesign</small><span><i class="avatar tiny purple-bg">T</i> Jun 8</span></button>
                    <button class="mini-task" data-task-title="Design system update"><strong>Design system update</strong><small>Website Redesign</small><span><i class="avatar tiny yellow-bg">S</i> Jun 12</span></button>
                  </div>
                  <div>
                    <p><i class="dot green"></i>Done <span>3</span></p>
                    <button class="mini-task" data-task-title="Logo design"><strong>Logo design</strong><small>Website Redesign</small><span><i class="avatar tiny purple-bg">T</i> Jun 8</span></button>
                    <button class="mini-task" data-task-title="Style guide"><strong>Style guide</strong><small>Mobile App</small><span><i class="avatar tiny blue-bg">A</i> Jun 12</span></button>
                  </div>
                </div>
              </article>

              <aside class="panel activity-panel">
                <header class="panel-header"><div><h3>Activity feed</h3><p>Live task updates</p></div><button class="text-button" data-view="activity">View all</button></header>
                <div class="feed compact" id="dashboardActivityFeed">
                  <div><span class="avatar purple-bg">T</span><p><strong>Towhidul Islam</strong> completed <b>Homepage component</b><small>8 min ago</small></p></div>
                  <div><span class="avatar blue-bg">A</span><p><strong>Anik Bowmik</strong> commented on <b>API integration</b><small>21 min ago</small></p></div>
                  <div><span class="avatar yellow-bg">S</span><p><strong>Sadman Khan</strong> moved a task to review<small>42 min ago</small></p></div>
                  <div><span class="avatar green-bg">T</span><p><strong>Tamim</strong> created a new test<small>1 hr ago</small></p></div>
                </div>
                <div class="workload-list" id="dashboardWorkload">
                  <h4>Workload</h4>
                  <label>Arafat Sarker <span>75%</span><i><b class="w75 blue-bg"></b></i></label>
                  <label>Towhidul Islam <span>63%</span><i><b class="w63 purple-bg"></b></i></label>
                  <label>Anik Bowmik <span>88%</span><i><b class="w88 red-bg"></b></i></label>
                  <label>Sadman Khan <span>50%</span><i><b class="w50 yellow-bg"></b></i></label>
                </div>
              </aside>
            </div>

            <div class="dashboard-bottom">
              <article class="panel donut-panel">
                <header class="panel-header"><div><h3>Tasks overview</h3><p>Across all active projects</p></div></header>
                <div class="donut-row" id="dashboardTaskOverview"></div>
              </article>
              <article class="panel">
                <header class="panel-header"><div><h3>Tasks by priority</h3><p>Open tasks</p></div></header>
                <div class="bar-chart" id="dashboardPriorityChart"></div>
              </article>
              <article class="panel deadlines">
                <header class="panel-header"><div><h3>Upcoming deadlines</h3><p>Next seven days</p></div></header>
                <ul id="dashboardDeadlines"></ul>
              </article>
            </div>
          </section>

          <section class="app-view" id="view-tasks" data-title="My Tasks" data-crumb="WORK / MY TASKS">
            <div class="section-heading"><div><h2>My Tasks</h2><p>Everything assigned to you, organized by urgency.</p></div><button class="primary-button" data-open-modal>+ Add task</button></div>
            <div class="filter-row" id="taskFilters"><button class="filter-chip active" data-task-filter="all">All tasks <b id="allTaskCount">0</b></button><button class="filter-chip" data-task-filter="today">Today <b id="todayTaskCount">0</b></button><button class="filter-chip" data-task-filter="upcoming">Upcoming <b id="upcomingTaskCount">0</b></button><button class="filter-chip" data-task-filter="overdue">Overdue <b id="overdueTaskCount">0</b></button><span></span></div>
            <article class="panel table-panel database-task-panel">
              <div class="task-group-title"><span><i class="dot blue"></i>Saved in MySQL</span><b id="databaseTaskCount">0 tasks</b></div>
              <div class="task-table" id="databaseTaskList"><p class="database-empty">Sign in to load your saved tasks.</p></div>
            </article>
            <article class="panel table-panel prototype-task-panel" aria-hidden="true">
              <div class="task-group-title"><span><i class="dot red"></i>Today</span><b>3 tasks</b></div>
              <div class="task-table">
                <button class="task-row" data-task-title="Homepage wireframe"><i class="check"></i><span><strong>Homepage wireframe</strong><small>Website Redesign</small></span><em class="priority high">High</em><span class="owner"><i class="avatar tiny blue-bg">A</i>Arafat</span><time>Today</time><b>•••</b></button>
                <button class="task-row" data-task-title="API integration"><i class="check"></i><span><strong>API integration</strong><small>Mobile App</small></span><em class="priority medium">Medium</em><span class="owner"><i class="avatar tiny purple-bg">T</i>Towhidul</span><time>Today</time><b>•••</b></button>
                <button class="task-row" data-task-title="Design review"><i class="check done">✓</i><span><strong>Design review</strong><small>Website Redesign</small></span><em class="priority low">Low</em><span class="owner"><i class="avatar tiny yellow-bg">S</i>Sadman</span><time>Today</time><b>•••</b></button>
              </div>
              <div class="task-group-title"><span><i class="dot blue"></i>Upcoming</span><b>4 tasks</b></div>
              <div class="task-table">
                <button class="task-row" data-task-title="Mobile navigation"><i class="check"></i><span><strong>Mobile navigation</strong><small>Mobile App</small></span><em class="priority high">High</em><span class="owner"><i class="avatar tiny blue-bg">A</i>Arafat</span><time>Aug 11</time><b>•••</b></button>
                <button class="task-row" data-task-title="Usability test"><i class="check"></i><span><strong>Usability test</strong><small>Website Redesign</small></span><em class="priority medium">Medium</em><span class="owner"><i class="avatar tiny green-bg">T</i>Tamim</span><time>Aug 13</time><b>•••</b></button>
                <button class="task-row" data-task-title="Launch checklist"><i class="check"></i><span><strong>Launch checklist</strong><small>Marketing Campaign</small></span><em class="priority medium">Medium</em><span class="owner"><i class="avatar tiny red-bg">A</i>Anik</span><time>Aug 14</time><b>•••</b></button>
              </div>
            </article>
          </section>

          <section class="app-view" id="view-board" data-title="Board" data-crumb="WORK / BOARD">
            <div class="section-heading board-heading"><div><h2 id="boardProjectTitle">All Projects</h2><p id="boardProjectSummary">Loading tasks...</p></div><button class="primary-button" data-open-modal>+ Add task</button></div>
            <div class="view-tabs"><button class="active">Board</button><button data-view="tasks">List</button><button data-view="calendar">Calendar</button><span></span><select id="boardProjectSelect" aria-label="Select project"><option value="all">All projects</option></select></div>
            <div class="kanban-board" id="databaseKanbanBoard">
              <article class="kanban-column backlog"><header><span>Backlog</span><b>2</b></header><button class="kanban-card" data-task-title="Research analytics"><em class="priority high">High</em><strong>Research analytics</strong><p>Define the key project performance metrics.</p><footer><span>◫ 2/4</span><i class="avatar tiny purple-bg">T</i></footer></button><button class="kanban-card" data-task-title="Review old assets"><em class="priority medium">Medium</em><strong>Review old assets</strong><p>Prepare the visual archive for migration.</p><footer><span>◫ 1/3</span><i class="avatar tiny blue-bg">A</i></footer></button></article>
              <article class="kanban-column todo"><header><span>To do</span><b>3</b></header><button class="kanban-card" data-task-title="Mobile navigation"><em class="priority high">High</em><strong>Mobile navigation</strong><p>Prepare the key navigation states.</p><footer><span>◫ 2/5</span><i class="avatar tiny purple-bg">T</i></footer></button><button class="kanban-card" data-task-title="Homepage wireframe"><em class="priority medium">Medium</em><strong>Homepage wireframe</strong><p>Prepare the primary page structure.</p><footer><span>◫ 3/5</span><i class="avatar tiny blue-bg">A</i></footer></button><button class="kanban-card" data-task-title="Empty states"><em class="priority medium">Medium</em><strong>Empty states</strong><p>Cover first-use and zero-data moments.</p><footer><span>◫ 1/4</span><i class="avatar tiny purple-bg">T</i></footer></button></article>
              <article class="kanban-column progress"><header><span>In progress</span><b>2</b></header><button class="kanban-card" data-task-title="Design system"><em class="priority high">High</em><strong>Design system</strong><p>Prepare the key atomic components.</p><footer><span>◫ 4/6</span><i class="avatar tiny blue-bg">A</i></footer></button><button class="kanban-card selected" data-task-title="Create homepage wireframe"><em class="priority medium">Medium</em><strong>Create homepage wireframe</strong><p>Responsive structure for desktop and mobile.</p><footer><span>◫ 3/5</span><i class="avatar tiny purple-bg">T</i></footer></button></article>
              <article class="kanban-column review"><header><span>In review</span><b>2</b></header><button class="kanban-card" data-task-title="Pricing design"><em class="priority high">High</em><strong>Pricing design</strong><p>Prepare the key pricing comparison.</p><footer><span>◫ 4/5</span><i class="avatar tiny purple-bg">T</i></footer></button><button class="kanban-card" data-task-title="Usability test"><em class="priority medium">Medium</em><strong>Usability test</strong><p>Prepare the key project validation.</p><footer><span>◫ 3/4</span><i class="avatar tiny blue-bg">A</i></footer></button></article>
              <article class="kanban-column done"><header><span>Done</span><b>2</b></header><button class="kanban-card" data-task-title="Research"><em class="priority high">High</em><strong>Research</strong><p>Prepare the core project evidence.</p><footer><span>✓ 5/5</span><i class="avatar tiny red-bg">A</i></footer></button><button class="kanban-card" data-task-title="Wireframes"><em class="priority medium">Medium</em><strong>Wireframes</strong><p>Prepare the key project artifacts.</p><footer><span>✓ 5/5</span><i class="avatar tiny green-bg">T</i></footer></button></article>
            </div>
          </section>

          <section class="app-view" id="view-task-detail" data-title="Task Details" data-crumb="WEBSITE REDESIGN / HOMEPAGE">
            <div class="detail-toolbar"><button class="secondary-button" data-view="board">← Back to board</button><button class="primary-button" id="markComplete">Mark complete</button></div>
            <div class="task-detail-layout">
              <article class="panel task-detail-main">
                <div class="detail-badges"><em class="status in-progress">In progress</em><em class="priority high">High priority</em></div>
                <h2 id="fullTaskTitle">Create homepage wireframe</h2>
                <p class="task-lead" id="fullTaskDescription">Define the new homepage structure and prepare responsive states for desktop, tablet, and mobile.</p>
                <hr />
                <h3>Description</h3>
                <p>Build a clear homepage information hierarchy covering the hero, product value proposition, featured projects, testimonials, pricing preview, and final call-to-action. The wireframe should support responsive behavior and be ready for prototype testing.</p>
                <h3>Attachments</h3>
                <div class="attachment-grid"><button><i class="file-icon red-bg">PDF</i><span><b>homepage-notes.pdf</b><small>PDF • 2.4 MB</small></span><b>•••</b></button><button><i class="file-icon purple-bg">FIG</i><span><b>inspiration-board.fig</b><small>FIG • 6.1 MB</small></span><b>•••</b></button></div>
                <div class="checklist-heading"><h3>Checklist</h3><span id="taskChecklistCount">0 of 0 complete</span></div>
                <div class="progress-track"><i id="taskChecklistProgress" style="width:0%"></i></div>
                <div class="checklist" id="taskChecklistList"><p class="database-empty">No checklist items yet.</p></div>
                <form class="inline-create" id="checklistForm"><input id="checklistInput" required maxlength="255" placeholder="Add a checklist item..." /><button class="secondary-button" type="submit">Add item</button></form>
                <h3>Comments</h3>
                <div class="task-comments" id="taskCommentList"><p class="database-empty">No comments yet.</p></div>
                <form class="comment-box" id="commentForm"><span class="avatar blue-bg" data-user-avatar>A</span><input id="commentInput" required maxlength="2000" placeholder="Write a comment..." /><button class="primary-button" type="submit">↑</button></form>
              </article>
              <aside class="panel detail-info">
                <h3>Task information</h3>
                <div id="detailAssignmentControls"><label>Assign to team<select id="detailTeamSelect"></select></label><label>Visibility<select id="detailVisibilitySelect"><option value="project">All project members</option><option value="assignees">Assignees and managers only</option></select></label><button id="saveTaskAssignment" class="secondary-button" type="button">Save assignment and visibility</button></div><p id="taskApprovalNote"></p><dl><dt>Assignees</dt><dd><select id="detailAssigneeSelect" multiple size="4" aria-label="Change assignees"></select></dd><dt>Reporter</dt><dd id="detailReporter">—</dd><dt>Project</dt><dd id="detailProject">—</dd><dt>Created</dt><dd id="detailCreated">—</dd><dt>Due date</dt><dd class="red-text" id="detailDue">—</dd></dl>
                <h3>Tags</h3><div class="tag-list"><span>UX Design</span><span>Homepage</span><span>Wireframe</span></div>
                <h3>Recent activity</h3><div class="feed compact"><div><span class="avatar tiny yellow-bg">S</span><p><strong>Sadman Khan</strong> added a comment<small>1 hour ago</small></p></div><div><span class="avatar tiny blue-bg">A</span><p><strong>Anik Bowmik</strong> completed a subtask<small>3 hours ago</small></p></div><div><span class="avatar tiny purple-bg">T</span><p><strong>Towhidul Islam</strong> changed the due date<small>Yesterday</small></p></div></div>
              </aside>
            </div>
          </section>

          <section class="app-view" id="view-calendar" data-title="Calendar" data-crumb="TIME / CALENDAR">
            <div class="section-heading"><div><h2>Calendar</h2><p>Plan task deadlines and project milestones.</p></div><button class="primary-button" data-open-modal>+ Create dated task</button></div>
            <div class="calendar-layout">
              <article class="panel calendar-card">
                <div class="calendar-toolbar"><div><button class="filter-chip active">Month</button></div><h3 id="calendarMonthTitle">Current month</h3><div><button class="secondary-button" id="calendarToday">Today</button><button class="icon-button" id="calendarPrevious">‹</button><button class="icon-button" id="calendarNext">›</button></div></div>
                <div class="calendar-weekdays"><b>Sun</b><b>Mon</b><b>Tue</b><b>Wed</b><b>Thu</b><b>Fri</b><b>Sat</b></div>
                <div class="calendar-grid" id="databaseCalendarGrid">
                  <span class="muted-day">26</span><span class="muted-day">27</span><span class="muted-day">28</span><span class="muted-day">29</span><span class="muted-day">30</span><span class="muted-day">31</span><span>1</span>
                  <span>2</span><span>3</span><span>4<i class="event blue-bg">Design sync</i></span><span>5</span><span>6<i class="event purple-bg">Sprint review</i></span><span>7</span><span>8</span>
                  <span>9<i class="event red-bg">Launch risk</i></span><span class="today">10<i class="event green-bg">Client call</i></span><span>11</span><span>12<i class="event blue-bg">Design sync</i></span><span>13</span><span>14<i class="event yellow-bg">Team retro</i></span><span>15</span>
                  <span>16</span><span>17<i class="event purple-bg">Sprint review</i></span><span>18</span><span>19</span><span>20<i class="event red-bg">Deadline</i></span><span>21</span><span>22<i class="event green-bg">Client call</i></span>
                  <span>23</span><span>24</span><span>25<i class="event blue-bg">Design sync</i></span><span>26</span><span>27</span><span>28<i class="event red-bg">Deadline</i></span><span>29</span><span>30</span><span>31<i class="event purple-bg">Sprint review</i></span><span class="muted-day">1</span><span class="muted-day">2</span><span class="muted-day">3</span><span class="muted-day">4</span><span class="muted-day">5</span>
                </div>
              </article>
              <aside class="panel upcoming-list"><header class="panel-header"><div><h3>Upcoming</h3><p>Database deadlines</p></div></header><div id="calendarUpcoming"></div></aside>
            </div>
          </section>

          <section class="app-view" id="view-projects" data-title="Projects" data-crumb="WORK / PROJECTS">
            <div class="section-heading"><div><h2>Projects</h2><p>Track every project, owner, deadline, and delivery status.</p></div><button class="primary-button" id="newProjectButton">+ New project</button></div>
            <div class="metric-grid four"><article class="metric-card accent-blue"><div><span>Active projects</span><strong id="projectActiveCount">0</strong><small>Currently in progress</small></div></article><article class="metric-card accent-green"><div><span>Avg. completion</span><strong id="projectAverageCompletion">0%</strong><small>Across all projects</small></div></article><article class="metric-card accent-yellow"><div><span>Due this month</span><strong id="projectDueCount">0</strong><small>Project deadlines</small></div></article><article class="metric-card accent-purple"><div><span>Team members</span><strong id="projectMemberCount">0</strong><small>Registered workspace users</small></div></article></div>
            <div class="filter-row" id="projectFilters"><button class="filter-chip active" data-project-filter="all">All projects</button><button class="filter-chip" data-project-filter="active">Active</button><button class="filter-chip" data-project-filter="complete">Complete</button><button class="filter-chip" data-project-filter="archived">Archived</button><span></span></div>
            <div class="project-grid" id="databaseProjectGrid">
              <article class="project-card blue-card"><header><span class="project-icon blue-bg">W</span><div><h3>Website Redesign</h3><em>Active</em></div><button>•••</button></header><p>Redesign the public marketing website.</p><label>Progress <b>72%</b><i><span style="width:72%"></span></i></label><footer><span><small>Deadline</small><b>Aug 16</b></span><span><small>Tasks</small><b>24 tasks</b></span><span class="avatar-stack"><i class="avatar tiny blue-bg">A</i><i class="avatar tiny purple-bg">T</i><i class="avatar tiny yellow-bg">S</i></span></footer></article>
              <article class="project-card purple-card"><header><span class="project-icon purple-bg">M</span><div><h3>Mobile App</h3><em>Active</em></div><button>•••</button></header><p>Build the first TaskFlow mobile experience.</p><label>Progress <b>58%</b><i><span style="width:58%"></span></i></label><footer><span><small>Deadline</small><b>Aug 28</b></span><span><small>Tasks</small><b>31 tasks</b></span><span class="avatar-stack"><i class="avatar tiny green-bg">T</i><i class="avatar tiny blue-bg">A</i><i class="avatar tiny red-bg">A</i></span></footer></article>
              <article class="project-card red-card"><header><span class="project-icon red-bg">M</span><div><h3>Marketing Campaign</h3><em>At risk</em></div><button>•••</button></header><p>Launch the summer acquisition campaign.</p><label>Progress <b>83%</b><i><span style="width:83%"></span></i></label><footer><span><small>Deadline</small><b class="red-text">Aug 05</b></span><span><small>Tasks</small><b>18 tasks</b></span><span class="avatar-stack"><i class="avatar tiny yellow-bg">S</i><i class="avatar tiny green-bg">T</i></span></footer></article>
              <article class="project-card yellow-card"><header><span class="project-icon yellow-bg">P</span><div><h3>Product Launch</h3><em>Active</em></div><button>•••</button></header><p>Prepare launch assets and release planning.</p><label>Progress <b>46%</b><i><span style="width:46%"></span></i></label><footer><span><small>Deadline</small><b>Sep 12</b></span><span><small>Tasks</small><b>27 tasks</b></span><span class="avatar-stack"><i class="avatar tiny purple-bg">T</i><i class="avatar tiny blue-bg">A</i></span></footer></article>
              <article class="project-card green-card"><header><span class="project-icon green-bg">C</span><div><h3>Client Portal</h3><em>Active</em></div><button>•••</button></header><p>Create secure client collaboration tools.</p><label>Progress <b>64%</b><i><span style="width:64%"></span></i></label><footer><span><small>Deadline</small><b>Sep 20</b></span><span><small>Tasks</small><b>22 tasks</b></span><span class="avatar-stack"><i class="avatar tiny green-bg">T</i><i class="avatar tiny yellow-bg">S</i></span></footer></article>
              <article class="project-card blue-card"><header><span class="project-icon blue-bg">D</span><div><h3>Design System</h3><em>In maintenance</em></div><button>•••</button></header><p>Build reusable product UI foundations.</p><label>Progress <b>91%</b><i><span style="width:91%"></span></i></label><footer><span><small>Deadline</small><b>Jul 30</b></span><span><small>Tasks</small><b>16 tasks</b></span><span class="avatar-stack"><i class="avatar tiny blue-bg">A</i><i class="avatar tiny purple-bg">T</i></span></footer></article>
            </div>
          </section>

          <section class="app-view" id="view-team" data-title="Team" data-crumb="PEOPLE / TEAM">
            <div class="section-heading"><div><h2>Team</h2><p>Invite teammates by email. Assign tasks after they accept.</p></div><button class="primary-button" id="teamInviteButton">Invite teammate</button></div>
            <form class="panel" id="projectTeamForm" style="padding:20px;margin-bottom:20px"><h3>Project team</h3><p>Choose your project and enter an email. They can create an account from the invitation link.</p><div class="form-grid"><label>My project<select id="teamProjectSelect" name="project_id" required></select></label><label>Teammate email<input name="email" type="email" placeholder="teammate@example.com" required /></label></div><button class="primary-button" type="submit">Send invitation</button><div id="projectInviteResult" role="status" style="margin-top:16px" hidden><p id="projectInviteMessage"></p><label>Invitation link<input id="projectInviteLink" type="text" readonly /></label><button id="copyProjectInvite" class="secondary-button" type="button">Copy link</button></div><div id="projectPendingInvites" style="margin-top:16px"></div><div id="projectTeamList" style="margin-top:16px"></div></form>
            <section class="panel relationship-panel"><h3>Project roles and ownership</h3><label>Project<select id="relationsProjectSelect"></select></label><p id="relationsProjectSummary"></p><div id="relationsMembers"></div><button type="button" id="leaveProjectButton" class="secondary-button">Leave project</button></section>
            <section class="panel relationship-panel"><h3>Reusable teams</h3><p>Build a team from people who accepted a project invitation. Add that team to other projects you manage. Existing task assignments stay unchanged when team membership changes.</p>
              <form id="createReusableTeamForm" class="relation-row"><input name="name" required minlength="2" maxlength="150" placeholder="Team name, e.g. Design Team"><button class="primary-button" type="submit">Create team</button></form>
              <label>Team<select id="reusableTeamSelect"></select></label><div id="reusableTeamPeople"></div>
              <form id="addReusableMemberForm" class="relation-row"><input name="email" type="email" required placeholder="Accepted teammate's email"><button class="secondary-button" type="submit">Add to reusable team</button></form>
              <form id="attachReusableTeamForm" class="relation-row"><select name="project_id" id="attachTeamProject" required></select><button class="secondary-button" type="submit">Add / sync team to project</button></form>
              <button type="button" class="secondary-button" id="leaveReusableTeam">Leave reusable team</button>
            </section>
            <p id="autoRefreshStatus" class="form-help">Updates refresh automatically every 15 seconds while you are not editing.</p>

            <div class="metric-grid four"><article class="metric-card accent-blue"><div><span>Active members</span><strong id="teamActiveCount">0</strong><small>Enabled accounts</small></div></article><article class="metric-card accent-green"><div><span>Avg. workload</span><strong id="teamAverageCapacity">0%</strong><small>Based on open tasks</small></div></article><article class="metric-card accent-yellow"><div><span>Available today</span><strong id="teamAvailableCount">0</strong><small>Under 70% capacity</small></div></article><article class="metric-card accent-purple"><div><span>Roles</span><strong id="teamRoleCount">0</strong><small>Active role categories</small></div></article></div>
            <div class="team-layout">
              <article class="panel member-table"><header class="panel-header"><div><h3>Team directory</h3><p>Availability and current load</p></div><input class="small-search" id="teamSearch" placeholder="Search members..." /></header>
                <div class="member-row heading"><span>Member</span><span>Role</span><span>Status</span><span>Workload</span><span>Tasks</span><span></span></div>
                <div id="databaseTeamList"><p class="admin-loading">Loading team...</p></div><div class="member-row prototype-task-panel"><span><i class="avatar blue-bg">A</i><b>Arafat Sarker<small>arafat.sarker@taskflow.team</small></b></span><span>Product Designer</span><em class="availability online">Available</em><label><i><b class="w75 blue-bg"></b></i>75%</label><strong>24</strong><button>•••</button></div>
                <div class="member-row"><span><i class="avatar purple-bg">T</i><b>Towhidul Islam<small>towhidul@taskflow.team</small></b></span><span>UI Engineer</span><em class="availability meeting">In meeting</em><label><i><b class="w63 purple-bg"></b></i>63%</label><strong>18</strong><button>•••</button></div>
                <div class="member-row"><span><i class="avatar red-bg">A</i><b>Anik Bowmik<small>anik.bowmik@taskflow.team</small></b></span><span>Backend Engineer</span><em class="availability focus">Focus time</em><label><i><b class="w88 red-bg"></b></i>88%</label><strong>29</strong><button>•••</button></div>
                <div class="member-row"><span><i class="avatar yellow-bg">S</i><b>Sadman Khan<small>sadman@taskflow.team</small></b></span><span>Product Manager</span><em class="availability online">Available</em><label><i><b class="w50 yellow-bg"></b></i>50%</label><strong>14</strong><button>•••</button></div>
                <div class="member-row"><span><i class="avatar green-bg">T</i><b>Tamim<small>tamim@taskflow.team</small></b></span><span>QA Specialist</span><em class="availability offline">Offline</em><label><i><b class="w42 green-bg"></b></i>42%</label><strong>11</strong><button>•••</button></div>
              </article>
              <aside class="panel insight-panel"><h3>Team insights</h3><p>Work distribution by role</p><div class="insight-bars" id="teamRoleDistribution"></div><div class="insight-note" id="teamInsightNote">Loading workload insight...</div></aside>
            </div>
          </section>

          <section class="app-view" id="view-workload" data-title="Team Workload" data-crumb="PEOPLE / WORKLOAD">
            <div class="section-heading"><div><h2>Team Workload</h2><p>Balance assignments and prevent team overload.</p></div><button class="primary-button" data-view="team">Manage team</button></div>
            <div class="metric-grid four"><article class="metric-card accent-blue"><div><span>Team capacity</span><strong id="workloadCapacity">0%</strong><small>Average open-task load</small></div></article><article class="metric-card accent-red"><div><span>Overloaded</span><strong id="workloadOverloaded">0</strong><small>90% capacity or higher</small></div></article><article class="metric-card accent-green"><div><span>Available</span><strong id="workloadAvailable">0</strong><small>Under 70% capacity</small></div></article><article class="metric-card accent-yellow"><div><span>Due this week</span><strong id="workloadDueWeek">0</strong><small>Open tasks</small></div></article></div>
            <div class="capacity-grid">
              <article class="panel"><header class="panel-header"><div><h3>Capacity overview</h3><p>Based on open assigned tasks</p></div></header><div class="capacity-list" id="databaseCapacityList"></div></article>
              <article class="panel"><header class="panel-header"><div><h3>Project allocation</h3><p>Share of open tasks</p></div></header><div class="allocation-list" id="databaseAllocationList"></div></article>
            </div>
            <article class="panel weekly-workload"><header class="panel-header"><div><h3>Tasks due this week by member</h3><p>Open tasks grouped by actual due date</p></div><div class="legend"><span><i class="green-soft"></i>Light load</span><span><i class="blue-soft"></i>Healthy</span><span><i class="yellow-soft"></i>Near capacity</span><span><i class="red-soft"></i>Overloaded</span></div></header>
              <div class="heatmap" id="databaseWorkloadHeatmap"></div>
            </article>
          </section>

          <section class="app-view" id="view-reports" data-title="Reports & Analytics" data-crumb="LEARNING / REPORTS">
            <div class="section-heading"><div><h2>Reports & Analytics</h2><p>Understand delivery speed, productivity, and project health.</p></div><button class="primary-button" id="exportReportButton">Export report</button></div>
            <div class="report-filter"><label>From<input type="date" id="reportFromDate" /></label><label>To<input type="date" id="reportToDate" /></label><button class="secondary-button" id="clearReportDates" type="button">Clear dates</button><span id="reportFilterSummary">Showing all task dates</span></div>
            <div class="metric-grid four"><article class="metric-card accent-blue"><div><span>Completed tasks</span><strong id="reportCompleted">0</strong><small>Database total</small></div></article><article class="metric-card accent-green"><div><span>Completion rate</span><strong id="reportProductivity">0%</strong><small>Completed / total tasks</small></div></article><article class="metric-card accent-red"><div><span>Overdue tasks</span><strong id="reportOverdue">0</strong><small>Open past due date</small></div></article><article class="metric-card accent-yellow"><div><span>On-time delivery</span><strong id="reportOnTime">0%</strong><small>Completed by deadline</small></div></article></div>
            <div class="reports-grid">
              <article class="panel trend-panel"><header class="panel-header"><div><h3>Task creation trend</h3><p>Last six months</p></div></header><div class="report-bars" id="reportTrend"></div></article>
              <article class="panel priority-panel"><header class="panel-header"><div><h3>Priority breakdown</h3><p id="reportPriorityTotal">0 total tasks</p></div></header><div id="reportPriorityBreakdown"></div></article>
              <article class="panel project-performance"><header class="panel-header"><div><h3>Project performance</h3><p>Live progress and health</p></div></header><div id="reportProjectPerformance"></div></article>
              <article class="panel insight-cards"><header class="panel-header"><div><h3>Key insights</h3><p>Signals from your workflow</p></div></header><div id="reportInsights"></div></article>
            </div>
          </section>

          <section class="app-view" id="view-activity" data-title="Activity" data-crumb="LEARNING / ACTIVITY">
            <div class="section-heading"><div><h2>Workspace activity</h2><p>A clear database record of decisions, updates, and progress.</p></div><button class="secondary-button" id="refreshActivity">Refresh activity</button></div>
            <div class="activity-layout">
              <article class="panel activity-timeline" id="databaseActivityTimeline"><p class="admin-loading">Loading activity...</p></article>
              <aside class="panel activity-summary" id="databaseActivitySummary"><h3>This week</h3><div class="summary-number"><strong>0</strong><span>workspace updates</span></div></aside>
            </div>
          </section>

          <section class="app-view" id="view-states" data-title="System States" data-crumb="LEARNING / SYSTEM STATES">
            <div class="section-heading"><div><h2>System states</h2><p>Clear feedback keeps the workspace trustworthy.</p></div></div>
            <div class="state-grid">
              <article class="state-card"><i class="state-visual empty"><span></span></i><div><em>Empty</em><h3>No tasks yet</h3><p>Create the first task to start your board.</p><button class="primary-button" data-open-modal>Create task</button></div></article>
              <article class="state-card"><i class="state-visual loading"><span></span><span></span><span></span></i><div><em>Loading</em><h3>Preparing your workspace</h3><p>Skeleton previews signal that work is underway.</p></div></article>
              <article class="state-card"><i class="state-visual success">✓</i><div><em>Success</em><h3>Task completed</h3><p>Progress updates instantly across dashboards.</p><button class="secondary-button" data-view="tasks">View tasks</button></div></article>
              <article class="state-card"><i class="state-visual risk">!</i><div><em>Risk</em><h3>Deadline approaching</h3><p>Warnings remain specific and actionable.</p><button class="secondary-button" data-view="calendar">Review schedule</button></div></article>
            </div>
          </section>

          <section class="app-view" id="view-admin" data-title="Admin Console" data-crumb="CONTROL / WORKSPACE ADMINISTRATION" data-admin-only>
            <div class="section-heading"><div><h2>Admin Console</h2><p>Govern access, security, data, and audit controls.</p></div><button class="secondary-button" data-admin-tab="governance">Workspace settings</button></div>
            <div class="admin-tabs"><button class="active" data-admin-tab="overview">Overview</button><button data-admin-tab="roles">Roles & permissions</button><button data-admin-tab="governance">Security & audit</button></div>
            <div class="admin-panel active" id="admin-overview">
              <div class="admin-message" id="adminMessage" role="status"></div>
              <div class="metric-grid four"><article class="metric-card accent-blue"><div><span>Active users</span><strong id="adminActiveUsers">0</strong><small>Enabled workspace accounts</small></div></article><article class="metric-card accent-purple"><div><span>Pending invites</span><strong id="adminPendingInvites">0</strong><small>Waiting for acceptance</small></div></article><article class="metric-card accent-red"><div><span>Security alerts</span><strong id="adminSecurityAlerts">0</strong><small>Policies requiring review</small></div></article><article class="metric-card accent-green"><div><span>System health</span><strong id="adminSystemHealth">—</strong><small>Database connection status</small></div></article></div>
              <div class="admin-overview-grid"><article class="panel"><header class="panel-header"><div><h3>Workspace activity</h3><p>Database actions during the last 7 days</p></div></header><div class="bar-chart admin-chart" id="adminActivityChart"></div></article><aside class="admin-attention"><h3>Requires attention</h3><div><i class="red-bg">!</i><span><b>2FA disabled</b><small id="admin2faAttention">0 members</small></span><button data-admin-tab="governance">Review ›</button></div><div><i class="yellow-bg">!</i><span><b>Invites expiring</b><small id="adminInviteAttention">0 invites</small></span><button data-admin-tab="roles">Review ›</button></div><div><i class="purple-bg">!</i><span><b>Overdue tasks</b><small id="adminOverdueAttention">0 tasks</small></span><button data-view="tasks">Review ›</button></div></aside></div>
            </div>
            <div class="admin-panel" id="admin-roles">
              <article class="panel member-table"><header class="panel-header"><div><h3>Members and invitations</h3><p>Live workspace access and role assignment</p></div><button class="primary-button" id="inviteMemberButton">Invite member</button></header><div class="member-row heading"><span>Member</span><span>Role</span><span>Status</span><span>Last login / expiry</span><span>Save</span></div><div id="adminMemberList"><p class="admin-loading">Loading members...</p></div></article>
              <div class="role-cards"><article><i class="blue-bg">01</i><h3>Workspace Admin</h3><p>Full workspace, billing, and security access.</p></article><article><i class="purple-bg">02</i><h3>Project Manager</h3><p>Create projects, assign work, manage people.</p></article><article><i class="green-bg">03</i><h3>Member</h3><p>Complete assigned work and collaborate.</p></article><article><i class="yellow-bg">04</i><h3>Guest</h3><p>Limited access to invited projects.</p></article></div>
            </div>
            <div class="admin-panel" id="admin-governance">
              <div class="governance-grid"><article class="governance-card"><i class="blue-bg">✓</i><em>Security</em><h3>2FA enforcement</h3><p>Planned; two-factor authentication is not implemented.</p><label class="toggle"><input id="require2faToggle" type="checkbox" disabled /><span></span></label></article><article class="governance-card"><i class="purple-bg">◆</i><em>Data</em><h3>Retention rules</h3><p>Planned; automatic deletion is not implemented.</p><label class="setting-field">Days<input id="retentionDays" type="number" min="30" max="3650" value="365" disabled /></label></article><article class="governance-card"><i class="green-bg">↗</i><em>Access</em><h3>Guest access</h3><p>Planned; guest restrictions are not implemented.</p><label class="toggle"><input id="allowGuestToggle" type="checkbox" disabled /><span></span></label></article></div>
              <div class="settings-actions"><button class="primary-button" id="saveAdminSettings" disabled>Settings planned</button></div>
              <article class="panel audit-log"><header class="panel-header"><div><h3>Recent audit activity</h3><p>Traceable login, member, task, and security changes</p></div><button class="secondary-button" id="exportAuditLog">Export CSV</button></header><div id="adminAuditList"><p class="admin-loading">Loading audit activity...</p></div></article>
            </div>
          </section>
        </div>

        <nav class="mobile-nav" aria-label="Mobile navigation">
          <button class="active" data-view="dashboard"><span>▦</span>Home</button>
          <button data-view="tasks"><span>✓</span>Tasks</button>
          <button data-view="board"><span>▥</span>Board</button>
          <button data-view="calendar"><span>□</span>Calendar</button>
          <button id="moreButton"><span>•••</span>More</button>
        </nav>
      </main>
    </div>

    <aside class="task-drawer" id="taskDrawer" aria-hidden="true" aria-label="Task details">
      <header><em class="status in-progress" id="drawerTaskStatus">In progress</em><button class="icon-button" data-close-drawer aria-label="Close task details">×</button></header>
      <p class="eyebrow" id="drawerTaskProject">WEBSITE REDESIGN</p>
      <h2 id="drawerTaskTitle">Create homepage wireframe</h2>
      <p id="drawerTaskDescription">Design the new homepage structure and prepare desktop, tablet, and mobile states.</p>
      <div class="drawer-meta"><span><small>Assignee</small><b id="drawerTaskAssignee"><i class="avatar tiny purple-bg">T</i>Towhidul Islam</b></span><span><small>Due date</small><b id="drawerTaskDue">Jul 28, 2026</b></span></div>
      <div class="checklist-heading"><h3>Checklist</h3><span id="drawerChecklistCount">0/0</span></div>
      <div class="progress-track"><i id="drawerChecklistProgress" style="width:0%"></i></div>
      <div class="checklist" id="drawerChecklistList"><p class="database-empty">No checklist items yet.</p></div>
      <h3>Comments</h3>
      <div id="drawerCommentList"><p class="database-empty">No comments yet.</p></div>
      <button class="primary-button full-width" id="openFullDetails">Open full details</button>
    </aside>
    <div class="drawer-scrim" data-close-drawer></div>

    <div class="modal-backdrop" id="taskModal" aria-hidden="true">
      <form class="modal-card" id="taskForm">
        <header><div><p class="eyebrow">QUICK CREATE</p><h2>Create a task</h2></div><button type="button" class="icon-button" data-close-modal aria-label="Close">×</button></header>
        <label>Task name<input id="newTaskName" name="task_name" required placeholder="What needs to be done?" /></label>
        <div class="form-grid"><label>Project<select name="project_id" id="taskProjectSelect"><option value="">Website Redesign</option></select></label><label>Status<select name="status"><option>To do</option><option>In progress</option><option>In review</option></select></label></div>
        <div class="form-grid"><label>Priority<select name="priority"><option>Medium</option><option>High</option><option>Low</option></select></label><label>Due date<input name="due_date" type="date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" /></label></div>
        <label>Assign to team<select name="team_id" id="taskTeamSelect"><option value="">Choose people individually</option></select></label>
        <label>Assignees<select name="assignee_ids[]" id="taskAssigneeSelect" multiple size="4" required></select><small>Use Ctrl / Command to select multiple people.</small></label>
        <label>Visibility<select name="visibility"><option value="project">All project members</option><option value="assignees">Assignees and project managers only</option></select></label>
        <label>Description<textarea name="description" placeholder="Add useful context for the team."></textarea></label>
        <footer><button type="button" class="secondary-button" data-close-modal>Cancel</button><button class="primary-button" type="submit">Create task</button></footer>
      </form>
    </div>

    <div class="modal-backdrop" id="inviteModal" aria-hidden="true">
      <form class="modal-card" id="inviteForm">
        <header><div><p class="eyebrow">ADMIN ACCESS</p><h2>Invite a member</h2></div><button type="button" class="icon-button" data-close-invite aria-label="Close">×</button></header>
        <div class="auth-message" id="inviteMessage" role="alert"></div>
        <label>Full name<input name="name" required minlength="2" placeholder="Member name" /></label>
        <label>Email address<input name="email" type="email" required placeholder="member@example.com" /></label>
        <label>Workspace role<select name="role"><option value="member">Member</option><option value="manager">Project Manager</option><option value="guest">Guest</option><option value="admin">Workspace Admin</option></select></label>
        <footer><button type="button" class="secondary-button" data-close-invite>Cancel</button><button class="primary-button" type="submit">Create invitation</button></footer>
      </form>
    </div>

    <div class="modal-backdrop" id="projectModal" aria-hidden="true">
      <form class="modal-card" id="projectForm">
        <header><div><p class="eyebrow">NEW WORKSPACE PROJECT</p><h2>Create a project</h2></div><button type="button" class="icon-button" data-close-project aria-label="Close">×</button></header>
        <div class="auth-message" id="projectMessage" role="alert"></div>
        <label>Project name<input name="name" required minlength="2" placeholder="Example: Mobile App" /></label>
        <label>Description<textarea name="description" placeholder="What will this project deliver?"></textarea></label>
        <div class="form-grid"><label>Project colour<input name="color" type="color" value="#0073EA" /></label><label>Deadline<input name="deadline" type="date" /></label></div>
        <footer><button type="button" class="secondary-button" data-close-project>Cancel</button><button class="primary-button" type="submit">Create project</button></footer>
      </form>
    </div>

    <div class="notification-panel" id="notificationPanel" aria-hidden="true">
      <header><div><p class="eyebrow">YOUR UPDATES</p><h2>Notifications</h2></div><button class="icon-button" id="closeNotifications" type="button">×</button></header>
      <button class="notification-read-all" id="readAllNotifications" type="button">Mark all as read</button>
      <div id="notificationList"><p class="database-empty">No notifications yet.</p></div>
    </div>

    <div class="modal-backdrop" id="profileModal" aria-hidden="true">
      <form class="modal-card" id="profileForm">
        <header><div><p class="eyebrow">ACCOUNT SETTINGS</p><h2>Edit profile</h2></div><button type="button" class="icon-button" data-close-profile>×</button></header>
        <div class="auth-message" id="profileMessage" role="alert"></div>
        <label>Full name<input name="name" id="profileNameInput" required minlength="2" /></label>
        <label>Email address<input name="email" id="profileEmailInput" type="email" required /></label>
        <label id="profileRoleRow">My role<select name="account_type" id="profileRoleSelect"><option value="member">Team Member</option><option value="manager">Project Manager</option></select></label>
        <hr />
        <p class="form-help">Leave the password fields empty if you do not want to change your password.</p>
        <label>Current password<input name="current_password" type="password" autocomplete="current-password" /></label>
        <label>New password<input name="new_password" type="password" minlength="8" autocomplete="new-password" /></label>
        <footer><button type="button" class="secondary-button" data-close-profile>Cancel</button><button class="primary-button" type="submit">Save profile</button></footer>
      </form>
    </div>

    <section class="login-screen" id="loginScreen" aria-hidden="true">
      <div class="login-value">
        <div class="brand-row light"><span class="brand-mark"><i></i><i></i><i></i></span><strong>TaskFlow</strong></div>
        <div><h1>Organize work.<br />Move faster together.</h1><p>A lightweight project workspace for teams to plan, collaborate, and maintain productive work habits.</p></div>
        <ul><li><i>✓</i><span><b>Simple Kanban boards</b><small>Create, assign, and move tasks in seconds.</small></span></li><li><i>↻</i><span><b>Live collaboration</b><small>Comments, files, activity, and team updates.</small></span></li><li><i>★</i><span><b>Productivity streaks</b><small>Build momentum and celebrate progress.</small></span></li></ul>
        <div class="login-preview"><span><b>Website Redesign</b><small>24 tasks • 68% complete</small></span><div><i><b>To do</b><small>Homepage</small><small>Mobile nav</small></i><i><b>In progress</b><small>Homepage</small><small>Mobile nav</small></i><i><b>Done</b><small>Homepage</small><small>Mobile nav</small></i></div></div>
      </div>
      <div class="login-form-wrap">
        <form class="login-form" id="loginForm">
          <div class="brand-row mobile-login-brand"><span class="brand-mark"><i></i><i></i><i></i></span><strong>TaskFlow</strong></div>
          <h2>Welcome back</h2><p>Sign in to continue managing your team’s work.</p>
          <div class="social-login"><button type="button">G&nbsp; Continue with Google</button><button type="button">▦&nbsp; Continue with Microsoft</button></div>
          <div class="divider"><span>OR CONTINUE WITH EMAIL</span></div>
          <div class="auth-message" id="loginMessage" role="alert"></div>
          <label>Email address<input id="loginEmail" name="email" type="email" value="arafat.sarker@gmail.com" required /></label>
          <label>Password<input id="loginPassword" name="password" type="password" value="taskflow2026" required /></label>
          <div class="remember-row"><label><input type="checkbox" checked />Remember me</label><button type="button">Forgot password?</button></div>
          <button class="primary-button full-width" type="submit">Sign in</button>
          <p class="login-footer">Don’t have an account? <button id="startFreeButton" type="button">Start free</button></p>
        </form>
      </div>
    </section>

    <section class="login-screen" id="registerScreen" aria-hidden="true">
      <div class="login-value">
        <div class="brand-row light"><span class="brand-mark"><i></i><i></i><i></i></span><strong>TaskFlow</strong></div>
        <div><h1>Start planning.<br />Build momentum.</h1><p>Create your workspace and save tasks securely in your local MySQL database.</p></div>
        <ul><li><i>✓</i><span><b>Real PHP account</b><small>Your password is safely hashed before storage.</small></span></li><li><i>↻</i><span><b>Persistent tasks</b><small>Your work remains available after refreshing.</small></span></li><li><i>★</i><span><b>XAMPP ready</b><small>Runs locally using Apache, PHP, and MySQL.</small></span></li></ul>
      </div>
      <div class="login-form-wrap">
        <form class="login-form" id="registerForm">
          <div class="brand-row mobile-login-brand"><span class="brand-mark"><i></i><i></i><i></i></span><strong>TaskFlow</strong></div>
          <h2>Create account</h2><p>Set up your personal TaskFlow workspace.</p>
          <div class="auth-message" id="registerMessage" role="alert"></div>
          <label>Full name<input name="name" type="text" placeholder="Your name" required minlength="2" /></label>
          <label>Email address<input name="email" type="email" placeholder="you@example.com" required /></label>
          <label>Password<input name="password" type="password" placeholder="At least 8 characters" required minlength="8" /></label>
          <label>I will work as<select name="account_type"><option value="member">Team Member</option><option value="manager">Project Manager</option></select></label>
          <button class="primary-button full-width" type="submit">Create account</button>
          <p class="login-footer">Already have an account? <button id="backToLoginButton" type="button">Sign in</button></p>
        </form>
      </div>
    </section>

    <div class="toast" id="toast" role="status"><i>✓</i><span><b>Task created</b><small>Your board has been updated.</small></span></div>
  </body>
</html>
