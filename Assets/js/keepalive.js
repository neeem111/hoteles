// Duración en milisegundos para comprobar actividad
const PING_INTERVAL = 30000; // 30 segundos

const BASE = "/hoteles";

// Si el usuario no interactúa en este tiempo, se cierra sesión
const INACTIVITY_LIMIT = 40_000; // 40s (para demo). En real: 10-15 min.

function markActive() {
  localStorage.setItem("lastActive", String(Date.now()));
}

markActive();

// Marca actividad en más casos (no solo focus)
window.addEventListener("focus", markActive);
window.addEventListener("mousemove", markActive, { passive: true });
window.addEventListener("keydown", markActive);
window.addEventListener("click", markActive);
window.addEventListener("scroll", markActive, { passive: true });
document.addEventListener("visibilitychange", () => {
  if (!document.hidden) markActive();
});

// Realiza un ping periódico al servidor
setInterval(async () => {
  const lastActive = parseInt(localStorage.getItem("lastActive") || "0", 10);

  // Si ha pasado el límite sin actividad → logout y redirigir
  if (Date.now() - lastActive > INACTIVITY_LIMIT) {
    try {
      // Llama al logout (mejor POST, pero así funciona rápido)
      await fetch(`${BASE}/auth/logout.php`, { cache: "no-store", credentials: "same-origin" });
    } catch (e) {
      // aunque falle el fetch, igual redirigimos
    }

    // Redirige a una vista "sesión expirada" o al login con flag
    window.location.href = `${BASE}/auth/logout.php?expired=1`;
    return;
  }

  // Mantener sesión viva si hay actividad
  try {
    await fetch(`${BASE}/Config/session_ping.php`, { cache: "no-store", credentials: "same-origin" });
  } catch (e) {
    // si el ping falla, no hagas nada
  }
}, PING_INTERVAL);
