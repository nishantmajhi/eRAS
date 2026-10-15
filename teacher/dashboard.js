document.addEventListener('DOMContentLoaded', () => {
  const actionSection = document.getElementById('actions');
  actionSection.style.display = "block";
  
  const periodSection = document.getElementById('periods');
  periodSection.style.display = 'none';
 
  const urlParams = new URLSearchParams(window.location.search);
  const action = urlParams.get('action');
  switch (action) {
    case 'new':
      handleActions('new');
      break;
    case 'update':
      handleActions('update');
      break;
    case 'print':
      handleActions('print');
      break;
    default:
      renderBreadcrumbs();
  }
  
  const newAttendance = document.getElementById('new');
  const updateAttendance = document.getElementById('update');
  const printAttendance = document.getElementById('print');
  
  newAttendance.addEventListener('click', () => {
    handleActions('new');
  });
  updateAttendance.addEventListener("click", () => {
    handleActions('update');
  });
  printAttendance.addEventListener("click", () => {
    handleActions('print');
  });
  
  function handleActions(type) {
    renderBreadcrumbs(type);
    actionSection.style.display = 'none';
    renderPeriods(type);
    periodSection.style.display = 'block';  
  }
});