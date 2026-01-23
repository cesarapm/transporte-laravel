<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Pedido</title>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body>
    <h1>Consulta de Pedido</h1>

    <form id="consultaForm">
        <label for="codigo">Código de Pedido:</label>
        <input type="text" id="codigo" name="codigo" required>
        <button type="submit">Consultar</button>
    </form>

    <h2>Estado del Pedido:</h2>
    <p id="estadoPedido"></p>

    <h2>Última Actualización:</h2>
    <p id="ultimaActualizacion"></p>

    <h2>Historial de Seguimiento:</h2>
    <ul id="historial"></ul>

    <script>
        document.getElementById('consultaForm').addEventListener('submit', function(event) {
    event.preventDefault();
    let codigo = document.getElementById('codigo').value;

    axios.post("{{ route('consulta.tracking') }}", { codigo: codigo })
        .then(response => {
            let estadoPedido = document.getElementById('estadoPedido');
            let ultimaActualizacion = document.getElementById('ultimaActualizacion');
            let historial = document.getElementById('historial');

            historial.innerHTML = "";
            ultimaActualizacion.innerText = "";

            if (response.data.message) {
                estadoPedido.innerText = response.data.message;
            }

            // Historial de la base de datos
            if (response.data.historial && response.data.historial.length > 0) {
                response.data.historial.forEach(item => {
                    let listItem = document.createElement('li');
                    // Convertir la fecha ISO 8601 a formato deseado
                    let fecha = new Date(item.created_at);  // Convierte la fecha ISO a un objeto Date
                    let fechaFormateada = fecha.toLocaleString('es-ES');  // Convierte la fecha a formato local (dd/mm/yyyy hh:mm:ss)
                    listItem.innerText = `${fechaFormateada} - ${item.campo_modificado}`;
                    historial.appendChild(listItem);
                });
            } else {
                historial.innerHTML = "<li>No hay historial registrado.</li>";
            }

            // Si viene de la API externa
            if (response.data.tracking) {
                let data = response.data.tracking.data ? response.data.tracking.data[0] : null;
                if (data) {
                    ultimaActualizacion.innerText = data.latest_event || "Sin información reciente";

                    if (data.origin_info && data.origin_info.trackinfo) {
                        data.origin_info.trackinfo.forEach(checkpoint => {
                            let item = document.createElement('li');
                            // Convertir la fecha de la API externa
                            let checkpointDate = new Date(checkpoint.checkpoint_date);
                            let checkpointFormattedDate = checkpointDate.toLocaleString('es-ES');
                            item.innerText = `${checkpointFormattedDate} - ${checkpoint.tracking_detail}`;
                            historial.appendChild(item);
                        });
                    }
                }
            }
        })
        .catch(error => {
            document.getElementById('estadoPedido').innerText = 'Error al consultar: ' + (error.response ? error.response.data.message : error.message);
            document.getElementById('historial').innerHTML = "";
        });
});
    </script>
</body>
</html>
