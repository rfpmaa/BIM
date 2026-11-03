(function () {
    const moduleTitles = {
        1: "Modul 1: Apa itu BIM?",
        2: "Modul 2: Pemodelan 3D",
        3: "Modul 3: Kolaborasi & CDE",
        4: "Modul 4: Manajemen Proyek"
    };
    let quizData = {};
    let currentModule = 1;

    function renderQuiz(moduleId) {
        const wrapper = document.getElementById("questions-wrapper");
        const form = document.getElementById("quiz-form");
        const data = quizData[moduleId];
        wrapper.replaceChildren();
        document.getElementById("module-title-text").textContent = moduleTitles[moduleId];

        if (!data || !data.questions.length) {
            const emptyMessage = document.createElement("p");
            emptyMessage.className = "quiz-empty";
            emptyMessage.textContent = "Belum ada soal untuk modul ini.";
            wrapper.appendChild(emptyMessage);
            form.querySelectorAll("button").forEach(button => button.disabled = true);
            updateProgress();
            document.getElementById("result-card").style.display = "none";
            return;
        }

        form.querySelectorAll("button").forEach(button => button.disabled = false);
        data.questions.forEach((question, index) => {
            const card = document.createElement("article");
            card.className = "quiz-card";
            const head = document.createElement("div");
            head.className = "quiz-card-head";
            const number = document.createElement("div");
            number.className = "q-number";
            number.textContent = index + 1;
            const questionText = document.createElement("h3");
            questionText.className = "q-text";
            questionText.textContent = question.q;
            head.append(number, questionText);

            const optionsList = document.createElement("div");
            optionsList.className = "options-list";
            question.options.forEach((option, optionIndex) => {
                const label = document.createElement("label");
                label.className = "option-item";
                const radio = document.createElement("input");
                radio.type = "radio";
                radio.name = "q_" + index;
                radio.value = optionIndex;
                radio.addEventListener("change", updateProgress);
                const letter = document.createElement("span");
                letter.className = "opt-letter";
                letter.textContent = String.fromCharCode(65 + optionIndex);
                const optionText = document.createElement("span");
                optionText.className = "opt-text";
                optionText.textContent = option;
                label.append(radio, letter, optionText);
                optionsList.appendChild(label);
            });
            card.append(head, optionsList);
            wrapper.appendChild(card);
        });

        updateProgress();
        document.getElementById("result-card").style.display = "none";
    }

    function switchModule(moduleId) {
        currentModule = Number(moduleId);
        document.querySelectorAll(".tab-btn").forEach((button, index) => {
            button.classList.toggle("active", index + 1 === currentModule);
        });
        renderQuiz(currentModule);
    }

    function updateProgress() {
        const questions = quizData[currentModule]?.questions || [];
        const total = questions.length;
        let answered = 0;
        for (let index = 0; index < total; index++) {
            if (document.querySelector(`input[name="q_${index}"]:checked`)) answered++;
        }
        document.getElementById("progress-fill").style.width = (total ? answered / total * 100 : 0) + "%";
        document.getElementById("progress-text").textContent = total
            ? `${answered} dari ${total} Terjawab`
            : "Belum ada soal";
    }

    function resetCurrentModule() {
        document.getElementById("quiz-form").reset();
        updateProgress();
        document.getElementById("result-card").style.display = "none";
    }

    function checkQuiz(event) {
        event.preventDefault();
        const questions = quizData[currentModule]?.questions || [];
        const total = questions.length;
        if (!total) return;

        let correctCount = 0;
        let answeredCount = 0;
        questions.forEach((question, index) => {
            const selected = document.querySelector(`input[name="q_${index}"]:checked`);
            if (selected) {
                answeredCount++;
                if (Number(selected.value) === question.answer) correctCount++;
            }
        });

        if (answeredCount < total) {
            alert(`Harap selesaikan seluruh ${total} soal pada modul ini sebelum memeriksa jawaban!`);
            return;
        }

        const score = Math.round(correctCount / total * 100);
        const resultCard = document.getElementById("result-card");
        const resultBadge = document.getElementById("result-badge");
        document.getElementById("result-score").textContent = `${score} / 100`;
        resultCard.style.display = "block";
        if (score >= 80) {
            resultBadge.style.background = "#dcfce7";
            resultBadge.style.color = "#15803d";
            resultBadge.innerHTML = '<i class="fa-solid fa-medal"></i>';
            document.getElementById("result-title").textContent = "Luar Biasa! Pemahaman Sempurna";
            document.getElementById("result-desc").textContent = `Kamu berhasil menjawab ${correctCount} dari ${total} soal dengan benar pada ${moduleTitles[currentModule]}. Silakan beralih ke modul berikutnya!`;
        } else {
            resultBadge.style.background = "#fef9c3";
            resultBadge.style.color = "#a16207";
            resultBadge.innerHTML = '<i class="fa-solid fa-book-open"></i>';
            document.getElementById("result-title").textContent = "Perlu Ditinjau Kembali";
            document.getElementById("result-desc").textContent = `Kamu menjawab ${correctCount} dari ${total} soal dengan benar. Simak kembali video dan rangkuman pada ${moduleTitles[currentModule]} untuk hasil yang lebih baik.`;
        }
        resultCard.scrollIntoView({ behavior: "smooth" });
    }

    window.renderQuiz = renderQuiz;
    window.switchModule = switchModule;
    window.updateProgress = updateProgress;
    window.resetCurrentModule = resetCurrentModule;
    window.checkQuiz = checkQuiz;

    fetch("../latihan_api.php", { credentials: "same-origin", cache: "no-store" })
        .then(response => {
            if (!response.ok) throw new Error("Permintaan soal gagal.");
            return response.json();
        })
        .then(result => {
            if (result.status !== "success" || !Array.isArray(result.data)) {
                throw new Error(result.message || "Format data soal tidak valid.");
            }
            quizData = {};
            result.data.forEach(item => {
                const moduleId = Number(item.modul);
                if (!moduleTitles[moduleId] || !Array.isArray(item.options) || item.options.length !== 4) return;
                if (!quizData[moduleId]) quizData[moduleId] = { questions: [] };
                quizData[moduleId].questions.push({
                    q: String(item.pertanyaan),
                    options: item.options.map(String),
                    answer: Number(item.answer)
                });
            });
            renderQuiz(currentModule);
        })
        .catch(() => {
            const wrapper = document.getElementById("questions-wrapper");
            wrapper.replaceChildren();
            const errorMessage = document.createElement("p");
            errorMessage.className = "quiz-empty";
            errorMessage.textContent = "Soal gagal dimuat. Muat ulang halaman atau hubungi guru.";
            wrapper.appendChild(errorMessage);
            document.querySelectorAll("#quiz-form button").forEach(button => button.disabled = true);
        });
})();
