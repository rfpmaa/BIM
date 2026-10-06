const moduleDefinitions = {
    1: {
        title: "Pengenalan BIM",
        description: "Dasar, konsep, dan manfaat BIM untuk alur kerja konstruksi modern.",
        lessons: ["Apa itu Building Information Modeling?", "Manfaat BIM dalam proyek", "Alur kerja dan istilah dasar BIM"]
    },
    2: {
        title: "Pemodelan 3D",
        description: "Praktik membuat model bangunan tiga dimensi menggunakan Revit.",
        lessons: ["Mengenal antarmuka Revit", "Membuat elemen arsitektur", "Mengatur level dan tampilan"]
    },
    3: {
        title: "Kolaborasi & BIM 360",
        description: "Kerja tim dalam proyek dengan pembagian informasi yang terkoordinasi.",
        lessons: ["Peran anggota tim BIM", "Berbagi model melalui cloud", "Koordinasi dan review model"]
    },
    4: {
        title: "Manajemen Proyek",
        description: "Memahami 4D, 5D, dan visualisasi untuk membantu pengambilan keputusan.",
        lessons: ["Simulasi waktu dengan BIM 4D", "Estimasi biaya dengan BIM 5D", "Visualisasi dan laporan proyek"]
    }
};

function getYouTubeEmbedUrl(rawUrl) {
    if (!rawUrl) return "";
    try {
        const url = new URL(rawUrl);
        const host = url.hostname.toLowerCase().replace(/^www\./, "");
        let videoId = "";

        if (host === "youtu.be") {
            videoId = url.pathname.split("/").filter(Boolean)[0] || "";
        } else if (["youtube.com", "m.youtube.com", "youtube-nocookie.com"].includes(host)) {
            if (url.pathname === "/watch") videoId = url.searchParams.get("v") || "";
            else videoId = url.pathname.match(/^\/(?:embed|shorts|live)\/([\w-]{11})/)?.[1] || "";
        }

        return /^[\w-]{11}$/.test(videoId)
            ? "https://www.youtube-nocookie.com/embed/" + videoId
            : "";
    } catch (error) {
        return "";
    }
}

function renderModuleMaterials(items, fallbackLessons) {
    const container = document.getElementById("module-material-list");
    container.replaceChildren();

    if (!items.length) {
        const article = document.createElement("article");
        article.className = "module-material";
        const title = document.createElement("h2");
        title.textContent = "Pokok bahasan";
        const list = document.createElement("ul");
        list.className = "module-default-lessons";
        fallbackLessons.forEach(function(lesson) {
            const item = document.createElement("li");
            item.textContent = lesson;
            list.appendChild(item);
        });
        article.append(title, list);
        container.appendChild(article);
        return;
    }

    items.forEach(function(item) {
        const article = document.createElement("article");
        article.className = "module-material";
        const title = document.createElement("h2");
        title.textContent = item.judul || "Materi pembelajaran";
        article.appendChild(title);

        if (item.ringkasan) {
            const summary = document.createElement("p");
            summary.className = "module-material-summary";
            summary.textContent = item.ringkasan;
            article.appendChild(summary);
        }

        if (item.isi) {
            const content = document.createElement("p");
            content.className = "module-material-content";
            content.textContent = item.isi;
            article.appendChild(content);
        }

        const embedUrl = getYouTubeEmbedUrl(item.video_url);
        if (embedUrl) {
            const videos = document.createElement("div");
            videos.className = "module-video-list";
            const video = document.createElement("section");
            video.className = "module-video";
            const videoTitle = document.createElement("h3");
            videoTitle.textContent = "Video: " + (item.judul || "Materi pembelajaran");
            const frame = document.createElement("iframe");
            frame.className = "module-video-frame";
            frame.src = embedUrl;
            frame.title = "Video YouTube: " + (item.judul || "Materi pembelajaran");
            frame.loading = "lazy";
            frame.allow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture";
            frame.allowFullscreen = true;
            frame.referrerPolicy = "strict-origin-when-cross-origin";
            video.append(videoTitle, frame);
            videos.appendChild(video);
            article.appendChild(videos);
        }

        container.appendChild(article);
    });
}

document.addEventListener("DOMContentLoaded", function() {
    const moduleNo = Number(document.body.dataset.module);
    const definition = moduleDefinitions[moduleNo];
    if (!definition) return;

    document.title = "Modul " + moduleNo + " · " + definition.title + " - BIM Learn";
    document.getElementById("module-number").textContent = String(moduleNo).padStart(2, "0");
    document.getElementById("module-title").textContent = moduleNo + ". " + definition.title;
    document.getElementById("module-description").textContent = definition.description;
    document.querySelector(".complete-btn").addEventListener("click", function() {
        selesaiModul(moduleNo);
    });

    fetch("../materi_public.php", { credentials: "same-origin", cache: "no-store" })
        .then(response => response.json())
        .then(result => {
            if (result.status !== "success") throw new Error(result.message || "Materi gagal dimuat.");
            const items = (result.data || []).filter(item => Number(item.modul) === moduleNo);
            renderModuleMaterials(items, definition.lessons);
        })
        .catch(function() {
            const container = document.getElementById("module-material-list");
            const error = document.createElement("p");
            error.className = "module-load-error";
            error.textContent = "Materi gagal dimuat. Periksa koneksi lalu coba lagi.";
            container.replaceChildren(error);
        });
});