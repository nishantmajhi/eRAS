document.addEventListener("DOMContentLoaded", () => {
  const breadcrumbSection = document.getElementById('breadcrumbs');
  const dashboard = document.createElement('span');
  dashboard.textContent = 'Dashboard';
  dashboard.addEventListener('click', () => {
    window.location.href = window.location.origin + '/teacher/';
  });
  breadcrumbSection.appendChild(dashboard);
  
  const span = document.createElement('span');
  span.textContent = 'New Attendance';
  span.addEventListener('click', () => {
    window.location.href = window.location.origin + '/teacher/?action=new';
  });
  breadcrumbSection.appendChild(span);
  
  const course = document.createElement('span');
  course.textContent = document.getElementsByName('courseCode')[0].value + ' - ' + document.getElementsByName('semester')[0].value + document.getElementsByName('section')[0].value;
  breadcrumbSection.appendChild(course);

  document.querySelector("form").addEventListener("submit", function (event) {
    event.preventDefault();

    confirmDialog(
      "Confirm",
      "Are you sure you want to submit attendance?",
      (confirmed) => {
        if (confirmed) {
          event.target.submit();
        }
      }
    );
  });
});