// ==========================================
// 1. FUNGSI UNTUK LOGIN, LOGOUT & CEK AKSES
// ==========================================
function verifikasiSesi() {
    if (window.location.pathname.endsWith("login.html")) return;

    fetch("../session_status.php", { credentials: "same-origin" })
        .then(response => response.json())
        .then(data => {
            if (!data.authenticated) {
                localStorage.removeItem("isLoggedIn");
                sessionStorage.setItem("pageNotification", JSON.stringify({
                    message: "Sesi Anda berakhir. Silakan masuk kembali.",
                    status: "error"
                }));
                window.location.replace("login.html");
            }
        })
        .catch(() => {
            tampilkanNotifikasi("Tidak dapat memeriksa sesi login.", "error");
        });
}

function cekLogin() {
    let statusLogin = localStorage.getItem("isLoggedIn");
    
    if (statusLogin === "true") {
        window.location.href = "profil.html";
    } else {
        sessionStorage.setItem("pageNotification", JSON.stringify({
            message: "Anda belum login. Silakan masuk terlebih dahulu.",
            status: "error"
        }));
        window.location.href = "login.html";
    }
}

function simulasiLogin() {
    localStorage.setItem("isLoggedIn", "true");
    tampilkanNotifikasi("Login berhasil! Selamat datang.", "success");
    window.location.href = "index.html"; 
}

function simulasiLogout() {
    fetch("../logout.php", { method: "POST", credentials: "same-origin" })
        .then(() => {
            localStorage.removeItem("isLoggedIn");
            sessionStorage.setItem("pageNotification", JSON.stringify({
                message: "Anda telah keluar dari akun.",
                status: "success"
            }));
            window.location.href = "login.html";
        })
        .catch(() => tampilkanNotifikasi("Logout gagal. Silakan coba lagi.", "error"));
}

function tampilkanNotifikasi(pesan, status) {
    let notification = document.createElement("div");
    let icon = status === "error" ? "fa-circle-exclamation" : "fa-circle-check";
    notification.className = "page-notification " + (status === "error" ? "notification-error" : "notification-success");
    notification.setAttribute("role", "status");
    notification.innerHTML = '<i class="fa-solid ' + icon + '"></i><span>' + pesan + '</span><button type="button" aria-label="Tutup notifikasi"><i class="fa-solid fa-xmark"></i></button>';
    document.body.appendChild(notification);
    notification.querySelector("button").addEventListener("click", function() {
        notification.remove();
    });
    setTimeout(function() {
        if (notification.isConnected) notification.remove();
    }, 4500);
}

// ==========================================
// 2. FUNGSI UNTUK PROGRES MODUL
// ==========================================
function getProgressKey(nomorModul) {
    let userId = localStorage.getItem("userId") || "guest";
    return "progres_modul_" + userId + "_" + nomorModul;
}

async function selesaiModul(nomorModul) {
    localStorage.setItem(getProgressKey(nomorModul), "100");
    try {
        let sessionResponse = await fetch("../session_status.php?t=" + Date.now(), {
            credentials: "same-origin",
            cache: "no-store"
        });
        let sessionResult = await sessionResponse.json();
        if (!sessionResult.authenticated || sessionResult.data.role !== "siswa") {
            throw new Error("Sesi siswa tidak ditemukan. Silakan login ulang.");
        }

        let response = await fetch("../progress_api.php?t=" + Date.now(), {
            method: "POST",
            credentials: "same-origin",
            cache: "no-store",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ modul: nomorModul, progress: 100 })
        });
        let result = await response.json();
        if (!response.ok || result.status !== "success") {
            throw new Error(result.message || "Progress gagal disimpan.");
        }
        updateProgressDisplays();
        tampilkanNotifikasi("Modul " + nomorModul + " tersimpan untuk akun " + sessionResult.data.email + ".", "success");
    } catch (error) {
        localStorage.removeItem(getProgressKey(nomorModul));
        updateProgressDisplays();
        tampilkanNotifikasi(error.message || "Progress gagal disimpan.", "error");
    }
}

function updateProgressDisplays() {
    for (let i = 1; i <= 4; i++) {
        let progress = localStorage.getItem(getProgressKey(i)) || 0;
        let homeBar = document.getElementById("bar-modul-" + i);
        let homeText = document.getElementById("text-modul-" + i);
        let moduleBar = document.getElementById("module-progress-bar-" + i);
        let moduleText = document.getElementById("module-progress-text-" + i);

        if (homeBar) homeBar.style.width = progress + "%";
        if (homeText) homeText.innerText = progress + "%";
        if (moduleBar) moduleBar.style.width = progress + "%";
        if (moduleText) moduleText.innerText = progress + "%";
    }
}

// ==========================================
// 3. FUNGSI KHUSUS HALAMAN PROFIL
// ==========================================
function previewDanSimpanFoto(event) {
    let file = event.target.files[0];
    if (!file) return;
    if (!file.type.startsWith("image/") || file.size > 2 * 1024 * 1024) {
        tampilkanNotifikasi("Foto harus berupa gambar dengan ukuran maksimal 2 MB.", "error");
        event.target.value = "";
        return;
    }

    let reader = new FileReader();
    reader.onload = function(){
        let dataURL = reader.result; // Mengubah gambar fisik jadi data teks base64
        
        // Tampilkan di lingkaran profil besar
        document.getElementById("img-profil-preview").src = dataURL;
        document.getElementById("img-profil-preview").style.display = "block";
        document.getElementById("icon-profil-default").style.display = "none";
        
        // Simpan ke memori browser
        localStorage.setItem("fotoProfil", dataURL);
        
        // Langsung update juga foto di header kanan atas
        let topIcon = document.getElementById("ikon-profil-header");
        if(topIcon) {
            let profileImage = document.createElement("img");
            profileImage.src = dataURL;
            profileImage.alt = "Foto profil";
            topIcon.replaceChildren(profileImage);
        }
    };
    reader.readAsDataURL(event.target.files[0]);
}

function simpanProfil() {
    let apiFields = {
        nama: "input-nama",
        email: "input-email",
        nis: "input-nis",
        kelas: "input-kelas",
        jurusan: "input-jurusan",
        jenis_kelamin: "input-jk",
        sekolah: "input-sekolah",
        telepon: "input-telepon",
        alamat: "input-alamat"
    };

    let formData = new FormData();

    Object.entries(apiFields).forEach(function([fieldName, inputId]) {
        let elem = document.getElementById(inputId);
        let val = elem ? elem.value.trim() : "";
        formData.append(fieldName, val);
        localStorage.setItem(fieldName + "User", val);
    });
    
    formData.append("foto", localStorage.getItem("fotoProfil") || "");

    fetch("../profil_api.php", { method: "POST", body: formData, credentials: "same-origin" })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                tampilkanNotifikasi(data.message, "success");
            } else {
                tampilkanNotifikasi(data.message, "error");
            }
        })
        .catch(() => tampilkanNotifikasi("Profil gagal disimpan karena koneksi bermasalah.", "error"));
}

function sinkronkanProfilDariServer() {
    if (window.location.pathname.endsWith("login.html")) return;

    fetch("../profil_api.php", { credentials: "same-origin" })
        .then(response => response.json())
        .then(result => {
            if (result.status !== "success" || !result.data) return;
            let profile = result.data;
            let storedFields = {
                namaUser: profile.nama,
                emailUser: profile.email,
                nisUser: profile.nis,
                kelasUser: profile.kelas,
                jurusanUser: profile.jurusan,
                jenisKelaminUser: profile.jenis_kelamin,
                sekolahUser: profile.sekolah,
                teleponUser: profile.telepon,
                alamatUser: profile.alamat
            };
            Object.entries(storedFields).forEach(function([key, value]) {
                localStorage.setItem(key, value || "");
            });
            if (profile.foto) {
                localStorage.setItem("fotoProfil", profile.foto);
            } else {
                localStorage.removeItem("fotoProfil");
            }

            let name = document.getElementById("greeting-user");
            let firstName = (profile.nama || "").trim().split(/\s+/)[0] || "Peserta";
            if (name) name.textContent = "Selamat Datang, " + firstName + "!";
            let headerName = document.getElementById("nama-profil-header");
            if (headerName) headerName.textContent = profile.nama || "Profil Saya";
            let headerIcon = document.getElementById("ikon-profil-header");
            if (headerIcon) {
                if (profile.foto) {
                    let profileImage = document.createElement("img");
                    profileImage.src = profile.foto;
                    profileImage.alt = "Foto profil";
                    headerIcon.replaceChildren(profileImage);
                } else {
                    headerIcon.replaceChildren();
                    let defaultIcon = document.createElement("i");
                    defaultIcon.className = "fa-solid fa-user";
                    headerIcon.appendChild(defaultIcon);
                }
            }

            if (document.getElementById("img-profil-preview")) {
                let profileInputs = {
                    "input-nama": profile.nama,
                    "input-email": profile.email,
                    "input-nis": profile.nis,
                    "input-kelas": profile.kelas,
                    "input-jurusan": profile.jurusan,
                    "input-jk": profile.jenis_kelamin,
                    "input-sekolah": profile.sekolah,
                    "input-telepon": profile.telepon,
                    "input-alamat": profile.alamat
                };
                Object.entries(profileInputs).forEach(function([id, value]) {
                    document.getElementById(id).value = value || "";
                });
                if (profile.foto) {
                    document.getElementById("img-profil-preview").src = profile.foto;
                    document.getElementById("img-profil-preview").style.display = "block";
                    document.getElementById("icon-profil-default").style.display = "none";
                }
            }
        });
}

function sinkronkanProgressDariServer() {
    if (window.location.pathname.endsWith("login.html")) return;
    fetch("../progress_api.php?t=" + Date.now(), { credentials: "same-origin", cache: "no-store" })
        .then(response => response.json())
        .then(result => {
            if (result.status !== "success") return;
            for (let modul = 1; modul <= 4; modul++) {
                localStorage.setItem(getProgressKey(modul), "0");
            }
            result.data.forEach(function(item) {
                localStorage.setItem(getProgressKey(item.modul), item.progress);
            });
            updateProgressDisplays();
        });
}

// ==========================================
// 4. JALANKAN OTOMATIS SAAT HALAMAN APAPUN DIBUKA
// ==========================================
document.addEventListener("DOMContentLoaded", function() {
    verifikasiSesi();
    sinkronkanProfilDariServer();
    sinkronkanProgressDariServer();
    let statusLogin = localStorage.getItem("isLoggedIn");
    let savedPic = localStorage.getItem("fotoProfil");
    let greetingUser = document.getElementById("greeting-user");

    if (greetingUser) {
        let namaLengkap = localStorage.getItem("namaUser") || "";
        let namaDepan = namaLengkap.trim().split(/\s+/)[0] || "Peserta";
        greetingUser.textContent = "Selamat Datang, " + namaDepan + "!";
    }

    // A. Atur Tampilan Header Kanan Atas (Login vs Ikon Profil/Foto Custom)
    let btnLogin = document.getElementById("btn-login-header");
    let iconProfil = document.getElementById("ikon-profil-header");
    let namaProfil = document.getElementById("nama-profil-header");
    
    if (btnLogin && iconProfil) {
        if (statusLogin === "true") {
            iconProfil.style.display = "flex";
            btnLogin.style.display = "none";
            
            // Cek apakah user sudah pernah upload foto
            if (savedPic) {
                iconProfil.innerHTML = `<img src="${savedPic}" style="width:100%; height:100%; object-fit:cover;">`;
            } else {
                iconProfil.innerHTML = `<i class="fa-solid fa-user"></i>`;
            }
            if (namaProfil) {
                namaProfil.textContent = localStorage.getItem("namaUser") || "Profil Saya";
            }
        } else {
            iconProfil.style.display = "none";
            btnLogin.style.display = "block";
            if (namaProfil) {
                namaProfil.textContent = "";
            }
        }
    }

    // B. Atur progres di beranda dan halaman modul
    updateProgressDisplays();

    // C. Muat Data Tersimpan di Halaman Profil (Hanya jalan di profil.html)
    if (document.getElementById("img-profil-preview")) {
        let profileFields = {
            namaUser: "input-nama",
            emailUser: "input-email",
            nisUser: "input-nis",
            kelasUser: "input-kelas",
            jurusanUser: "input-jurusan",
            jenisKelaminUser: "input-jk",
            sekolahUser: "input-sekolah",
            teleponUser: "input-telepon",
            alamatUser: "input-alamat"
        };

        Object.entries(profileFields).forEach(function([storageKey, inputId]) {
            document.getElementById(inputId).value = localStorage.getItem(storageKey) || "";
        });
        
        // Tarik foto profil besar
        if (savedPic) {
            document.getElementById("img-profil-preview").src = savedPic;
            document.getElementById("img-profil-preview").style.display = "block";
            document.getElementById("icon-profil-default").style.display = "none";
        }
    }
});