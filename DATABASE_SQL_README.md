# `database.sql` Detailed README

This README explains the purpose and structure of TaskFlow's `database.sql` file. The SQL file creates the tables, relationships, validation rules, and indexes required by the PHP backend.

## File Location

```text
taskflow-php/database.sql
```

The automatic `install.php` script reads this file and executes it when **Install database** is selected.

## Database Name

The application uses this database:

```text
taskflow
```

The database name is configured in `config/database.php`:

```php
const DB_NAME = 'taskflow';
```

## How to Import Manually

If the automatic installer is not used:

1. Start Apache and MySQL in XAMPP.
2. Open `http://localhost/phpmyadmin/`.
3. Create a database named `taskflow`.
4. Select the `taskflow` database.
5. Open the **Import** tab.
6. Select `database.sql`.
7. Select **Import** or **Go**.

## Complete SQL Structure

```sql
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','member') NOT NULL DEFAULT 'member',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  color VARCHAR(20) NOT NULL DEFAULT '#0073EA',
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
```

## Table 1: `users`

The `users` table stores login accounts and profile information.

| Column | Explanation |
|---|---|
| `id` | Automatically generated unique user number |
| `name` | User's full name shown in the profile bar |
| `email` | Unique address used for login |
| `password_hash` | Encrypted-style password hash created by PHP |
| `role` | Determines whether the user is an administrator or member |
| `created_at` | Automatically records when registration occurred |

### Important Rules

- `AUTO_INCREMENT` automatically creates the next ID.
- `PRIMARY KEY` uniquely identifies each user.
- `NOT NULL` means the value is required.
- `UNIQUE` prevents two accounts from using the same email.
- The default role is `member`.
- Plain-text passwords must never be stored in this table.

## Table 2: `projects`

The `projects` table stores the projects created or owned by users.

| Column | Explanation |
|---|---|
| `id` | Unique project number |
| `name` | Project title, such as Website Redesign |
| `color` | Hex colour used by the interface |
| `owner_id` | ID of the user who owns the project |
| `created_at` | Project creation timestamp |

### Project Foreign Key

```sql
FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
```

This connects every project to a valid user. `ON DELETE CASCADE` means deleting a user also deletes that user's projects.

## Table 3: `tasks`

The `tasks` table stores all task-management information.

| Column | Explanation |
|---|---|
| `id` | Unique task number |
| `title` | Task name |
| `project_id` | Project containing the task |
| `status` | Current workflow position |
| `priority` | Low, medium, or high importance |
| `due_date` | Task deadline |
| `description` | Additional task details |
| `assignee_id` | User responsible for completing the task |
| `created_by` | User who originally created the task |
| `created_at` | Creation date and time |
| `updated_at` | Automatically updated after a change |

### Allowed Status Values

```text
backlog
todo
progress
review
done
```

The interface displays these as Backlog, To do, In progress, In review, and Done.

### Allowed Priority Values

```text
low
medium
high
```

Using `ENUM` stops unsupported values from being stored.

## Task Relationships

### Task to Project

```sql
FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
```

Every task belongs to a project. Deleting a project deletes all its tasks.

### Task to Assignee

```sql
FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL
```

The assigned user may be empty. If that user is deleted, the task remains but becomes unassigned.

### Task to Creator

```sql
FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
```

Every task must have a creator. Deleting the creator deletes tasks created by that user.

## Relationship Summary

```text
users.id    1 ──────── many projects.owner_id
projects.id 1 ──────── many tasks.project_id
users.id    1 ──────── many tasks.assignee_id
users.id    1 ──────── many tasks.created_by
```

## Database Indexes

Indexes improve the speed of frequently used queries.

| Index | Used for |
|---|---|
| `idx_tasks_status` | Filtering tasks by workflow status |
| `idx_tasks_assignee` | Loading tasks assigned to a user |
| `idx_tasks_due_date` | Today, upcoming, overdue, and calendar queries |

## How PHP Uses These Tables

| PHP file | Database work |
|---|---|
| `api/auth/register.php` | Inserts a user and first project |
| `api/auth/login.php` | Selects a user by email |
| `api/auth/me.php` | Loads the current session user's profile |
| `api/tasks/create.php` | Inserts a new project or task |
| `api/tasks/list.php` | Selects and searches a user's tasks |
| `api/tasks/update.php` | Updates task details or completion status |
| `api/tasks/delete.php` | Deletes a task after checking ownership |

## Example Queries

### View all users

```sql
SELECT id, name, email, role, created_at
FROM users;
```

### View tasks with project and user names

```sql
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
```

### View today's tasks

```sql
SELECT *
FROM tasks
WHERE due_date = CURDATE();
```

### View upcoming tasks

```sql
SELECT *
FROM tasks
WHERE due_date > CURDATE()
ORDER BY due_date;
```

### View unfinished overdue tasks

```sql
SELECT *
FROM tasks
WHERE due_date < CURDATE()
  AND status <> 'done'
ORDER BY due_date;
```

### Search tasks

```sql
SELECT tasks.*
FROM tasks
JOIN projects ON projects.id = tasks.project_id
WHERE tasks.title LIKE '%homepage%'
   OR tasks.description LIKE '%homepage%'
   OR projects.name LIKE '%homepage%';
```

The PHP application uses prepared parameters instead of placing user-entered search text directly inside SQL.

## Test in phpMyAdmin

1. Open `http://localhost/phpmyadmin/`.
2. Select the `taskflow` database.
3. Open **SQL**.
4. Run one of the example queries.
5. Use the **Browse** tab to view inserted users, projects, and tasks.

## Common Import Errors

### Cannot create foreign key

Import the complete `database.sql` file without changing the table order. The correct order is `users`, `projects`, and then `tasks`.

### Table already exists

The file uses `CREATE TABLE IF NOT EXISTS`, so importing it again should preserve existing tables and records.

### Unknown database

Create a database named `taskflow` first or use `install.php`, which creates it automatically.

### Access denied for `root`

Update `DB_USER` and `DB_PASS` in `config/database.php` to match the MySQL account.

## Important Note

`database.sql` creates the table structure only. The demonstration account and sample records are added by `install.php`. Registration and task creation add new records through the PHP API.
