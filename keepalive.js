// Duración en milisegundos para comprobar actividad
const PING_INTERVAL = 30000; // 30 segundos

// Marca que esta pestaña está activa
localStorage.setItem("lastActive", Date.now());

// Aviso cuando el usuario cambia entre pestañas
window.addEventListener("focus", () => {
    localStorage.setItem("lastActive", Date.now());
});

// Realiza un ping periódico al servidor:
setInterval(() => {
    const lastActive = parseInt(localStorage.getItem("lastActive"));

    // Si ha pasado más de 40 segundos sin actividad,
    // se considera que no hay pestañas activas
    if (Date.now() - lastActive > 40000) {
        // No hay pestañas activas
        fetch("logout.php")
            .then(() => {
                window.location.href = "login.php";
            });
        return;
    }

    // Si esta pestaña está activa, enviamos ping a PHP para mantener sesión
    fetch("session_ping.php", { cache: "no-store" });

}, PING_INTERVAL);
