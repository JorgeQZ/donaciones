const botones = document.querySelectorAll(".boton");
const subsecciones = document.querySelectorAll(".subseccion");

// Activar primero por defecto
if (botones.length > 0) botones[0].classList.add("activo");
if (subsecciones.length > 0) subsecciones[0].classList.add("activo");

// Click behavior
botones.forEach((boton, index) => {
  boton.addEventListener("click", () => {
    botones.forEach((b) => b.classList.remove("activo"));
    subsecciones.forEach((s) => s.classList.remove("activo"));

    boton.classList.add("activo");
    if (subsecciones[index]) {
      subsecciones[index].classList.add("activo");
    }
  });
});
