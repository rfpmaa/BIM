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
        '<li><a href="cp-tp.html" class="' + (activePage === "cp-tp" ? "active" : "") + '"><i class="fa-solid fa-bullseye"></i> Kelola CP &amp; TP</a></li>' +
        '<li><a href="latihan.html" class="' + (activePage === "latihan" ? "active" : "") + '"><i class="fa-solid fa-clipboard-question"></i> Kelola Soal</a></li>' +
        '<li><a href="tugas.html" class="' + (activePage === "tugas" ? "active" : "") + '"><i class="fa-solid fa-file-arrow-down"></i> File Tugas</a></li>' +
        '<li><a href="siswa.html" class="' + (activePage === "siswa" ? "active" : "") + '"><i class="fa-solid fa-users"></i> Progres Siswa</a></li>' +
        '<li><a href="../user/index.html"><i class="fa-solid fa-arrow-left"></i> Portal Siswa</a></li>' +
        '</ul></aside>';
}

document.addEventListener("DOMContentLoaded", function() {
    let sidebar = document.getElementById("admin-sidebar");
    if (sidebar) {
        sidebar.innerHTML = adminSidebar(document.body.dataset.adminPage);
        document.body.classList.add("admin-page");

        let main = document.querySelector(".main-content");
        if (main) {
            let toggle = document.createElement("button");
            toggle.className = "admin-mobile-toggle";
            toggle.type = "button";
            toggle.setAttribute("aria-controls", "admin-sidebar");
            toggle.setAttribute("aria-expanded", "true");
            toggle.setAttribute("aria-label", "Buka menu admin");
            toggle.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
            main.prepend(toggle);

            let backdrop = document.createElement("button");
            backdrop.className = "admin-mobile-backdrop";
            backdrop.type = "button";
            backdrop.setAttribute("aria-label", "Tutup menu admin");
            document.body.appendChild(backdrop);

            let mobileQuery = window.matchMedia("(max-width: 760px)");
            function setMenuState(open) {
                if (mobileQuery.matches) {
                    document.body.classList.toggle("admin-menu-open", open);
                } else {
                    document.body.classList.toggle("admin-sidebar-collapsed", !open);
                }
                toggle.setAttribute("aria-expanded", String(open));
                toggle.setAttribute("aria-label", open ? "Tutup menu admin" : "Buka menu admin");
                toggle.innerHTML = '<i class="fa-solid fa-' + (open ? "xmark" : "bars") + '" aria-hidden="true"></i>';
            }

            function syncViewport() {
                document.body.classList.remove("admin-menu-open", "admin-sidebar-collapsed");
                setMenuState(!mobileQuery.matches);
            }

            toggle.addEventListener("click", function() {
                setMenuState(toggle.getAttribute("aria-expanded") !== "true");
            });
            backdrop.addEventListener("click", function() {
                setMenuState(false);
            });
            sidebar.querySelectorAll("a").forEach(function(link) {
                link.addEventListener("click", function() {
                    if (mobileQuery.matches) setMenuState(false);
                });
            });
            document.addEventListener("keydown", function(event) {
                if (event.key === "Escape" && document.body.classList.contains("admin-menu-open")) {
                    setMenuState(false);
                    toggle.focus();
                }
            });
            mobileQuery.addEventListener("change", syncViewport);
            syncViewport();
        }
    }
    let logoutButton = document.getElementById("admin-logout");
    if (logoutButton) logoutButton.addEventListener("click", logoutAdmin);
});