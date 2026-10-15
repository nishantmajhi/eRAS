function renderPeriods(mode) {
  const section = document.getElementById("periods");
  section.innerHTML = "";

  const groupedPeriods = {};
  periods.forEach((period) => {
    if (!groupedPeriods[period.program_name]) {
      groupedPeriods[period.program_name] = [];
    }
    groupedPeriods[period.program_name].push(period);
  });

  for (const program in groupedPeriods) {
    const title = document.createElement("h3");
    title.className = "title";
    title.textContent = program;
    section.appendChild(title);

    const nav = document.createElement("nav");

    groupedPeriods[program].forEach((period) => {
      const article = document.createElement("article");

      const img = document.createElement("img");
      if (mode === "new") {
        img.src = "../assets/img/notebook.png";
        img.alt = "New Icon";
      } else if (mode === "update") {
        img.src = "../assets/img/rejected.png";
        img.alt = "Update Icon";
      } else if (mode === "print") {
        img.src = "../assets/img/printer.png";
        img.alt = "Print Icon";
      }

      const hgroup = document.createElement("hgroup");

      const h3 = document.createElement("h3");
      h3.textContent = `Semester - ${period.semester_no}${period.section}`;

      const h4 = document.createElement("h4");
      h4.textContent = period.course_code;

      hgroup.appendChild(h3);
      hgroup.appendChild(h4);

      article.appendChild(img);
      article.appendChild(hgroup);
      article.addEventListener("click", () => {
        const today = new Date().toISOString().split("T")[0];
        if (mode === "new") {
          const queryParams = {
            courseCode: period.course_code,
            semester: period.semester_no,
            section: period.section,
          };

          const queryString = new URLSearchParams(queryParams).toString();
          window.location.href = `${window.location.origin}/attendance/new/?${queryString}`;
        } else if (mode === "update") {
          const queryParams = {
            createdDate: today,
            courseCode: period.course_code,
            semester: period.semester_no,
            section: period.section,
          };

          const queryString = new URLSearchParams(queryParams).toString();
          window.location.href = `${window.location.origin}/attendance/update/?${queryString}`;
        } else if (mode === "print") {
          const queryParams = {
            createdDate: today,
            courseCode: period.course_code,
            semester: period.semester_no,
            section: period.section,
          };

          const queryString = new URLSearchParams(queryParams).toString();
          window.open(
            `${window.location.origin}/attendance/print/?${queryString}`,
            "_blank"
          );
        }
      });

      nav.appendChild(article);
    });

    section.appendChild(nav);
  }
}
