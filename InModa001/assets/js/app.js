// === InModa - Gestión de Turnos con MySQL === //

document.addEventListener("DOMContentLoaded", () => {
    const calendarContainer = document.getElementById("calendar");
    const editBtn = document.getElementById("editCalendar");

    // Cargar turnos desde la base de datos
    async function cargarTurnos() {
        const res = await fetch("turnos_api.php?accion=listar");
        const data = await res.json();

        if (data.length === 0) {
            calendarContainer.innerHTML = "<p>No hay turnos registrados.</p>";
            return;
        }

        const lista = document.createElement("ul");
        lista.classList.add("turnos-list");

        data.forEach(turno => {
            const item = document.createElement("li");
            item.classList.add("turno-item");
            item.innerHTML = `
                <strong>${turno.fecha}</strong> - ${turno.empleado} (${turno.horario})
                <div class="turno-actions">
                    <button class="edit-btn" data-id="${turno.id}">✏️</button>
                    <button class="delete-btn" data-id="${turno.id}">🗑️</button>
                </div>
            `;
            lista.appendChild(item);
        });

        calendarContainer.innerHTML = "";
        calendarContainer.appendChild(lista);

        document.querySelectorAll(".edit-btn").forEach(btn => 
            btn.addEventListener("click", () => editarTurno(btn.dataset.id))
        );

        document.querySelectorAll(".delete-btn").forEach(btn => 
            btn.addEventListener("click", () => eliminarTurno(btn.dataset.id))
        );
    }

    // Agregar turno nuevo
    async function agregarTurno() {
        const fecha = prompt("📅 Fecha del turno (Ej: 2025-10-22):");
        const empleado = prompt("👤 Nombre del empleado:");
        const horario = prompt("🕒 Horario (Ej: 9am - 5pm):");

        if (!fecha || !empleado || !horario) return alert("Todos los campos son obligatorios.");

        const formData = new FormData();
        formData.append("fecha", fecha);
        formData.append("empleado", empleado);
        formData.append("horario", horario);

        const res = await fetch("turnos_api.php?accion=agregar", {
            method: "POST",
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            alert("✅ Turno agregado correctamente.");
            cargarTurnos();
        } else {
            alert("❌ Error al agregar el turno.");
        }
    }

    // Editar turno
    async function editarTurno(id) {
        const fecha = prompt("📅 Nueva fecha:");
        const empleado = prompt("👤 Nuevo empleado:");
        const horario = prompt("🕒 Nuevo horario:");

        const formData = new FormData();
        formData.append("id", id);
        formData.append("fecha", fecha);
        formData.append("empleado", empleado);
        formData.append("horario", horario);

        const res = await fetch("turnos_api.php?accion=editar", {
            method: "POST",
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            alert("✏️ Turno actualizado.");
            cargarTurnos();
        }
    }

    // Eliminar turno
    async function eliminarTurno(id) {
        const confirmar = confirm("¿Seguro que deseas eliminar este turno?");
        if (!confirmar) return;

        const formData = new FormData();
        formData.append("id", id);

        const res = await fetch("turnos_api.php?accion=eliminar", {
            method: "POST",
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            alert("🗑️ Turno eliminado.");
            cargarTurnos();
        }
    }

    // Evento de agregar turno
    if (editBtn) editBtn.addEventListener("click", agregarTurno);

    // Cargar al inicio
    cargarTurnos();
});

