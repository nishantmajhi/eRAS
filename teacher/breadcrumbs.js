function renderBreadcrumbs(mode='') {
    const section = document.getElementById('breadcrumbs');
    section.innerHTML = '';

    const dashboard = document.createElement('span');
    dashboard.textContent = 'Dashboard';
    dashboard.addEventListener('click', () => {
        const url = new URL(window.location.href);
        url.search = '';
        window.location.href = url.toString();
    });
    section.appendChild(dashboard);

    if (mode === 'new') {
    const crumb = document.createElement('span');
    crumb.textContent = 'New Attendance';
    section.appendChild(crumb);
    } else if (mode === 'update') {
    const crumb = document.createElement('span');
    crumb.textContent = 'Update Attendance';
    section.appendChild(crumb);
    } else if (mode === 'print') {
    const crumb = document.createElement('span');
    crumb.textContent = 'Print Attendance';
    section.appendChild(crumb);
    }
}