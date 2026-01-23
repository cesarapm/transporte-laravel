<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Consulta de Pedido - Rastreo de Guía</title>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
        }
        .note {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .error {
            font-size: 0.9rem;
            color: red;
        }
        #historial {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-md navbar-dark" style="background-color: #320500;">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="https://www.transportes-mexico.com/rastreo/media/lgodskgdso.png" width="150" height="88" alt="Logo Transportes México" />
        </a>
        <button
            class="navbar-toggler"
            type="button"
            data-toggle="collapse"
            data-target="#navbarNavAltMarkup"
            aria-controls="navbarNavAltMarkup"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav ml-auto">
                <a class="nav-item nav-link active mx-4 pl-2 nav-act h5" href="https://www.transportes-mexico.com/index.html"><strong>Inicio</strong></a>
                <a class="nav-item nav-link mx-4 pl-2 nav-Nact h5" href="https://www.transportes-mexico.com/Servicios.html"><strong>Servicios</strong></a>
                <a class="nav-item nav-link mx-4 pl-2 nav-Nact h5" href="https://www.transportes-mexico.com/rastreo/"><strong>Rastreo</strong></a>
                <a class="nav-item nav-link mx-4 pl-2 nav-Nact h5" href="https://www.transportes-mexico.com/Contacto.html"><strong>Contacto</strong></a>
            </div>
        </div>
    </nav>

    <div class="container my-4 d-flex justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="col-md-6 bg-white p-4 rounded shadow">
            <h1 class="mb-4">Rastreo de Guía</h1>

            <form id="consultaForm" class="mb-4" autocomplete="off">
                <div class="mb-3">
                    <label for="codigo" class="form-label">Código de Pedido:</label>
                    <input
                        type="text"
                        id="codigo"
                        name="codigo"
                        class="form-control"
                        required
                        value="{{ $numero ?? '' }}"
                        placeholder="Ejemplo: 1234567890-123-1"
                    />
                    <p class="note">Se formateará automáticamente como: 1234567890-123-1</p>
                    <p class="error" id="errorMsg" style="display: none;">Debe ingresar exactamente 16 dígitos</p>
                </div>
                <button type="submit" class="btn btn-primary">Consultar</button>
                <button type="button" id="btnBorrar" class="btn btn-secondary">Borrar</button>
            </form>

            <h2 class="h4">Última Actualización:</h2>
            <p id="ultimaActualizacion"></p>

            <h2 class="h4">Historial de Seguimiento:</h2>
            <ul id="historial" class="list-group"></ul>
        </div>
    </div>



    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <script>
        const codigoInput = document.getElementById('codigo');
        const errorMsg = document.getElementById('errorMsg');
        const ultimaActualizacion = document.getElementById('ultimaActualizacion');
        const historial = document.getElementById('historial');

        codigoInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, ''); // Quitar no numéricos

            if (value.length > 10) value = value.substring(0, 10) + '-' + value.substring(10);
            if (value.length > 14) value = value.substring(0, 14) + '-' + value.substring(14, 16);

            e.target.value = value;

            if (value.length !== 16) {
                errorMsg.style.display = 'block';
            } else {
                errorMsg.style.display = 'none';
            }
        });

        document.getElementById('consultaForm').addEventListener('submit', function (event) {
            event.preventDefault();

            if (codigoInput.value.length !== 16) {
                errorMsg.style.display = 'block';
                return;
            }

            errorMsg.style.display = 'none';
            historial.innerHTML = '';
            ultimaActualizacion.innerText = 'Cargando...';

            axios
                .post('https://apirest.transportes-mexico.com/api/proxy-tracking', {
                    codigo: codigoInput.value,
                })
                .then((response) => {
                    ultimaActualizacion.innerText = '';
                    historial.innerHTML = '';

                    let eventosHistorial = [];

                    if (response.data.historial && response.data.historial.length > 0) {
                        response.data.historial.forEach((item) => {
                            let fecha = new Date(item.created_at);
                            let contenido = item.campo_modificado;
                            if (contenido.includes('TrackingMore')) {
                                contenido = contenido.replace(/^.*?TrackingMore\s*-\s*/, '').split(',')[0];
                            }
                            eventosHistorial.push({
                                fecha: fecha,
                                texto: `${fecha.toLocaleString('es-ES')} - ${contenido}`,
                            });
                        });
                    }

                    if (response.data.tracking && response.data.tracking.data.length > 0) {
                        let data = response.data.tracking.data[0];
                        let rawEvent = data.latest_event || 'Sin información reciente';
                        let cleanedEvent = rawEvent.replace(/^.*?-\s*TrackingMore\s*-\s*/, '').split(',')[0];
                        ultimaActualizacion.innerText = cleanedEvent;

                        if (data.origin_info && data.origin_info.trackinfo) {
                            data.origin_info.trackinfo.forEach((checkpoint) => {
                                let fecha = new Date(checkpoint.checkpoint_date);
                                eventosHistorial.push({
                                    fecha: fecha,
                                    texto: `${fecha.toLocaleString('es-ES')} - ${checkpoint.tracking_detail}`,
                                });
                            });
                        }
                    }

                    // Ordenar por fecha ascendente
                    eventosHistorial.sort((a, b) => a.fecha - b.fecha);

                    // Eliminar duplicados conservando la más antigua
                    let seen = new Map();
                    for (let evento of eventosHistorial) {
                        let key = evento.texto.split(' - ')[1];
                        if (!seen.has(key)) {
                            seen.set(key, evento);
                        }
                    }

                    // Ordenar filtrados por fecha descendente (más recientes primero)
                    let eventosFiltrados = Array.from(seen.values()).sort((a, b) => b.fecha - a.fecha);

                    if (eventosFiltrados.length > 0) {
                        eventosFiltrados.forEach((evento) => {
                            let li = document.createElement('li');
                            li.classList.add('list-group-item');
                            li.textContent = evento.texto;
                            historial.appendChild(li);
                        });
                    } else {
                        historial.innerHTML = '<li class="list-group-item">En tránsito en USA</li>';
                    }
                })
                .catch((error) => {
                    historial.innerHTML = '<li class="list-group-item text-danger">No existe la guía que corresponde al número ingresado.</li>';
                    ultimaActualizacion.innerText = '';
                });
        });

        document.getElementById('btnBorrar').addEventListener('click', function () {
            codigoInput.value = '';
            errorMsg.style.display = 'none';
            ultimaActualizacion.innerText = '';
            historial.innerHTML = '';
        });

        // Si viene un número desde la URL, disparar búsqueda automática
        window.addEventListener('load', function () {
            @if(!empty($numero))
                codigoInput.dispatchEvent(new Event('input'));
                document.getElementById('consultaForm').dispatchEvent(new Event('submit'));
            @endif
        });
    </script>
</body>
</html>
