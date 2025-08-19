document.addEventListener("DOMContentLoaded", () => {
  const links = document.querySelectorAll("nav a");
  const sections = document.querySelectorAll(".section");

  links.forEach(link => {
    link.addEventListener("click", e => {
      e.preventDefault();

      // Sezione da mostrare
      const targetId = link.getAttribute("href").substring(1);

      // Nascondo tutte
      sections.forEach(sec => sec.classList.remove("active"));

      // Mostro quella giusta
      document.getElementById(targetId).classList.add("active");

      // Aggiorno il menu
      links.forEach(l => l.classList.remove("active"));
      link.classList.add("active");
    });
  });
});
