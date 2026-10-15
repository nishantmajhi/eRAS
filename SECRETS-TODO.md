# Credentials to rotate

Written 2026-09-05. Delete this file once the items below are done.

## 1. The database password was committed

`.ENV` was tracked in this repository and held live credentials for an
InfinityFree MySQL database:

```
HOSTNAME = 'sql100.infinityfree.com'
USERNAME = 'if0_40504636'
PASSWORD = <a real password>
DBNAME   = 'if0_40504636_eras'
```

It has been removed from tracking and added to `.gitignore`, and `.ENV.example`
now holds the template. **The file on disk is untouched**, so nothing stops
working.

**This repository has a remote:** `github.com/nishantmajhi/eRAS.git`. If it is
or ever was public, treat that password as disclosed — and note that the
hostname and username are in there too, so the disclosure is a complete set of
connection details, not a fragment.

What to do:

1. **Change the password** in the InfinityFree control panel, then update your
   local `.ENV`.
2. **Decide about the git history.** The credentials remain readable in every
   commit before 2026-09-05. Either rewrite the history with `git filter-repo` and
   force-push, or make the repository private if it is not already. Rewriting is
   irreversible and affects every clone, which is why it was left to you.

Worth knowing: `Database::establishConnection()` currently calls
`connectLocalhost()` and the cloud path is commented out, so the committed
credentials may never have been in active use. They are still disclosed.

## 2. Teacher accounts have guessable passwords

`admin/api/teachers.php` created every teacher account with the teacher's own ID
as the password. A teacher ID is printed on lists, appears in URLs, and is known
to students and staff, so any account whose password had not been changed was
open to anyone holding a staff list.

That is fixed: new accounts now get a random temporary password, shown once to
the administrator who creates them.

**Existing accounts are not fixed by that change.** Any teacher account created
before 2026-09-05 still has its ID as its password unless the teacher changed it.
Reset them:

```sql
-- List the teacher accounts to review.
-- The account identifier column is `uuid`, not `username`.
SELECT uuid, role FROM users WHERE role = 'teacher';
```

The administrator's dashboard already has an Accounts section that sets a
password on an existing account, so each of these can be reset there. That
endpoint now refuses a password equal to the account's own ID — the exact value
these accounts currently hold — and now reports a 404 instead of success when
the ID does not match an account, which it previously did silently.

What is still missing is a way to *make* a teacher change their own password.
That needs a screen, so it is a UI change and therefore yours to make; see the
"Remaining work" section of `review.md`.

## 3. Seed data

`database/seeders/teachers.php` seeds every teacher with the password `teacher`, and
`database/eras.sql` is a committed schema and data dump. Both are fine for
development. Make sure the seeders cannot be reached from a deployed instance —
they are plain PHP files under the document root, so without that they can be.
