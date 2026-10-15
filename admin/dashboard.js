document.addEventListener('DOMContentLoaded', () => {

  // ── seed overview stats injected by index.php ──
  if (typeof adminStats !== 'undefined') {
    document.getElementById('stat-teachers').textContent = adminStats.teachers;
    document.getElementById('stat-students').textContent = adminStats.students;
    document.getElementById('stat-courses').textContent = adminStats.courses;
    document.getElementById('stat-users').textContent = adminStats.users;
  }

  // ── load programs from DB, then populate all dropdowns ──
  loadPrograms();

  // ── sidebar navigation ──
  document.querySelectorAll('.nav-item').forEach((item) => {
    item.addEventListener('click', () => {
      switchPanel(item.dataset.panel);
    });
  });

  // ── overview goto buttons ──
  document.querySelectorAll('[data-goto]').forEach((btn) => {
    btn.addEventListener('click', () => switchPanel(btn.dataset.goto));
  });

  // ── teachers ──
  document.getElementById('btn-add-teacher').addEventListener('click', () => {
    openTeacherDialog(null);
  });

  $('#teacher-search, #teacher-filter-program, #teacher-filter-sem, #teacher-filter-section').on('input change', () => loadTeachers());

  // ── students ──
  document.getElementById('btn-add-student').addEventListener('click', () => {
    openStudentDialog(null);
  });

  $('#student-search, #student-filter-program, #student-filter-sem, #student-filter-section').on('input change', () => loadStudents());

  // ── courses ──
  document.getElementById('btn-add-course').addEventListener('click', () => {
    openCourseDialog(null);
  });

  document.getElementById('btn-add-program').addEventListener('click', () => {
    openAddProgramDialog();
  });

  $('#course-search, #course-filter-program, #course-filter-sem').on('input change', () => loadCourses());

  // ── reports ──
  document.getElementById('btn-run-report').addEventListener('click', () => runReport());

});

// ══════════════════════════════════════════
//  NAVIGATION
// ══════════════════════════════════════════

function switchPanel(name) {
  document.querySelectorAll('.nav-item').forEach((el) => el.classList.remove('active'));
  document.querySelector(`.nav-item[data-panel="${name}"]`).classList.add('active');

  document.querySelectorAll('.panel').forEach((el) => el.classList.remove('active'));
  document.getElementById(`panel-${name}`).classList.add('active');

  if (name === 'teachers') loadTeachers();
  if (name === 'students') loadStudents();
  if (name === 'courses')  loadCourses();
  if (name === 'users')    loadUsers();
}

// ══════════════════════════════════════════
//  PROGRAMS  (loaded from DB)
// ══════════════════════════════════════════

// Cache so we only fetch once per page load
let _programs = null;

function loadPrograms() {
  $.ajax({
    url: 'api/programs.php',
    method: 'GET',
    success: (data) => {
      _programs = data; // [{name, semesters:[]}]
      populateProgramDropdowns();
    },
    error: () => console.warn('Could not load programs from server.'),
  });
}

/** Rebuild every program <select> on the page from the cached _programs list. */
function populateProgramDropdowns() {
  if (!_programs) return;

  const programSelects = [
    'teacher-filter-program',
    'student-filter-program',
    'course-filter-program',
    'rep-program',
  ];

  programSelects.forEach((id) => {
    const sel = document.getElementById(id);
    if (!sel) return;
    // keep the first "All / All programs" option, remove the rest
    while (sel.options.length > 1) sel.remove(1);
    _programs.forEach(({ name }) => {
      const opt = document.createElement('option');
      opt.value = name;
      opt.textContent = name;
      sel.appendChild(opt);
    });
  });

  // Semester dropdowns show union of all semesters across all programs
  populateSemesterDropdowns(null);
}

/**
 * Repopulate semester <select>s.
 * If programName is provided, only show semesters for that program.
 * If null, show union of all semesters.
 */
function populateSemesterDropdowns(programName) {
  if (!_programs) return;

  let sems;
  if (programName) {
    const prog = _programs.find((p) => p.name === programName);
    sems = prog ? prog.semesters : [];
  } else {
    const all = new Set();
    _programs.forEach((p) => p.semesters.forEach((s) => all.add(s)));
    sems = [...all].sort((a, b) => a - b);
  }

  const semSelects = [
    'teacher-filter-sem',
    'student-filter-sem',
    'course-filter-sem',
    'rep-sem',
  ];

  semSelects.forEach((id) => {
    const sel = document.getElementById(id);
    if (!sel) return;
    const prev = sel.value;
    while (sel.options.length > 1) sel.remove(1);
    sems.forEach((n) => {
      const opt = document.createElement('option');
      opt.value = n;
      opt.textContent = 'Semester ' + n;
      sel.appendChild(opt);
    });
    // restore previous value if still valid
    if ([...sel.options].some((o) => o.value == prev)) sel.value = prev;
  });
}

// When admin picks a program in any filter, narrow the semester dropdown
$(document).on('change', '#teacher-filter-program, #student-filter-program, #course-filter-program, #rep-program', function () {
  populateSemesterDropdowns(this.value || null);
});

// ══════════════════════════════════════════
//  ADD PROGRAM
// ══════════════════════════════════════════

function openAddProgramDialog() {
  const msgHTML = `
    <p class="panel-note" style="margin-bottom:0.8rem">
      Programs are defined by their courses. To add a new program, enter its name
      and the first course for that program.
    </p>
    <div class="form-group">
      <label for="f-prog-name">Program name</label>
      <input type="text" id="f-prog-name" placeholder="e.g. BBA" />
    </div>
    <div class="form-group">
      <label for="f-prog-ccode">First course code</label>
      <input type="text" id="f-prog-ccode" placeholder="e.g. MGT101" />
    </div>
    <div class="form-group">
      <label for="f-prog-ctitle">First course title</label>
      <input type="text" id="f-prog-ctitle" placeholder="e.g. Principles of Management" />
    </div>
    <div class="form-group">
      <label for="f-prog-sem">Semester</label>
      <select id="f-prog-sem">
        ${[1,2,3,4,5,6,7,8].map((n) => `<option value="${n}">${n}</option>`).join('')}
      </select>
    </div>
  `;

  const dialog = generateDialogTemplate('Add new program', msgHTML);
  const actions = document.createElement('section');
  actions.id = 'actions';

  const cancelBtn = document.createElement('button');
  cancelBtn.type = 'button';
  cancelBtn.textContent = 'Cancel';
  cancelBtn.className = 'transparent_button';
  cancelBtn.onclick = () => document.body.removeChild(document.querySelector('.modal'));
  actions.appendChild(cancelBtn);

  const submitBtn = document.createElement('button');
  submitBtn.type = 'button';
  submitBtn.textContent = 'Create program';
  submitBtn.className = 'positive_button';
  submitBtn.onclick = () => submitNewProgram();
  actions.appendChild(submitBtn);

  dialog.appendChild(actions);
  document.body.appendChild(dialog);
  dialog.showModal();
}

function submitNewProgram() {
  const progName = document.getElementById('f-prog-name').value.trim();
  const code     = document.getElementById('f-prog-ccode').value.trim().toUpperCase();
  const title    = document.getElementById('f-prog-ctitle').value.trim();
  const sem      = document.getElementById('f-prog-sem').value;

  if (!progName || !code || !title) {
    alertDialog('Validation error', '<p>All fields are required.</p>');
    return;
  }

  // Check if program already exists in cached list
  if (_programs && _programs.find((p) => p.name.toLowerCase() === progName.toLowerCase())) {
    alertDialog('Duplicate program', `<p><strong>${progName}</strong> already exists. Use "Add course" to add more courses to it.</p>`);
    return;
  }

  $.ajax({
    url: 'api/courses.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({ course_code: code, course_title: title, program_name: progName, semester_no: sem }),
    success: () => {
      document.body.removeChild(document.querySelector('.modal'));
      // Refresh programs cache then reload courses
      $.ajax({
        url: 'api/programs.php',
        method: 'GET',
        success: (data) => {
          _programs = data;
          populateProgramDropdowns();
          loadCourses();
          loadStats();
        },
      });
    },
    error: (xhr) => {
      const msg = xhr.responseJSON?.error || 'Failed to create program.';
      alertDialog('Error', `<p>${msg}</p>`);
    },
  });
}

// ══════════════════════════════════════════
//  TEACHERS
// ══════════════════════════════════════════

function loadTeachers() {
  const search  = document.getElementById('teacher-search').value;
  const program = document.getElementById('teacher-filter-program').value;
  const sem     = document.getElementById('teacher-filter-sem').value;
  const section = document.getElementById('teacher-filter-section').value;

  $.ajax({
    url: 'api/teachers.php',
    method: 'GET',
    data: { search, program, sem, section },
    success: (rows) => renderTeacherTable(rows),
    error: () => alertDialog('Error', '<p>Failed to load teachers.</p>'),
  });
}

function renderTeacherTable(rows) {
  const tbody = document.getElementById('teacher-tbody');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="panel-note" style="text-align:center;padding:2rem">No teachers found.</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map((t) => `
    <tr>
      <td>${t.teacher_id}</td>
      <td class="col-name">${t.teacher_name}</td>
      <td>${t.course_code}</td>
      <td>${t.section}</td>
      <td>
        <button type="button" class="transparent_button" data-id="${t.id}" data-action="edit-teacher">Edit</button>
        <button type="button" class="transparent_button danger-btn" data-id="${t.id}" data-action="delete-teacher">Remove</button>
      </td>
    </tr>
  `).join('');

  document.querySelectorAll('[data-action="edit-teacher"]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const row = rows.find((r) => r.id == btn.dataset.id);
      openTeacherDialog(row);
    });
  });

  document.querySelectorAll('[data-action="delete-teacher"]').forEach((btn) => {
    btn.addEventListener('click', () => deleteTeacher(btn.dataset.id));
  });
}

function openTeacherDialog(teacher) {
  const isEdit = teacher !== null;

  const msgHTML = `
    <div class="form-group">
      <label for="f-tname">Full name</label>
      <input type="text" id="f-tname" value="${isEdit ? teacher.teacher_name : ''}" placeholder="e.g. Ramesh Kumar Poudel" />
    </div>
    <div class="form-group">
      <label for="f-tcourse">Course code</label>
      <input type="text" id="f-tcourse" value="${isEdit ? teacher.course_code : ''}" placeholder="e.g. CSC109" />
    </div>
    <div class="form-group">
      <label for="f-tsection">Section</label>
      <select id="f-tsection">
        <option value="A" ${isEdit && teacher.section === 'A' ? 'selected' : ''}>A</option>
        <option value="B" ${isEdit && teacher.section === 'B' ? 'selected' : ''}>B</option>
      </select>
    </div>
  `;

  const dialog = generateDialogTemplate(isEdit ? 'Edit teacher' : 'Add teacher', msgHTML);
  const actions = document.createElement('section');
  actions.id = 'actions';

  const cancelBtn = document.createElement('button');
  cancelBtn.type = 'button';
  cancelBtn.textContent = 'Cancel';
  cancelBtn.className = 'transparent_button';
  cancelBtn.onclick = () => document.body.removeChild(document.querySelector('.modal'));
  actions.appendChild(cancelBtn);

  const submitBtn = document.createElement('button');
  submitBtn.type = 'button';
  submitBtn.textContent = isEdit ? 'Save changes' : 'Add teacher';
  submitBtn.className = 'positive_button';
  submitBtn.onclick = () => submitTeacher(isEdit ? teacher.id : null);
  actions.appendChild(submitBtn);

  dialog.appendChild(actions);
  document.body.appendChild(dialog);
  dialog.showModal();
}

function submitTeacher(id) {
  const name    = document.getElementById('f-tname').value.trim();
  const course  = document.getElementById('f-tcourse').value.trim().toUpperCase();
  const section = document.getElementById('f-tsection').value;

  if (!name || !course) {
    alertDialog('Validation error', '<p>Name and course code are required.</p>');
    return;
  }

  $.ajax({
    url: 'api/teachers.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({ id, teacher_name: name, course_code: course, section }),
    success: () => {
      document.body.removeChild(document.querySelector('.modal'));
      loadTeachers();
      loadStats();
    },
    error: (xhr) => {
      const msg = xhr.responseJSON?.error || 'Failed to save teacher.';
      alertDialog('Error', `<p>${msg}</p>`);
    },
  });
}

function deleteTeacher(id) {
  confirmDialog('Remove teacher', '<p>Remove this teacher record? This cannot be undone.</p>', (confirmed) => {
    if (!confirmed) return;
    $.ajax({
      url: 'api/teachers.php',
      method: 'DELETE',
      contentType: 'application/json',
      data: JSON.stringify({ id }),
      success: () => { loadTeachers(); loadStats(); },
      error: () => alertDialog('Error', '<p>Failed to remove teacher.</p>'),
    });
  });
}

// ══════════════════════════════════════════
//  STUDENTS
// ══════════════════════════════════════════

function loadStudents() {
  const search  = document.getElementById('student-search').value;
  const program = document.getElementById('student-filter-program').value;
  const sem     = document.getElementById('student-filter-sem').value;
  const section = document.getElementById('student-filter-section').value;

  $.ajax({
    url: 'api/students.php',
    method: 'GET',
    data: { search, program, sem, section },
    success: (rows) => renderStudentTable(rows),
    error: () => alertDialog('Error', '<p>Failed to load students.</p>'),
  });
}

function renderStudentTable(rows) {
  const tbody = document.getElementById('student-tbody');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="panel-note" style="text-align:center;padding:2rem">No students found.</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map((s) => `
    <tr>
      <td>${s.student_id}</td>
      <td class="col-name">${s.student_name}</td>
      <td>${s.course_code}</td>
      <td>${s.section}</td>
      <td>
        <button type="button" class="transparent_button" data-id="${s.id}" data-action="edit-student">Edit</button>
        <button type="button" class="transparent_button danger-btn" data-id="${s.id}" data-action="delete-student">Remove</button>
      </td>
    </tr>
  `).join('');

  document.querySelectorAll('[data-action="edit-student"]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const row = rows.find((r) => r.id == btn.dataset.id);
      openStudentDialog(row);
    });
  });

  document.querySelectorAll('[data-action="delete-student"]').forEach((btn) => {
    btn.addEventListener('click', () => deleteStudent(btn.dataset.id));
  });
}

function openStudentDialog(student) {
  const isEdit = student !== null;

  const msgHTML = `
    <div class="form-group">
      <label for="f-sname">Full name</label>
      <input type="text" id="f-sname" value="${isEdit ? student.student_name : ''}" placeholder="e.g. Aarav Sharma" />
    </div>
    <div class="form-group">
      <label for="f-scourse">Course code</label>
      <input type="text" id="f-scourse" value="${isEdit ? student.course_code : ''}" placeholder="e.g. CSC109" />
    </div>
    <div class="form-group">
      <label for="f-ssection">Section</label>
      <select id="f-ssection">
        <option value="A" ${isEdit && student.section === 'A' ? 'selected' : ''}>A</option>
        <option value="B" ${isEdit && student.section === 'B' ? 'selected' : ''}>B</option>
      </select>
    </div>
  `;

  const dialog = generateDialogTemplate(isEdit ? 'Edit student' : 'Enroll student', msgHTML);
  const actions = document.createElement('section');
  actions.id = 'actions';

  const cancelBtn = document.createElement('button');
  cancelBtn.type = 'button';
  cancelBtn.textContent = 'Cancel';
  cancelBtn.className = 'transparent_button';
  cancelBtn.onclick = () => document.body.removeChild(document.querySelector('.modal'));
  actions.appendChild(cancelBtn);

  const submitBtn = document.createElement('button');
  submitBtn.type = 'button';
  submitBtn.textContent = isEdit ? 'Save changes' : 'Enroll';
  submitBtn.className = 'positive_button';
  submitBtn.onclick = () => submitStudent(isEdit ? student.id : null);
  actions.appendChild(submitBtn);

  dialog.appendChild(actions);
  document.body.appendChild(dialog);
  dialog.showModal();
}

function submitStudent(id) {
  const name    = document.getElementById('f-sname').value.trim();
  const course  = document.getElementById('f-scourse').value.trim().toUpperCase();
  const section = document.getElementById('f-ssection').value;

  if (!name || !course) {
    alertDialog('Validation error', '<p>Name and course code are required.</p>');
    return;
  }

  $.ajax({
    url: 'api/students.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({ id, student_name: name, course_code: course, section }),
    success: () => {
      document.body.removeChild(document.querySelector('.modal'));
      loadStudents();
      loadStats();
    },
    error: (xhr) => {
      const msg = xhr.responseJSON?.error || 'Failed to save student.';
      alertDialog('Error', `<p>${msg}</p>`);
    },
  });
}

function deleteStudent(id) {
  confirmDialog('Remove student', '<p>Remove this student record? This cannot be undone.</p>', (confirmed) => {
    if (!confirmed) return;
    $.ajax({
      url: 'api/students.php',
      method: 'DELETE',
      contentType: 'application/json',
      data: JSON.stringify({ id }),
      success: () => { loadStudents(); loadStats(); },
      error: () => alertDialog('Error', '<p>Failed to remove student.</p>'),
    });
  });
}

// ══════════════════════════════════════════
//  COURSES
// ══════════════════════════════════════════

function loadCourses() {
  const search  = document.getElementById('course-search').value;
  const program = document.getElementById('course-filter-program').value;
  const sem     = document.getElementById('course-filter-sem').value;
  $.ajax({
    url: 'api/courses.php',
    method: 'GET',
    data: { search, program, sem },
    success: (rows) => renderCourseTable(rows),
    error: () => alertDialog('Error', '<p>Failed to load courses.</p>'),
  });
}

function renderCourseTable(rows) {
  const tbody = document.getElementById('course-tbody');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="panel-note" style="text-align:center;padding:2rem">No courses found.</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map((c) => `
    <tr>
      <td>${c.course_code}</td>
      <td class="col-name">${c.course_title}</td>
      <td>${c.program_name}</td>
      <td>${c.semester_no}</td>
      <td>
        <button type="button" class="transparent_button" data-code="${c.course_code}" data-action="edit-course">Edit</button>
      </td>
    </tr>
  `).join('');

  document.querySelectorAll('[data-action="edit-course"]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const row = rows.find((r) => r.course_code === btn.dataset.code);
      openCourseDialog(row);
    });
  });
}

function openCourseDialog(course) {
  const isEdit = course !== null;

  // Build program options from cache; fall back to common defaults if not loaded yet
  const progList = _programs && _programs.length
    ? _programs.map((p) => p.name)
    : ['BCA', 'CSIT', 'BIM'];

  const progOptions = progList.map((name) =>
    `<option value="${name}" ${isEdit && course.program_name === name ? 'selected' : ''}>${name}</option>`
  ).join('');

  // Determine which semesters to show based on the selected program (edit) or all
  const selectedProg = isEdit ? course.program_name : null;
  const semList = selectedProg && _programs
    ? (_programs.find((p) => p.name === selectedProg)?.semesters || [1,2,3,4,5,6,7,8])
    : [1,2,3,4,5,6,7,8];

  const semOptions = semList.map((n) =>
    `<option value="${n}" ${isEdit && course.semester_no == n ? 'selected' : ''}>${n}</option>`
  ).join('');

  const msgHTML = `
    <div class="form-group">
      <label for="f-ccode">Course code${isEdit ? ' <span class="faint">(cannot change)</span>' : ''}</label>
      <input type="text" id="f-ccode" value="${isEdit ? course.course_code : ''}" ${isEdit ? 'readonly' : ''} placeholder="e.g. CACS201" />
    </div>
    <div class="form-group">
      <label for="f-ctitle">Title</label>
      <input type="text" id="f-ctitle" value="${isEdit ? course.course_title : ''}" placeholder="e.g. Data Structures and Algorithms" />
    </div>
    <div class="form-group">
      <label for="f-cprog">Program</label>
      <select id="f-cprog">${progOptions}</select>
    </div>
    <div class="form-group">
      <label for="f-csem">Semester</label>
      <select id="f-csem">${semOptions}</select>
    </div>
  `;

  const dialog = generateDialogTemplate(isEdit ? 'Edit course' : 'Add course', msgHTML);
  const actions = document.createElement('section');
  actions.id = 'actions';

  const cancelBtn = document.createElement('button');
  cancelBtn.type = 'button';
  cancelBtn.textContent = 'Cancel';
  cancelBtn.className = 'transparent_button';
  cancelBtn.onclick = () => document.body.removeChild(document.querySelector('.modal'));
  actions.appendChild(cancelBtn);

  const submitBtn = document.createElement('button');
  submitBtn.type = 'button';
  submitBtn.textContent = isEdit ? 'Save changes' : 'Add course';
  submitBtn.className = 'positive_button';
  submitBtn.onclick = () => submitCourse(isEdit ? course.course_code : null);
  actions.appendChild(submitBtn);

  dialog.appendChild(actions);
  document.body.appendChild(dialog);
  dialog.showModal();
}

function submitCourse(originalCode) {
  const code    = document.getElementById('f-ccode').value.trim().toUpperCase();
  const title   = document.getElementById('f-ctitle').value.trim();
  const program = document.getElementById('f-cprog').value;
  const sem     = document.getElementById('f-csem').value;

  if (!code || !title) {
    alertDialog('Validation error', '<p>Course code and title are required.</p>');
    return;
  }

  $.ajax({
    url: 'api/courses.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({ original_code: originalCode, course_code: code, course_title: title, program_name: program, semester_no: sem }),
    success: () => {
      document.body.removeChild(document.querySelector('.modal'));
      loadCourses();
      loadStats();
    },
    error: (xhr) => {
      const msg = xhr.responseJSON?.error || 'Failed to save course.';
      alertDialog('Error', `<p>${msg}</p>`);
    },
  });
}

// ══════════════════════════════════════════
//  USERS
// ══════════════════════════════════════════

function loadUsers() {
  $.ajax({
    url: 'api/users.php',
    method: 'GET',
    success: (rows) => renderUsersTable(rows),
    error: () => alertDialog('Error', '<p>Failed to load users.</p>'),
  });
}

function renderUsersTable(rows) {
  const container = document.getElementById('users-accordion');
  if (!rows.length) {
    container.innerHTML = '<p class="panel-note" style="padding:1rem">No user accounts found.</p>';
    return;
  }

  // Group by role for better organisation
  const groups = {};
  rows.forEach((u) => {
    if (!groups[u.role]) groups[u.role] = [];
    groups[u.role].push(u);
  });

  container.innerHTML = Object.entries(groups).map(([role, users]) => `
    <div class="acc-group">
      <div class="acc-group-label">${role.charAt(0).toUpperCase() + role.slice(1)} accounts (${users.length})</div>
      ${users.map((u) => `
        <div class="acc-item" data-uuid="${u.uuid}">
          <div class="acc-header">
            <span class="acc-uuid">${u.uuid}</span>
            <span class="acc-chevron">▸</span>
          </div>
          <div class="acc-body">
            <p class="acc-role-tag">Role: <strong>${u.role}</strong></p>
            <div class="form-group">
              <label>New password</label>
              <input type="password" class="acc-pw1" placeholder="New password (min 8 chars)" />
            </div>
            <div class="form-group">
              <label>Confirm password</label>
              <input type="password" class="acc-pw2" placeholder="Confirm password" />
            </div>
            <div class="acc-actions">
              <button type="button" class="positive_button acc-reset-btn" data-uuid="${u.uuid}">Reset password</button>
            </div>
          </div>
        </div>
      `).join('')}
    </div>
  `).join('');

  // Open the first item by default
  const firstItem = container.querySelector('.acc-item');
  if (firstItem) firstItem.classList.add('open');

  // Toggle expand/collapse
  container.querySelectorAll('.acc-header').forEach((header) => {
    header.addEventListener('click', () => {
      const item = header.closest('.acc-item');
      const isOpen = item.classList.contains('open');
      // close all
      container.querySelectorAll('.acc-item').forEach((el) => el.classList.remove('open'));
      if (!isOpen) item.classList.add('open');
    });
  });

  // Reset password buttons
  container.querySelectorAll('.acc-reset-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.acc-item');
      const uuid = btn.dataset.uuid;
      const pw1  = item.querySelector('.acc-pw1').value;
      const pw2  = item.querySelector('.acc-pw2').value;

      if (pw1.length < 8) {
        alertDialog('Validation error', '<p>Password must be at least 8 characters.</p>');
        return;
      }
      if (pw1 !== pw2) {
        alertDialog('Validation error', '<p>Passwords do not match.</p>');
        return;
      }

      confirmDialog('Reset password', `<p>Reset password for <strong>${uuid}</strong>?</p>`, (confirmed) => {
        if (!confirmed) return;
        $.ajax({
          url: 'api/users.php',
          method: 'POST',
          contentType: 'application/json',
          data: JSON.stringify({ uuid, password: pw1 }),
          success: () => {
            item.querySelector('.acc-pw1').value = '';
            item.querySelector('.acc-pw2').value = '';
            item.classList.remove('open');
            alertDialog('Done', '<p>Password has been reset.</p>');
          },
          error: (xhr) => {
            const msg = xhr.responseJSON?.error || 'Failed to reset password.';
            alertDialog('Error', `<p>${msg}</p>`);
          },
        });
      });
    });
  });
}

// ══════════════════════════════════════════
//  REPORTS
// ══════════════════════════════════════════

function runReport() {
  const program = document.getElementById('rep-program').value;
  const sem     = document.getElementById('rep-sem').value;
  const section = document.getElementById('rep-section').value;
  const from    = document.getElementById('rep-from').value;
  const to      = document.getElementById('rep-to').value;

  $.ajax({
    url: 'api/reports.php',
    method: 'GET',
    data: { program, sem, section, from, to },
    success: (rows) => renderReportTable(rows),
    error: () => alertDialog('Error', '<p>Failed to load report.</p>'),
  });
}

function renderReportTable(rows) {
  const wrap = document.getElementById('report-result');
  if (!rows.length) {
    wrap.innerHTML = '<p class="panel-note" style="padding:1rem">No data found for the selected filters.</p>';
    return;
  }
  const tableHTML = `
    <table>
      <thead>
        <tr>
          <th>Student ID</th>
          <th>Name</th>
          <th>Course</th>
          <th>Section</th>
          <th>Present</th>
          <th>Total</th>
          <th>%</th>
        </tr>
      </thead>
      <tbody>
        ${rows.map((r) => {
          const pct = r.total > 0 ? Math.round((r.present / r.total) * 100) : 0;
          const cls = pct < 75 ? 'pct-low' : 'pct-ok';
          return `<tr>
            <td>${r.student_id}</td>
            <td class="col-name">${r.student_name}</td>
            <td>${r.course_code}</td>
            <td>${r.section}</td>
            <td>${r.present}</td>
            <td>${r.total}</td>
            <td class="${cls}">${pct}%</td>
          </tr>`;
        }).join('')}
      </tbody>
    </table>
  `;
  wrap.classList.add('table-fluid');
  wrap.innerHTML = tableHTML;
}

// ══════════════════════════════════════════
//  STATS REFRESH
// ══════════════════════════════════════════

function loadStats() {
  $.ajax({
    url: 'api/stats.php',
    method: 'GET',
    success: (data) => {
      document.getElementById('stat-teachers').textContent = data.teachers;
      document.getElementById('stat-students').textContent = data.students;
      document.getElementById('stat-courses').textContent  = data.courses;
      document.getElementById('stat-users').textContent    = data.users;
    },
  });
}
