TASKFLOW DATABASE.SQL README
============================

This file explains the TaskFlow database.sql file, database tables,
relationships, PHP connections, installation, and testing process.


1. PROJECT TECHNOLOGY
---------------------

Frontend : HTML, CSS and JavaScript
Backend  : PHP 8.1 or newer
Database : MySQL or MariaDB
Server   : XAMPP Apache
Database connection: PHP PDO
Authentication: PHP sessions


2. DATABASE FILE
----------------

Database file location:

taskflow-php/database.sql

Database name:

taskflow

The install.php file automatically creates the taskflow database and executes
the database.sql file.


3. XAMPP INSTALLATION
---------------------

1. Extract the taskflow-php folder into:

   C:\xampp\htdocs\

2. Open the XAMPP Control Panel.
3. Start Apache.
4. Start MySQL.
5. Open this address:

   http://localhost/taskflow-php/install.php

6. Click "Install database".
7. Open the application:

   http://localhost/taskflow-php/


4. DEMO LOGIN
-------------

Email    : arafat.sarker@gmail.com
Password : taskflow2026

The password is stored securely using PHP password_hash(). Login checks the
password using password_verify(). The readable password is not stored in the
users table.


5. MANUAL PHPMYADMIN IMPORT
---------------------------

If install.php is not used:

1. Open http://localhost/phpmyadmin/
2. Create a database named taskflow.
3. Select the taskflow database.
4. Open the Import tab.
5. Select database.sql.
6. Click Import or Go.

Important: A manual database.sql import creates the table structure. The demo
account and sample tasks are added by install.php.


6. DATABASE CONNECTION
----------------------

The database settings are stored in:

config/database.php

Default XAMPP settings:

DB_HOST = 127.0.0.1
DB_PORT = 3306
DB_NAME = taskflow
DB_USER = root
DB_PASS = empty

If the MySQL root account has a password, add it to DB_PASS.


7. DATABASE.SQL CODE
--------------------

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','manager','member','guest') NOT NULL DEFAULT 'member',
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

The full database.sql file also creates these Admin Console tables:

workspace_settings
  Stores 2FA policy, guest access, and retention days.

invitations
  Stores pending, accepted, expired, and cancelled member invitations.

audit_logs
  Stores login, logout, task, member, invitation, and security actions.

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description VARCHAR(500) NULL,
  color VARCHAR(20) NOT NULL DEFAULT '#0073EA',
  status ENUM('active','complete','archived') NOT NULL DEFAULT 'active',
  deadline DATE NULL,
  owner_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_project_owner
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  project_id INT UNSIGNED NOT NULL,
  status ENUM('backlog','todo','progress','review','done')
    NOT NULL DEFAULT 'todo',
  priority ENUM('low','medium','high')
    NOT NULL DEFAULT 'medium',
  due_date DATE NULL,
  description TEXT NULL,
  assignee_id INT UNSIGNED NULL,
  created_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_task_project
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_task_assignee
    FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_task_creator
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_tasks_status (status),
  INDEX idx_tasks_assignee (assignee_id),
  INDEX idx_tasks_due_date (due_date)
) ENGINE=InnoDB;


8. USERS TABLE
--------------

Purpose: Stores accounts, login information, and profile information.

id
  Unique user ID. AUTO_INCREMENT generates the next number automatically.

name
  The user's full name. It appears in the profile bar after signup or login.

email
  The address used for login. UNIQUE prevents duplicate accounts.

password_hash
  Stores the protected password hash created by PHP.

role
  Can contain admin, manager, member, or guest. New accounts use member by
  default unless the email has a valid invitation.

status
  Can contain active or disabled. Disabled users cannot log in.

last_login_at
  Stores the user's most recent successful login date and time.

created_at
  Automatically records the account creation date and time.


9. PROJECTS TABLE
-----------------

Purpose: Stores projects owned by users.

id
  Unique project ID.

name
  Project name, such as Website Redesign or Mobile App.

description
  Explains the project goal and delivery scope.

color
  Hex colour used by the interface. The default is #0073EA.

status
  Allowed values are active, complete, and archived. Project filter buttons
  and project status controls use this value.

deadline
  Optional project deadline used by Projects metrics and cards.

owner_id
  Connects the project to users.id.

created_at
  Automatically records the project creation time.

Project relationship:

projects.owner_id -> users.id

ON DELETE CASCADE means deleting a user also deletes projects owned by that
user.


10. TASKS TABLE
---------------

Purpose: Stores all task-management records.

id
  Unique task ID.

title
  Task name entered in the Create Task form.

project_id
  Connects the task to projects.id.

status
  Allowed values:
  backlog, todo, progress, review, done

priority
  Allowed values:
  low, medium, high

due_date
  Task deadline. Used by Today, Upcoming, and Overdue filters.

description
  Additional information about the task.

assignee_id
  The user responsible for the task. This value may be NULL.

created_by
  The user who originally created the task.

created_at
  Automatically records task creation time.

updated_at
  Automatically changes whenever the task is updated.


11. TABLE RELATIONSHIPS
-----------------------

users.id    1 -------- many projects.owner_id
projects.id 1 -------- many tasks.project_id
users.id    1 -------- many tasks.assignee_id
users.id    1 -------- many tasks.created_by

Task to project:

FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE

Deleting a project deletes all related tasks.

Task to assignee:

FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL

Deleting an assigned user keeps the task but makes it unassigned.

Task to creator:

FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE

Deleting the creator deletes tasks created by that user.


12. DATABASE INDEXES
--------------------

idx_tasks_status
  Makes status filtering faster.

idx_tasks_assignee
  Makes loading a user's assigned tasks faster.

idx_tasks_due_date
  Makes Today, Upcoming, Overdue, and calendar queries faster.


13. PHP FILES AND DATABASE WORK
-------------------------------

api/auth/register.php
  Creates a user, hashes the password, and creates the user's first project.

api/auth/login.php
  Finds a user by email and verifies the submitted password.

api/auth/me.php
  Loads the logged-in user's name, email, and role.

api/auth/logout.php
  Destroys the current PHP session.

api/tasks/create.php
  Inserts a task into the tasks table.

api/tasks/list.php
  Loads and searches the authenticated user's tasks.

api/tasks/update.php
  Updates task information or marks a task as complete.

api/tasks/delete.php
  Deletes a task after checking project ownership.

api/workspace/data.php
  Loads the shared live dataset used by Dashboard, Board, Calendar, Projects,
  Team, Workload, Reports, and Activity.

api/projects/create.php
  Creates a project with a description, colour, and optional deadline.

api/projects/update.php
  Changes a project to active, complete, or archived after checking that the
  current user is the owner or an administrator.

api/admin/overview.php
  Loads live user, invitation, security, system health, overdue task, and
  seven-day activity statistics.

api/admin/members.php
  Loads registered users and pending invitations.

api/admin/invite.php
  Creates a seven-day invitation with a selected role.

api/admin/cancel-invite.php
  Cancels a pending invitation.

api/admin/update-user.php
  Changes a user's role or active/disabled status.

api/admin/settings.php
  Loads and saves workspace security and retention settings.

api/admin/audit.php
  Loads the latest 100 audit records.


14. CRUD WORKFLOW
-----------------

CREATE
  The Create Task form sends information to api/tasks/create.php.
  PHP uses INSERT INTO tasks to save the task.

READ
  api/tasks/list.php uses SELECT to load the user's tasks.

UPDATE
  api/tasks/update.php uses UPDATE tasks. The completion button changes status
  to done.

DELETE
  api/tasks/delete.php uses DELETE after checking that the user owns the
  related project.


15. FILTER WORKFLOW
-------------------

All Tasks
  Shows every task returned for the authenticated user.

Today
  Shows tasks where due_date equals the current date.

Upcoming
  Shows tasks where due_date is later than the current date.

Overdue
  Shows unfinished tasks where due_date is earlier than the current date.


16. SEARCH WORKFLOW
-------------------

Search works through live typing, the search icon, and the Enter key.

The backend searches:

- Task title
- Task description
- Project name

The PHP backend uses prepared PDO parameters with MySQL LIKE conditions.


17. EXAMPLE SQL QUERIES
-----------------------

View all users:

SELECT id, name, email, role, created_at
FROM users;

View all tasks with project and assignee names:

SELECT
  tasks.id,
  tasks.title,
  tasks.status,
  tasks.priority,
  tasks.due_date,
  projects.name AS project_name,
  users.name AS assignee_name
FROM tasks
JOIN projects ON projects.id = tasks.project_id
LEFT JOIN users ON users.id = tasks.assignee_id
ORDER BY tasks.created_at DESC;

View today's tasks:

SELECT *
FROM tasks
WHERE due_date = CURDATE();

View upcoming tasks:

SELECT *
FROM tasks
WHERE due_date > CURDATE()
ORDER BY due_date;

View unfinished overdue tasks:

SELECT *
FROM tasks
WHERE due_date < CURDATE()
  AND status <> 'done'
ORDER BY due_date;


18. SECURITY DETAILS
--------------------

- Passwords are protected with password_hash().
- Login passwords are checked with password_verify().
- PHP sessions protect authenticated API operations.
- PDO prepared statements prevent SQL injection.
- Email addresses are validated.
- Status and priority values are restricted.
- Task ownership is checked before update or deletion.
- Session IDs are regenerated after successful authentication.
- Database text is escaped before display in the browser.


19. TESTING CHECKLIST
---------------------

1. Register a new account.
2. Check that the new record appears in the users table.
3. Confirm that a project is created for the new user.
4. Log out and log in again.
5. Create tasks with past, current, and future due dates.
6. Refresh the browser and confirm the tasks remain.
7. Test All Tasks, Today, Upcoming, and Overdue.
8. Search using a task name or project name.
9. Mark a task complete.
10. Delete a task.
11. Create a new project and change its status.
12. Drag a Board card between status columns and refresh the page.
13. Navigate Calendar months and open a dated task.
14. Search Team members and review Workload calculations.
15. Refresh Activity and export the Reports CSV.
16. Verify each change in phpMyAdmin.


20. COMMON XAMPP ERRORS
-----------------------

Apache does not start:
  Port 80 may be used by another application. Stop that service or change the
  Apache port.

MySQL does not start:
  Port 3306 may be occupied. Check the MySQL log and other MySQL services.

Database connection failed:
  Check the host, port, database name, username, and password in
  config/database.php.

Unknown database:
  Open install.php or manually create a database named taskflow.

Could not find driver:
  Enable extension=pdo_mysql in php.ini and restart Apache.

Changes are not visible:
  Press Ctrl + F5 to clear cached CSS and JavaScript.

Workspace data unavailable or SQLSTATE[HY093]:
  Use the newest project files, open install.php once, and sign in again. The
  current API uses separate positional PDO parameters for owner, assignee, and
  search conditions so it works with PDO native prepared statements in XAMPP.

See the exact PHP/MySQL error:
  Open C:\xampp\apache\logs\error.log after reproducing the problem. Workspace
  and task-list database errors are recorded there while the browser receives
  a safe JSON error message.


21. FUNCTIONAL WORKSPACE OVERVIEW
---------------------------------

Dashboard:
  Shows live task totals, work in progress, overdue work, completion rate,
  active projects, recent audit activity, member workload, priority totals,
  and deadlines for the next seven days.

My Tasks:
  Loads saved tasks and supports All, Today, Upcoming, and Overdue filters.
  Search works by typing, pressing Enter, or clicking the search button. Tasks
  can be created, completed, and deleted.

Board:
  Groups live tasks into Backlog, To do, In progress, In review, and Done.
  Select one project or all projects. Dragging a card to another column saves
  the new status to MySQL.

Projects:
  Creates projects and displays task progress, deadline, owner, and status.
  Filters show all, active, complete, or archived projects. Owners and admins
  can save project status changes.

Calendar:
  Builds a real monthly calendar from task due dates. Previous, next, and
  Today buttons navigate months. Calendar task buttons open task details.

Team and Workload:
  Team loads registered users, roles, account state, open tasks, and calculated
  capacity. Team search filters the directory. Workload summarizes capacity,
  overloaded and available members, due-this-week work, project allocation,
  and a weekday distribution of open tasks.

Reports and Activity:
  Reports calculates completion, overdue, on-time delivery, six-month task
  creation, priority, and project performance information. Export report saves
  a CSV. Activity displays the latest audit records and a seven-day summary.

System States:
  The example state actions are connected to task creation, My Tasks, and the
  Calendar so each call to action leads to a usable workflow.

Data scope note:
  The current user sees tasks from projects they own or tasks assigned to them.
  Team and project summaries use workspace-wide records. The weekday heatmap
  divides each member's current open-task count across five workdays because a
  separate daily scheduling table is not part of this version.


22. FUNCTIONAL ADMIN CONSOLE
----------------------------

The Admin Console is visible only to users with the admin role. Protected PHP
endpoints also verify the role, so hiding the navigation is not the only
security control.

Overview:
  Shows live active users, pending invitations, security alerts, database
  health, overdue tasks, expiring invitations, and seven-day audit activity.

Roles and Permissions:
  Loads all users from MySQL. The admin can assign Workspace Admin, Project
  Manager, Member, or Guest roles and can activate or disable accounts. An
  admin cannot remove their own administrator access.

Invitations:
  Creates a pending invitation that expires after seven days. If the invited
  email registers before expiry, the assigned role is applied and the
  invitation becomes accepted. Pending invitations can also be cancelled.
  This local XAMPP version records invitations but does not send real email.

Security and Audit:
  Saves the 2FA policy flag, guest access flag, and retention period. The audit
  log records important authentication, task, invitation, role, and security
  operations. The visible audit records can be exported as a CSV file.

Upgrade instruction:
  After replacing an older TaskFlow version, open install.php and click
  Install database again. Existing accounts and tasks are preserved while the
  new Admin Console tables and user columns are added.


23. CORE LIMITATION UPGRADE
---------------------------

This version resolves the main local-XAMPP limitations without requiring any
external paid service.

Task assignment:
  The Create Task form loads active users from MySQL. Project owners and admins
  can also reassign an existing task from Task Details. Assignment changes
  create a notification for the new assignee.

Comments:
  Task comments are stored in task_comments. Accessible task participants can
  add and read comments. Relevant users receive an in-app notification.

Checklists:
  Checklist items are stored in checklist_items. Users can create items and
  mark them complete. Progress is calculated from the saved checklist records.

Notifications:
  notifications stores assignment and comment alerts. The notification panel
  shows unread totals, opens related tasks, and supports Mark all as read.

Profile and password:
  Users can change their name and email. A password change requires the current
  password and stores the replacement using password_hash().

Reports:
  From and To date fields filter report calculations by task creation date.
  The CSV export uses the same selected date range.

Mobile task status:
  My Tasks includes a status selector, providing a touch-friendly alternative
  to Kanban drag-and-drop.

Safe project archiving:
  Archiving asks for confirmation and keeps the project and tasks in MySQL.
  Archived projects can be restored by changing their status to Active.

New tables:
  task_comments
  checklist_items
  notifications

Important upgrade step:
  Open http://localhost/taskflow-php/install.php and click Install database
  once after copying this version. The installer creates the new tables while
  preserving existing users, projects, and tasks.
