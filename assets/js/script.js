const toggleButton = document.getElementsByClassName("toggle-button")[0];
const navbarLinks = document.getElementsByClassName("navbar-links")[0];


toggleButton.addEventListener("click", () => {
    navbarLinks.classList.toggle("active");
});

/*const cursor = document.querySelector(".cursor");
const titles = document.querySelectorAll("h1");
gg
// Seguir o mouse
document.addEventListener("mousemove", (e) => {
  cursor.style.left = e.clientX + "px";
  cursor.style.top = e.clientY + "px";
});

// Quando passar no título
titles.forEach(title => {
  title.addEventListener("mouseenter", () => {
    cursor.classList.add("active");
  });

  title.addEventListener("mouseleave", () => {
    cursor.classList.remove("active");
  });
});*/

const cursor = document.querySelector(".cursor");
const titles = document.querySelectorAll("h1, lord-icon");

let mouseX = 0;
let mouseY = 0;

let posX = 0;
let posY = 0;

// Captura posição real do mouse
document.addEventListener("mousemove", (e) => {
  mouseX = e.clientX;
  mouseY = e.clientY;
});

// Efeito ao passar no título 
titles.forEach(title => {
  title.addEventListener("mouseenter", () => {
    cursor.classList.add("active");
  });

  title.addEventListener("mouseleave", () => {
    cursor.classList.remove("active");
  });
});

// Quando o mouse sai da janela
document.addEventListener("mouseleave", () => {
  cursor.classList.add("hidden");
});

// Quando o mouse volta
document.addEventListener("mouseenter", () => {
  cursor.classList.remove("hidden");
});

// Animação suave (50% mais rápido)
function animate() {
  posX += (mouseX - posX) * 0.12;
  posY += (mouseY - posY) * 0.12;

  cursor.style.left = posX + "px";
  cursor.style.top = posY + "px";

  requestAnimationFrame(animate);
}

animate();

const carrossel = document.querySelector(".carrossel");
const slides = document.querySelectorAll(".slide"); // Seleciona cada imagem/quadrado
let scrollPos = 0;
let speed = 0.8; 
let isPaused = false; // Controle de pausa

function moveCarrossel() {
    if (!carrossel || isPaused) {
        // Se estiver pausado, continua chamando o loop mas não move o scroll
        requestAnimationFrame(moveCarrossel);
        return;
    }

    scrollPos += speed;
    carrossel.scrollLeft = scrollPos;

    if (scrollPos >= (carrossel.scrollWidth - carrossel.clientWidth)) {
        scrollPos = 0;
    }

    requestAnimationFrame(moveCarrossel);
}

// Configura cada slide para pausar a animação ao entrar com o mouse
slides.forEach(slide => {
    slide.addEventListener("mouseenter", () => {
        isPaused = true;
    });
    slide.addEventListener("mouseleave", () => {
        isPaused = false;
    });
});

window.addEventListener("load", () => {
    moveCarrossel();
});

let lastScroll = 0;
const navbar = document.querySelector(".navbar");

window.addEventListener("scroll", () => {
  const currentScroll = window.pageYOffset;

  if (currentScroll > lastScroll) {
    // Descendo → esconde
    navbar.style.top = "-100px"; // ajuste conforme altura do seu navbar
  } else {
    // Subindo → mostra
    navbar.style.top = "0";
  }

  lastScroll = currentScroll <= 0 ? 0 : currentScroll;
});

const parallaxSections = document.querySelectorAll(".hero, .servicos");
const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

function updateParallax() {
    if (reduceMotion.matches) return;

    parallaxSections.forEach(section => {
        const rect = section.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        if (rect.bottom < 0 || rect.top > viewportHeight) return;

        const progress = (viewportHeight - rect.top) / (viewportHeight + rect.height);
        const offset = (progress - 0.5) * 48;
        section.style.setProperty("--parallax-offset", `${offset.toFixed(2)}px`);
    });
}

window.addEventListener("scroll", updateParallax, { passive: true });
window.addEventListener("resize", updateParallax);
window.addEventListener("load", updateParallax);
updateParallax();
// Efeito de Reveal ao Scroll
const observers = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = "1";
            entry.target.style.transform = "translateY(0)";
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.bento-item').forEach(item => {
    item.style.opacity = "0";
    item.style.transform = "translateY(30px)";
    item.style.transition = "all 0.6s ease-out";
    observers.observe(item);
});
// Garante que o modal seja capturado corretamente
const modalTeam = document.getElementById("team-modal");

function openTeamModal(element) {
    if (!modalTeam) return;

    // 1. Pegar os dados da div clicada
    const name = element.getAttribute("data-name");
    const role = element.getAttribute("data-role");
    const bio = element.getAttribute("data-bio");
    const imgSrc = element.querySelector("img").src;

    // 2. Injetar no Modal
    document.getElementById("modal-name").innerText = name;
    document.getElementById("modal-role").innerText = role;
    document.getElementById("modal-bio").innerText = bio;
    document.getElementById("modal-img").src = imgSrc;

    // 3. Exibir
    modalTeam.style.display = "flex";
}

// Fechar o modal
const btnClose = document.querySelector(".close-modal");
if(btnClose) {
    btnClose.onclick = () => {
        modalTeam.style.display = "none";
    };
}

// Fechar ao clicar fora
window.addEventListener("click", (event) => {
    if (modalTeam && event.target == modalTeam) {
        modalTeam.style.display = "none";
    }
});

/* =======================================================
   CHATBOT
======================================================= */

const chatbotForm = document.getElementById("chatbot-form");
const chatbotInput = document.getElementById("chatbot-input");
const chatbotMessages = document.getElementById("chatbot-messages");
const chatbotMode = document.getElementById("chatbot-mode");
const chatbotEnd = document.getElementById("chatbot-end");
const visitorName = document.getElementById("visitor-name");
const visitorPhone = document.getElementById("visitor-phone");
const visitorEmail = document.getElementById("visitor-email");

let conversationId = localStorage.getItem("sete_chat_conversation_id") || "";

function addChatMessage(text, sender = "bot") {
    if (!chatbotMessages) return;

    const message = document.createElement("div");
    message.className = `chat-message ${sender}`;
    message.textContent = text;
    chatbotMessages.appendChild(message);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

async function sendChatMessage(messageText) {
    const payload = {
        conversation_id: conversationId,
        message: messageText,
        mode: chatbotMode ? chatbotMode.value : "manual",
        visitor_name: visitorName ? visitorName.value : "",
        visitor_phone: visitorPhone ? visitorPhone.value : "",
        visitor_email: visitorEmail ? visitorEmail.value : ""
    };

    const response = await fetch("backend/chatbot_response.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(payload)
    });

    if (!response.ok) {
        throw new Error("Erro ao comunicar com o chatbot.");
    }

    return response.json();
}

if (chatbotForm) {
    chatbotForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const messageText = chatbotInput.value.trim();
        if (!messageText) return;

        addChatMessage(messageText, "user");
        chatbotInput.value = "";
        chatbotInput.disabled = true;

        try {
            const data = await sendChatMessage(messageText);

            if (data.conversation_id) {
                conversationId = data.conversation_id;
                localStorage.setItem("sete_chat_conversation_id", conversationId);
            }

            addChatMessage(data.reply || "Não consegui responder agora. Tente novamente em instantes.", data.sender || "bot");
        } catch (error) {
            addChatMessage("Não foi possível conectar ao atendimento. Verifique se o PHP e o MySQL estão configurados.", "bot");
        } finally {
            chatbotInput.disabled = false;
            chatbotInput.focus();
        }
    });
}

if (chatbotEnd) {
    chatbotEnd.addEventListener("click", async () => {
        if (!conversationId) {
            addChatMessage("A conversa ainda não foi iniciada.", "bot");
            return;
        }

        try {
            await fetch("backend/chat_end.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ conversation_id: conversationId })
            });
        } finally {
            localStorage.removeItem("sete_chat_conversation_id");
            conversationId = "";
            addChatMessage("Conversa encerrada. Quando quiser, é só enviar uma nova mensagem.", "bot");
        }
    });
}
