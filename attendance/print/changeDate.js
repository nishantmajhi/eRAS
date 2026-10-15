document.addEventListener("DOMContentLoaded", function () {
  const datePicker = document.querySelector('input[type="date"]');

  function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
  }

  const courseCode = getQueryParam("courseCode");
  const semester = getQueryParam("courseCode");
  const section = getQueryParam("section");

  datePicker.addEventListener("change", function () {
    const selectedDate = datePicker.value;
    const url =
      window.location.origin +
      `/attendance/print/?createdDate=${selectedDate}&courseCode=${courseCode}&semester=${semester}&section=${section}`;
    window.open(url, "_self");
  });
});
