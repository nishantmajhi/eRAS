# eRAS

An electronic attendance register for a college.

A teacher opens their period, sees the students enrolled in that course and
section, marks each one present or absent, and saves. Later they can correct a
day they got wrong, or print the register for a date. A student can see their
own record. An administrator maintains the courses, the staff, the enrolment,
and the accounts.

Vanilla PHP, MariaDB, and hand-written HTML, CSS and JavaScript. No Composer, no
build step, no framework.

## The model

Four things exist, and everything else follows from how they relate.

A **course** (`courses`) has a code, a title, a programme and a semester.
Enrolment is per **section**, a single letter, so a course runs as several
independent classes.

A **student** (`students`) is a name and a generated ID, attached to one course
and one section. A **teacher** (`teachers`) is a name and a generated ID, and
has one row per course-and-section they teach — a teacher with three classes has
three rows sharing one teacher ID.

An **account** (`users`) is an ID, a password hash and a role. A teacher's
account ID is their teacher ID; the administrator's is fixed. **Students have no
account** — see "Students sign in without a password" below.

**Attendance** (`attendance`) is one row per student per class per day, holding
`P` or `A`, who recorded it, when, and a list of the times it was later
corrected.

The pairing of *course code and section* is what identifies a class throughout,
and it is what decides who may touch a register: a teacher may record, correct
and print attendance only for a course-and-section that appears against their
teacher ID in `teachers`.

## Running it

You need PHP 8.1 or later and a MariaDB or MySQL server. Nothing else.

```sh
git clone https://github.com/nishantmajhi/eRAS.git
cd eRAS
```

Create the database:

```sh
mysql -u root -p -e "CREATE DATABASE eras;"
```

Create the tables and the demo data:

```sh
php database/seed.php
```

Serve it:

```sh
php -S localhost:1230
```

Then open <http://localhost:1230>.

The demo administrator is `2025-ADMIN-0` with the password `superuser`, and the
seeded teachers all have the password `teacher`. These are for a local database
and nothing else.

### Pointing it at a hosted database

Copy `.ENV.example` to `.ENV`, fill in the connection details, and set
`CONNECTION = 'cloud'`. Leaving `CONNECTION` unset uses the local database at
`localhost` as `root` with no password.

`.ENV` is gitignored and must stay that way. An earlier `.ENV` with live
credentials in it was committed to this repository; **read `SECRETS-TODO.md`**
before deploying anything.

### Tests

```sh
php tests/run.php
```

An optional argument filters by filename: `php tests/run.php Attendance`.

The suite has no dependencies — it is a few hundred lines of plain PHP in
`tests/Harness.php` — because a project with no Composer file should not need
one to be testable. It covers the rules that can be stated without a database:
what a mark may be, who may be marked, how identifiers are numbered, when a
session ends, where a redirect may point, and how passwords are generated and
checked. It also walks every PHP file in the project and checks that each
`require` names a file that exists with exactly that spelling, which matters
because this is written on Windows and deployed on Linux.

## Layout

```
index.php            sign-in; the only page that is not behind a role check
logout.php
home.html            the sign-in form
auth/Session.php     session start, expiry, and the cookie's flags
utils/               the shared rules, one class per file
admin/               the administrator's dashboard
  api/               JSON endpoints it calls
teacher/             a teacher's list of periods
student/             a student's own record
attendance/new/      recording a register
attendance/update/   correcting one
attendance/print/    printing one
template/status.html the page every "it worked" and "it failed" message uses
database/
  seed.php           creates every table and the demo data
  seeders/           one file per table
  data/              the demo rows
tests/
```

`utils/` holds the parts worth reading on their own:

| File | What it decides |
| --- | --- |
| `Attendance.php` | What a mark is, who is on a roster, who teaches a class, how the correction history stays inside its column |
| `Passwords.php` | How a temporary password is generated and what makes a password acceptable |
| `LoginThrottle.php` | How quickly repeated failures lock an account |
| `TeacherIds.php` | How the next teacher ID is numbered |
| `Redirect.php` | Which redirect targets are on this site |
| `Status.php` | The shared status page |
| `Database.php` | Which database to open |

## Two things to know before you rely on this

### Students sign in without a password

Type any student ID at the sign-in form and you are that student. The `students`
table has no password column, so this is not a missing check — it is how the
system was designed. But student IDs run in sequence (`2026-BCA-SDNT-1`,
`-2`, `-3`), so anybody can walk them and read any student's attendance record.

Fixing it needs a column, a way to issue the first password, and a screen to
change it. That is a design decision and a new page, so it has been left for
you rather than guessed at; the sign-in code says the same thing at the point
where it happens.

Guessing is at least slowed now: an unrecognised ID falls through to the account
lookup, where the login throttle counts the failure.

### Credentials

`SECRETS-TODO.md` lists what needs rotating and why. The short version: a real
database password was committed and is still readable in this repository's
history, and any teacher account created before September 2026 has the teacher's
own ID as its password.

## What changed in the September 2026 pass

Recorded because the reasoning matters more than the diff. Each item has a
regression test unless noted.

**Nobody checked who taught the class.** Course code and section arrived in the
query string and in the form body, and no page compared them against the
signed-in teacher. Any teacher could record, rewrite or print any class's
register by editing the address bar. `Attendance::teaches()` is the missing
check, and it now runs on GET and again on POST — the form body is a separate
request and carries its own course code.

**The submit handlers trusted the shape of the form.** They walked `$_POST` and
read every field they did not recognise as a student ID, with its value as the
mark. A hand-written request could insert rows for students in other classes,
and the mark was whatever the first byte of the value happened to be.
`Attendance::collectMarks()` checks both against the class roster and against
the two marks that exist. The correction page had left `createdDate` out of its
list of non-student fields, so the date was processed as a student.

**Adding a teacher never worked.** The ID allocator ran
`SELECT teacher_id FROM users`, and `users` has no such column; since PHP 8.1
that is an uncaught exception. It also ordered textually, so it would have
handed out a duplicate as soon as `2026-TCHR-10` existed and sorted above
`2026-TCHR-9`. `TeacherIds` replaces it.

**Teacher accounts were created with their own ID as the password.** Sequential,
printed on staff lists, visible in URLs. New accounts get a random temporary
password shown once. Existing accounts are not fixed by that change; see
`SECRETS-TODO.md`.

**Nothing limited password guessing,** which mattered a great deal given the
above. `LoginThrottle` escalates from a minute at five failures to an hour at
twenty, per account, forgetting failures older than a day.

**The sign-in form said which half was wrong.** "Invalid username!" and
"Invalid password!" together are a way to enumerate accounts. One message now.

**The attendance report has never produced a correct number.** It was written
against a guessed schema and said so in a comment. `SUM(a.status = 'present')`
counted nothing, because the column holds `P`; and a date filter referenced
`a.date`, which does not exist, so any filtered request was a 500.

**A database outage showed a blank page.** `establishConnection()` returned
`null` on failure and every caller immediately dereferenced it, so the
"Connection to database failed!" page they all had ready was unreachable.

**The unauthenticated redirect went nowhere.** `header('Location:' .
$_SERVER['HTTP_HOST'])` has no space and no scheme, so browsers read it as a
relative path: from `/admin/index.php` it resolved to `/admin/example.com`. The
Host header is also client-supplied, and one page prefixed it with `http://` and
so would have followed it off-site. Redirects are now computed from where the
file is on disk and never consult the request.

**Redirect targets were not checked.** `$_SESSION['redirect_target']` came from
`REQUEST_URI`, which a client can make `//evil.example/path` — a protocol-
relative URL, which is another origin.

**Session expiry redirected API endpoints to a page.** From
`/admin/api/teachers.php` the relative `index.php` resolved to a URL that does
not exist, and the calling `fetch()` got HTML where it expected JSON. Expired
API requests now answer 401 with JSON.

**The session cookie had no flags** — not HttpOnly, not SameSite — and there was
only an idle timeout, so a continuously-used session never ended. It now has
both, and an eight-hour absolute lifetime. Logging out cleared the cookie with
different parameters than it was set with, which leaves the original in place.

**Administrator-entered values went into pages unescaped.** Student names,
course titles and the query-string values echoed back into the form.

**Data embedded in `<script>` blocks was not escaped for that context.** A value
containing `</script>` closes the element.

**The attendance table was created by `CREATE TABLE IF NOT EXISTS` on every
submission,** which put a schema change in the request path and defined the
table somewhere nobody would look. It is in `database/seeders/attendance.php`
now, with a uniqueness constraint on student-course-section-date that the
application's check-then-insert could never guarantee on its own.

**The correction history overflowed its column.** `modified_dates` is
`VARCHAR(500)` and was appended to without limit; past roughly twenty
corrections the write fails, and in strict mode that error rolls back the whole
submission. The oldest entries are dropped now, never a partial one.

**`CSRF` tokens were compared with `!=`.** Loose comparison, and two
numeric-looking strings compare as numbers. `hash_equals` now.

**Deleting a teacher never removed the orphaned account:** `$cnt === 0` against
a mysqli value, which is a string.

**Setting a password for a user that does not exist reported success.** An
`UPDATE` matching nothing is not an error, so a typo in the ID handed out a
password that would never work.

**A month or date that could not be parsed was silently accepted.**
`strtotime()` returns `false`, and `date()` reads that as the epoch, so a
nonsense month showed January 1970. `strtotime` also accepts `2026-02-31` and
quietly returns the third of March.

Also: `test/` became `database/`, since it holds the schema and the seed data
rather than tests; `utils/redirect.php` became `utils/Redirect.php` to match its
neighbours; twenty-odd hand-built copies of the status page became
`Status::error()` and `Status::success()`, which escape their message; and
`database/.htaccess` denies access to the seeders on an Apache host.

One change is visible: the account password field in the administrator's
dashboard now says eight characters rather than six, because the server-side
rule it was describing is eight.

## Licence

Free for personal, non-commercial use. You may use, study and modify this
software for yourself, but you may not use it commercially, and you may not
share or publish it, in its original form or modified. See [LICENSE](LICENSE)
for the full terms.

jQuery, in `assets/js/`, keeps its own MIT licence.
