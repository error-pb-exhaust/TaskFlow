# TaskFlow Database and Backend Guide

This document explains how the TaskFlow PHP/MySQL version works, how to install it in XAMPP, and how each database feature connects to the user interface.

## 1. Technology Used

- Frontend: HTML5, CSS3 and JavaScript
- Backend: PHP 8.1 or newer
- Database: MySQL or MariaDB
- Local server: XAMPP Apache
- Database connection: PHP PDO
- Authentication: PHP sessions

## 2. XAMPP Installation

1. Install and open XAMPP.
2. Start the **Apache** and **MySQL** modules.
3. Extract the `taskflow-php` folder into:

   ```text
   C:\xampp\htdocs\
   ```

4. Open the database installer:

   ```text
   http://localhost/taskflow-php/install.php
   ```

5. Select **Install database**.
6. Open the application:

   ```text
   http://localhost/taskflow-php/
   ```

The installer creates the `taskflow` database, all required tables, a demonstration account, a project, and sample tasks.

## 3. Demonstration Account

```text
Email: arafat.sarker@gmail.com
Password: taskflow2026
```

The password is not stored as readable text. PHP converts it into a secure hash using `password_hash()` and checks it using `password_verify()`.

## 4. Database Configuration

The database settings are located in:

```text
config/database.php
```

Default XAMPP configuration:

```php
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'taskflow';
const DB_USER = 'root';
const DB_PASS = '';
```

If the MySQL `root` account has a password, place it in `DB_PASS`.

## 5. Database Tables

### `users`

Stores registered users and authentication information.

| Column | Type | Purpose |
|---|---|---|
| `id` | Integer | Unique user ID and primary key |
| `name` | Varchar | User's full name |
| `email` | Varchar | Unique login email address |
| `password_hash` | Varchar | Securely hashed password |
| `role` | Enum | `admin` or `member` |
| `created_at` | Timestamp | Account creation date and time |

### `projects`

Stores projects owned by users.

| Column | Type | Purpose |
|---|---|---|
| `id` | Integer | Unique project ID |
| `name` | Varchar | Project name |
| `color` | Varchar | Project interface colour |
| `owner_id` | Integer | User who owns the project |
| `created_at` | Timestamp | Project creation time |

### `tasks`

Stores all persistent TaskFlow tasks.

| Column | Type | Purpose |
|---|---|---|
| `id` | Integer | Unique task ID |
| `title` | Varchar | Task name |
| `project_id` | Integer | Related project |
| `status` | Enum | `backlog`, `todo`, `progress`, `review` or `done` |
| `priority` | Enum | `low`, `medium` or `high` |
| `due_date` | Date | Task deadline |
| `description` | Text | Detailed task information |
| `assignee_id` | Integer | Assigned user |
| `created_by` | Integer | User who created the task |
| `created_at` | Timestamp | Task creation time |
| `updated_at` | Timestamp | Latest update time |

## 6. Table Relationships

- One user can own many projects.
- One project can contain many tasks.
- One user can be assigned many tasks.
- One user can create many tasks.
- Deleting a project deletes its related tasks.
- Deleting an assigned user changes the task assignment to `NULL`.

```text
users (1) -------- (many) projects
projects (1) ----- (many) tasks
users (1) -------- (many) assigned tasks
users (1) -------- (many) created tasks
```

## 7. Backend File Structure

```text
taskflow-php/
├── index.php
├── install.php
├── database.sql
├── app.js
├── styles.css
├── config/
│   ├── bootstrap.php
│   └── database.php
└── api/
    ├── auth/
    │   ├── login.php
    │   ├── logout.php
    │   ├── me.php
    │   └── register.php
    └── tasks/
        ├── create.php
        ├── delete.php
        ├── list.php
        └── update.php
```

## 8. Authentication Workflow

### Registration

1. The user selects **Start free**.
2. The user enters a name, email address, and password.
3. JavaScript sends the form to `api/auth/register.php`.
4. PHP validates the information.
5. The password is hashed.
6. The user and a first project are inserted into MySQL.
7. A PHP session is created.
8. The user's name, email, role, and avatar appear in the profile bar.

### Login

1. The login form sends email and password to `api/auth/login.php`.
2. PHP finds the user by email.
3. `password_verify()` checks the password.
4. PHP stores the user's ID in `$_SESSION['user_id']`.
5. The interface loads the user's database tasks.

### Logout

`api/auth/logout.php` clears and destroys the current PHP session. The login screen is then displayed again.

## 9. Task CRUD Workflow

CRUD means Create, Read, Update and Delete.

### Create

- Interface: **Create task** modal
- PHP file: `api/tasks/create.php`
- Database operation: `INSERT INTO tasks`

The task name, project, status, priority, due date, and description are submitted through `FormData` and saved using a prepared PDO statement.

### Read

- Interface: **My Tasks** database list
- PHP file: `api/tasks/list.php`
- Database operation: `SELECT`

Only projects owned by the logged-in user or tasks assigned to that user are returned.

### Update

- Interface: Task completion checkbox
- PHP file: `api/tasks/update.php`
- Database operation: `UPDATE tasks`

Selecting the completion button changes the task status to `done`.

### Delete

- Interface: **Delete** button beside a saved task
- PHP file: `api/tasks/delete.php`
- Database operation: `DELETE`

The backend confirms that the logged-in user owns the project before deleting its task.

## 10. Working Task Filters

The following buttons filter tasks without refreshing the page:

- **All tasks:** Shows every task returned for the user.
- **Today:** Shows tasks with today's due date.
- **Upcoming:** Shows tasks with a due date after today.
- **Overdue:** Shows unfinished tasks with a due date before today.

Each button displays a count calculated from the currently loaded database records.

## 11. Working Search

The search feature works through:

- Live typing
- Selecting the search icon
- Pressing Enter
- `Ctrl + K` or `Command + K` to focus the search field
- Escape to clear the search

The backend searches these database fields:

- Task title
- Task description
- Project name

The search query uses a prepared PDO statement and MySQL `LIKE` conditions.

## 12. Current Date, Time and User Profile

JavaScript reads the user's computer time and updates the dashboard clock every second. It also changes the greeting between **Good morning**, **Good afternoon**, and **Good evening**.

After login or signup, the profile bar displays:

- User's first-letter avatar
- Full name
- Email address
- Account role
- Sign-out option

## 13. API Response Format

Successful response example:

```json
{
  "success": true,
  "message": "Task created successfully.",
  "task_id": 4
}
```

Error response example:

```json
{
  "success": false,
  "message": "Please sign in first."
}
```

## 14. Security Used

- Password hashing and verification
- PHP session authentication
- Prepared PDO statements against SQL injection
- Email validation
- Server-side field validation
- Task ownership checks
- Restricted task status and priority values
- HTML escaping when database content is displayed
- Session ID regeneration after login and registration

For a public production server, also add HTTPS, CSRF protection, rate limiting, environment variables, email verification, password reset, and production error logging.

## 15. Testing Checklist

1. Register a new account.
2. Confirm the new name and email appear in the profile panel.
3. Sign out and log in using the new account.
4. Create tasks with past, present, and future dates.
5. Confirm the task remains after refreshing the browser.
6. Test All, Today, Upcoming, and Overdue filters.
7. Search using a task title and project name.
8. Mark a task as complete.
9. Delete a task.
10. Verify the changes in phpMyAdmin.

## 16. Viewing Data in phpMyAdmin

Open:

```text
http://localhost/phpmyadmin/
```

Select the `taskflow` database and open the `users`, `projects`, or `tasks` table. Use the **Browse** tab to view stored records.

## 17. Common XAMPP Problems

### Apache does not start

Port 80 may already be used by IIS, Skype, or another web server. Stop the conflicting service or change Apache's port.

### MySQL does not start

Port 3306 may be occupied. Check the XAMPP logs and stop another MySQL service if necessary.

### Database connection failed

Check `config/database.php`. Confirm the database name, username, password, port, and MySQL status.

### `could not find driver`

Enable the PHP PDO MySQL extension in `php.ini`:

```text
extension=pdo_mysql
```

Restart Apache after changing `php.ini`.

### Changes are not visible

Use `Ctrl + F5` to perform a hard refresh and clear the browser's cached CSS and JavaScript.

## 18. Project Limitation

Authentication, user profiles, search, task filters, and task CRUD operations use the real database. The analytics, reports, team directory, calendar examples, and some admin statistics remain demonstration interface content.
