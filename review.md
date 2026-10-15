# Code Review — eRAS

First reviewed 2026-09-04. Remediated 2026-09-05 on branch `cleanup/2026-09-05`.

This file used to be a to-do list. Most of it has been done, so it is now a
record of what was fixed and — the part worth your attention — what was
deliberately left alone.

## Verdict, revised

The original review said the security fundamentals were already right and named
three problems. The fundamentals *were* right in the places it looked: prepared
statements, `password_hash`/`password_verify`, `session_regenerate_id` on
sign-in, a role check at the top of every API endpoint.

It looked in the wrong places. The role checks answer "is this a teacher?" but
nothing anywhere answered "is this *their* class", and the attendance handlers
took the shape of the form on trust. Those were the two real holes, and neither
was in the original list.

The other thing the first pass missed is that several features had never worked
at all: adding a teacher, the attendance report, and the redirect to the sign-in
page. Each failed in a way that produced no error a user would report — an
exception behind a JSON endpoint, a column that silently summed to zero, a 404.
A codebase that is never exercised end-to-end accumulates these quietly.

## Fixed

The full list with reasoning is in the last section of `README.md`, which is the
better place for it: it belongs with the project, not in a review file. In
summary:

| | |
| --- | --- |
| Authorisation | A teacher may now only record, correct or print a register for a course-and-section they actually teach. Checked on GET and again on POST. |
| Submitted data | Marks are matched against the class roster and against `P`/`A`; everything else is rejected rather than inserted. |
| Accounts | Random temporary passwords, a login throttle, one generic sign-in error, password rules applied to admin resets, 404 rather than success when the account does not exist. |
| Identifiers | Teacher IDs are allocated numerically from the right table. Adding a teacher works. |
| Sessions | HttpOnly, SameSite, conditional Secure, an absolute lifetime, JSON 401 on API paths, and a logout that actually clears the cookie. |
| Redirects | Computed from the file's own location, never from the Host header; stored targets checked to be on this site. |
| Output | Escaped in HTML and, separately, for embedding in `<script>`. |
| Reporting | The attendance report queries columns that exist and counts the mark the table actually stores. |
| Schema | The `attendance` table is defined with the others, with a uniqueness constraint the application could not enforce alone. |
| Failure modes | A database outage shows the status page rather than a blank one; unparseable dates are refused rather than turned into 1970. |

70-odd tests in `tests/`, no dependencies, `php tests/run.php`.

## Deliberately not done

**Students sign in with no password.** Any student ID gets you that student's
record, and the IDs are sequential. This is not an oversight in the code — the
`students` table has no password column — so fixing it means a schema change, a
way to issue a first password, and a screen to change it. That is a product
decision and a new page. It is called out in `README.md`, in `index.php` at the
point where it happens, and here.

**A forced password change for teachers.** The right fix for the accounts that
still have their ID as their password. Needs a `password_reset_required` column
and a change-password page.

**CSRF tokens on the admin JSON API.** The endpoints under `admin/api/` are
authenticated by session cookie and have no token. The session cookie is now
`SameSite=Lax`, which stops a cross-site POST from carrying it, so the practical
hole is closed; a token is the belt to that braces and needs a change to
`admin/dashboard.js` to send it. Worth doing.

**An audit trail for attendance edits.** `modified_dates` records *when* a row
was corrected but not by whom or from what. Answering a dispute needs both. This
is a schema change.

**Rewriting the git history.** A live database password is readable in every
commit before 2026-09-05. Rewriting is irreversible and affects every clone, so
it was left to you — see `SECRETS-TODO.md`.

**`date_default_timezone_set` in `Session::init()`.** The original review is
right that a global setting configured as a side effect of session handling is
surprising. Moving it needs a bootstrap file that every entry point includes,
which is a restructuring worth doing on its own rather than folded into a
security pass.

**A `LICENSE`.** Yours to choose.

## Notes for a future session

The rules that can be stated without a database live in `utils/` as classes with
pure functions, and every one of them is tested. When you add behaviour, put the
decision there and the plumbing in the page — that is what made the attendance
handlers testable, and it is why the correction-history overflow was found.

`tests/RequiresTest.php` walks every PHP file and checks each `require` names a
file that exists with exactly that spelling. Keep it: this project is written on
a case-insensitive filesystem and deployed on a case-sensitive one, and a failed
`require` is a blank page in production.

The weakest remaining area is still account lifecycle. Everything in
"Deliberately not done" that matters is there.

## Licence, 2026-10-02

The author chose one licence for all of their projects: free for personal,
non-commercial use, modifiable for the user's own use, and not to be
distributed, original or modified. No standard licence says that, so `LICENSE`
is a short custom text, the same in every project apart from the year and what
it excludes. The README's licence section says the same. Anything above that
calls the licence an open question, or calls the project MIT, is superseded.

## CI, 2026-10-02

`.github/workflows/ci.yml` runs `tests/run.php` on PHP 8.4, on
`ubuntu-latest`. No MySQL and no credentials. Nothing has been pushed, so it
has not run on GitHub yet. Every job was run before committing, from a fresh
clone of this branch, the way a runner would run it: Linux jobs in a throwaway
Ubuntu 24.04 (the image `ubuntu-latest` uses) with the same tool versions. 74
tests, 660 assertions.
