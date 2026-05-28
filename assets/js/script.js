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
const hasFinePointer = window.matchMedia("(pointer: fine)").matches;

let mouseX = 0;
let mouseY = 0;

let posX = 0;
let posY = 0;
let cursorHideTimeout;

if (cursor && !hasFinePointer) {
  cursor.style.display = "none";
}

function showCursorTemporarily() {
  if (!cursor || !hasFinePointer) return;

  cursor.classList.remove("hidden");
  clearTimeout(cursorHideTimeout);
  cursorHideTimeout = setTimeout(() => {
    cursor.classList.add("hidden");
  }, 1800);
}

if (cursor && hasFinePointer) {
  cursor.classList.add("hidden");

  // Captura posição real do mouse
  document.addEventListener("mousemove", (e) => {
    mouseX = e.clientX;
    mouseY = e.clientY;
    showCursorTemporarily();
  });

  // Efeito ao passar no título
  titles.forEach(title => {
    title.addEventListener("mouseenter", () => {
      cursor.classList.add("active");
      showCursorTemporarily();
    });

    title.addEventListener("mouseleave", () => {
      cursor.classList.remove("active");
      showCursorTemporarily();
    });
  });

  // Quando o mouse sai da janela
  document.addEventListener("mouseleave", () => {
    cursor.classList.add("hidden");
  });

  // Quando o mouse volta
  document.addEventListener("mouseenter", showCursorTemporarily);
}

// Animação suave (50% mais rápido)
function animate() {
  if (!cursor || !hasFinePointer) return;

  posX += (mouseX - posX) * 0.12;
  posY += (mouseY - posY) * 0.12;

  cursor.style.left = posX + "px";
  cursor.style.top = posY + "px";

  requestAnimationFrame(animate);
}

animate();

function duplicateLoopContent(element) {
    if (!element || element.dataset.loopReady === "true") return;

    const itemCount = element.children.length * 2;
    element.innerHTML += element.innerHTML;
    element.style.setProperty("--loop-item-count", itemCount);
    element.dataset.loopReady = "true";
}

function createCarouselTrack() {
    const carrossel = document.querySelector(".carrossel");
    if (!carrossel || carrossel.dataset.loopReady === "true") return;

    const track = document.createElement("div");
    track.className = "carrossel-track";

    Array.from(carrossel.children).forEach((item) => {
        track.appendChild(item);
    });

    track.innerHTML += track.innerHTML;
    carrossel.appendChild(track);
    carrossel.dataset.loopReady = "true";
}

duplicateLoopContent(document.querySelector(".slider"));
document.querySelectorAll(".faixa-track").forEach(duplicateLoopContent);
createCarouselTrack();

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
