document.addEventListener("DOMContentLoaded", function () {
  const monthYear = document.getElementById("month-year");
  const calendarBody = document.getElementById("calendar-body");
  const prevMonthBtn = document.querySelector(".prev-month");
  const nextMonthBtn = document.querySelector(".next-month");

  let currentDate = new Date();

  function generateCalendar(date) {
    calendarBody.innerHTML = ""; 
    const year = date.getFullYear();
    const month = date.getMonth();

    const firstDay = new Date(year, month, 1).getDay();
    const totalDays = new Date(year, month + 1, 0).getDate();

    monthYear.textContent = `${date.toLocaleString("default", {
      month: "long",
    })} ${year}`;

    let row = document.createElement("tr");
    let cellCount = 0;

    for (let i = 0; i < firstDay; i++) {
      let emptyCell = document.createElement("td");
      row.appendChild(emptyCell);
      cellCount++;
    }

    for (let day = 1; day <= totalDays; day++) {
      let cell = document.createElement("td");
      cell.textContent = day;
      row.appendChild(cell);
      cellCount++;

      if (cellCount % 7 === 0) {
        calendarBody.appendChild(row);
        row = document.createElement("tr");
        cellCount = 0;
      }
    }

    if (cellCount > 0) {
      for (let i = cellCount; i < 7; i++) {
        let emptyCell = document.createElement("td");
        row.appendChild(emptyCell);
      }
      calendarBody.appendChild(row);
    }
  }

  prevMonthBtn.addEventListener("click", function () {
    currentDate.setMonth(currentDate.getMonth() - 1);
    generateCalendar(currentDate);
  });

  nextMonthBtn.addEventListener("click", function () {
    currentDate.setMonth(currentDate.getMonth() + 1);
    generateCalendar(currentDate);
  });

  generateCalendar(currentDate);
});
