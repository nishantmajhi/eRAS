function renderCalendar() {
  const mainElement = document.querySelector("main");
  mainElement.innerHTML = "";

  const today = document.getElementsByName("fullDate")[0].value;
  const [yearStr, monthStr] = today.split("-");
  const year = parseInt(yearStr, 10);
  const month = parseInt(monthStr, 10) - 1;

  const title = document.createElement("nav");
  const monthOfYear = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];

  const prev = document.createElement("button");
  prev.type = "button";
  prev.textContent = "<<";

  const prevMonth = month === 0 ? 11 : month - 1;
  const prevYear = month === 0 ? year - 1 : year;
  prev.title = `${monthOfYear[prevMonth]} - ${prevYear}`;
  prev.addEventListener("click", () => {
    const monthStr = String(prevMonth + 1).padStart(2, "0");
    window.location.href = `${window.location.origin}/student/?month=${prevYear}-${monthStr}`;
  });
  title.appendChild(prev);

  const info = document.createElement("h2");
  info.textContent = `${monthOfYear[month]} - ${year}`;
  info.id = "info";
  title.appendChild(info);

  const next = document.createElement("button");
  next.type = "button";
  next.textContent = ">>";

  const nextIndex = month === 11 ? 0 : month + 1;
  const nextYear = month === 11 ? year + 1 : year;
  next.title = `${monthOfYear[nextIndex]} - ${nextYear}`;
  next.addEventListener("click", () => {
    const monthStr = String(nextIndex + 1).padStart(2, "0");
    window.location.href = `${window.location.origin}/student/?month=${nextYear}-${monthStr}`;
  });
  title.appendChild(next);

  mainElement.appendChild(title);

  const thead = document.createElement("thead");
  const table = document.createElement("table");
  const tr = document.createElement("tr");

  const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
  daysOfWeek.forEach((day) => {
    const th = document.createElement("th");
    th.textContent = day;
    th.className = "days";
    tr.appendChild(th);
  });

  thead.appendChild(tr);
  table.appendChild(thead);

  const tbody = document.createElement("tbody");
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startDay = firstDay.getDay();
  const totalDays = lastDay.getDate();

  let dayCounter = 1;
  const totalCells = Math.ceil((startDay + totalDays) / 7) * 7;

  for (let i = 0; i < totalCells; i++) {
    if (i % 7 === 0) {
      var row = document.createElement("tr");
      tbody.appendChild(row);
    }

    const cell = document.createElement("td");

    if (i >= startDay && dayCounter <= totalDays) {
      cell.textContent = dayCounter;
      dayCounter++;
    } else {
      cell.textContent = "";
    }

    row.appendChild(cell);
  }

  table.appendChild(tbody);
  mainElement.appendChild(table);
}

function renderVisuals() {
  const calendarDays = document.querySelectorAll("table td");
  const attendanceRecords = attendanceData.attendance;

  const totalCourses = new Set(attendanceRecords.map((record) => record.course))
    .size;

  const attendanceByDate = {};
  attendanceRecords.forEach((record) => {
    if (!attendanceByDate[record.date]) {
      attendanceByDate[record.date] = { present: [], absent: [] };
    }
    if (record.status === "P") {
      attendanceByDate[record.date].present.push(record.course);
    } else {
      attendanceByDate[record.date].absent.push(record.course);
    }
  });

  const today = new Date();
  const currentYear = today.getFullYear();
  const currentMonth = today.getMonth() + 1;

  calendarDays.forEach((dayElement) => {
    const day = dayElement.textContent.padStart(2, "0");
    const formattedDate = `${currentYear}-${currentMonth
      .toString()
      .padStart(2, "0")}-${day}`;

    if (attendanceByDate[formattedDate]) {
      const { present, absent } = attendanceByDate[formattedDate];

      if (present.length === totalCourses) {
        dayElement.style.backgroundColor = "lightgreen";
      } else if (present.length > 0) {
        dayElement.style.backgroundColor = "hsl(22, 99%, 77%)";
      } else {
        dayElement.style.color = "white";
        dayElement.style.backgroundColor = "#ee161f";
      }

      dayElement.addEventListener("click", () => {
        const dateObject = new Date(formattedDate);
        const nameOfDay = dateObject.toLocaleDateString("en-US", {
          weekday: "long",
        });

        if (present.length === totalCourses) {
          alertDialog(
            `${nameOfDay}, ${formattedDate}`,
            "Present in all subjects."
          );
        } else if (absent.length === totalCourses) {
          alertDialog(
            `${nameOfDay}, ${formattedDate}`,
            "Absent in all subjects."
          );
        } else {
          alertDialog(
            `${nameOfDay}, ${formattedDate}`,
            `<div>Present: ${present.join(
              ", "
            )}</div><div>Absent: ${absent.join(", ")}</div>`
          );
        }
      });
    }
  });
}
