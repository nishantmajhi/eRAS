document.addEventListener("DOMContentLoaded", () => {
  const title = capitalize("update attendance");
  document.title = title + document.title;

  const breadcrumbSection = document.getElementById("breadcrumbs");
  const dashboard = document.createElement("span");
  dashboard.textContent = "Dashboard";
  dashboard.addEventListener("click", () => {
    window.location.href = window.location.origin + "/teacher/";
  });
  breadcrumbSection.appendChild(dashboard);

  const span = document.createElement("span");
  span.textContent = title;
  span.addEventListener("click", () => {
    window.location.href = window.location.origin + "/teacher/?action=update";
  });
  breadcrumbSection.appendChild(span);

  const course = document.createElement("span");
  const courseCode = getQueryParam("courseCode");
  const semester = getQueryParam("semester");
  const section = getQueryParam("section");

  const date = document.createElement("input");
  date.title = "Choose date of attendance";
  date.type = "date";
  date.name = "date";

  const today = new Date().toISOString().split("T")[0];
  let createdDate = document.getElementsByName("createdDate")[0].value;
  if (!createdDate) createdDate = today;

  date.min = document.getElementsByName("firstDay")[0].value;
  date.max = today;
  date.value = createdDate;
  date.addEventListener("change", function () {
    const selectedDate = date.value;
    const url =
      window.location.origin +
      `/attendance/update/?createdDate=${selectedDate}&courseCode=${courseCode}&semester=${semester}&section=${section}`;
    window.open(url, "_self");
  });

  course.textContent = `${courseCode} - ${semester}${section} > `;
  course.appendChild(date);
  breadcrumbSection.appendChild(course);

  function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
  }

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

function capitalize(str) {
  return str
    .split(" ")
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1)) // Capitalize each word
    .join(" ");
}
