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
const titles = document.querySelectorAll("h1");

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

const carrossel = document.querySelector(".carrossel")

let scroll = 0

function animar(){
    scroll += 0.5
    carrossel.scrollLeft = scroll

    if(scroll >= carrossel.scrollWidth - carrossel.clientWidth){
        scroll = 0
    }

    requestAnimationFrame(animar)
}

animar()

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
