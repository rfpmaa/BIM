document.addEventListener("DOMContentLoaded", async function() {
    const list = document.getElementById("learning-outcomes-list");
    const count = document.getElementById("learning-outcomes-count");
    if (!list || !count) return;

    try {
        const response = await fetch("../cp_tp_api.php", { credentials: "same-origin", cache: "no-store" });
        const result = await response.json();
        if (!response.ok || result.status !== "success") {
            throw new Error(result.message || "Capaian pembelajaran gagal dimuat.");
        }

        list.replaceChildren();
        count.textContent = result.data.length + " data";
        if (!result.data.length) {
            const empty = document.createElement("p");
            empty.className = "learning-outcomes-state";
            empty.textContent = "Capaian dan tujuan pembelajaran akan ditampilkan di sini.";
            list.appendChild(empty);
            return;
        }

        result.data.forEach(function(item) {
            const card = document.createElement("article");
            card.className = "learning-outcome-card";

            const number = document.createElement("div");
            number.className = "learning-outcome-number";
            number.textContent = String(item.no_soal).padStart(2, "0");
            number.setAttribute("aria-label", "Nomor soal " + item.no_soal);

            const content = document.createElement("div");
            content.className = "learning-outcome-content";

            const heading = document.createElement("div");
            heading.className = "learning-outcome-card-heading";
            const title = document.createElement("h4");
            title.textContent = "Soal " + item.no_soal;
            const score = document.createElement("span");
            score.className = "learning-outcome-score";
            score.textContent = "Skor " + Number(item.skor).toLocaleString("id-ID");
            heading.append(title, score);

            const descriptions = [
                ["Capaian Pembelajaran", item.capaian_pembelajaran],
                ["Tujuan Pembelajaran", item.tujuan_pembelajaran],
                ["Kriteria Ketercapaian Tujuan Pembelajaran", item.kriteria_ketercapaian]
            ];
            const details = document.createElement("div");
            details.className = "learning-outcome-details";
            descriptions.forEach(function(entry) {
                const detail = document.createElement("div");
                detail.className = "learning-outcome-detail";
                const label = document.createElement("span");
                label.textContent = entry[0];
                const text = document.createElement("p");
                text.textContent = entry[1];
                detail.append(label, text);
                details.appendChild(detail);
            });

            const tags = document.createElement("div");
            tags.className = "learning-outcome-tags";
            const cognitive = document.createElement("span");
            cognitive.textContent = item.kemampuan_kognitif;
            const difficulty = document.createElement("span");
            difficulty.textContent = item.tingkat_kesukaran;
            tags.append(cognitive, difficulty);

            content.append(heading, details, tags);
            card.append(number, content);
            list.appendChild(card);
        });
    } catch (error) {
        list.replaceChildren();
        const state = document.createElement("p");
        state.className = "learning-outcomes-state learning-outcomes-error";
        state.textContent = error.message || "Capaian pembelajaran gagal dimuat.";
        list.appendChild(state);
        count.textContent = "Tidak tersedia";
    }
});
