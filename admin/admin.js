function showAdminNotice(message, status) {
    let notice = document.getElementById("admin-notice");
    if (!notice) return;

    notice.textContent = message;
    notice.style.display = "block";
    notice.style.padding = "10px 14px";
    notice.style.marginBottom = "18px";
    notice.style.borderRadius = "8px";
    notice.style.fontSize = "13px";
    notice.style.fontWeight = "600";

    if (status === "error") {
        notice.style.background = "#fff1f2";
        notice.style.color = "#b91c1c";
        notice.style.border = "1px solid #fecaca";
    } else {
        notice.style.background = "#f0fdf4";
        notice.style.color = "#15803d";
        notice.style.border = "1px solid #bbf7d0";
    }

    setTimeout(() => {
        notice.style.display = "none";
    }, 3500);
}

function logoutAdmin() {
    fetch("../logout.php?scope=admin", { method: "POST", credentials: "same-origin" })
        .then(() => window.location.href = "login.html")
        .catch(() => showAdminNotice("Logout gagal. Silakan coba lagi.", "error"));
}

function adminSidebar(activePage) {
    return '<aside class="sidebar"><div class="logo"><i class="fa-solid fa-cube"></i><span>BIM Learn</span></div><ul>' +
        '<li><a href="dashboard.html" class="' + (activePage === "dashboard" ? "active" : "") + '"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>' +
        '<li><a href="materi.html" class="' + (activePage === "materi" ? "active" : "") + '"><i class="fa-solid fa-book"></i> Kelola Materi</a></li>' +
        '<li><a href="siswa.html" class="' + (activePage === "siswa" ? "active" : "") + '"><i class="fa-solid fa-users"></i> Progres Siswa</a></li>' +
        '<li><a href="../user/index.html"><i class="fa-solid fa-arrow-left"></i> Portal Siswa</a></li>' +
        '</ul></aside>';
}

document.addEventListener("DOMContentLoaded", function() {
    let sidebar = document.getElementById("admin-sidebar");
    if (sidebar) sidebar.innerHTML = adminSidebar(document.body.dataset.adminPage);
    let logoutButton = document.getElementById("admin-logout");
    if (logoutButton) logoutButton.addEventListener("click", logoutAdmin);
});