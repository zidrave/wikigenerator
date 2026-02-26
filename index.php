<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Documentación MD</title>
    
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-bash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-php.min.js"></script>

    <style>
        /* Variables de diseño */
        :root {
            --primary-color: #007bff;
            --bg-menu: #f8f9fa;
            --text-main: #333;
            --border-color: #eaecef;
            --font-stack: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
        }

        /* Estructura Base */
        * { box-sizing: border-box; }
        body { 
            font-family: var(--font-stack); 
            display: flex; 
            margin: 0; 
            height: 100vh; 
            color: var(--text-main);
            background-color: #fff;
        }

        /* Panel Lateral (Menú) */
        #menu { 
            width: 280px; 
            background: var(--bg-menu); 
            border-right: 1px solid var(--border-color); 
            padding: 25px 15px; 
            overflow-y: auto;
            flex-shrink: 0;
        }
        #menu h3 { 
            font-size: 1.2rem; 
            margin-bottom: 20px; 
            padding-left: 10px;
            color: #555;
        }

        .menu-item { 
            display: block; 
            padding: 10px 15px; 
            color: #444; 
            text-decoration: none; 
            cursor: pointer; 
            border-radius: 6px;
            margin-bottom: 5px;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }
        .menu-item:hover { background: #e9ecef; }
        .menu-item.active { 
            background: var(--primary-color); 
            color: white; 
            font-weight: 500;
        }

        /* Área de Contenido */
        #wrapper-contenido {
            flex-grow: 1;
            overflow-y: auto;
            background: #fff;
        }
        #contenido { 
            max-width: 900px; 
            margin: 0; 
            padding: 60px 50px; 
            line-height: 1.7; 
        }

        /* Estilos del Markdown renderizado */
        #visor-md h1 { border-bottom: 1px solid var(--border-color); padding-bottom: 0.3em; margin-top: 24px; }
        #visor-md h2 { border-bottom: 1px solid var(--border-color); padding-bottom: 0.3em; margin-top: 20px; }
        #visor-md blockquote { 
            border-left: 4px solid var(--primary-color); 
            padding: 10px 20px; 
            margin: 20px 0;
            background: #f0f7ff;
            color: #555;
            font-style: italic;
        }
        #visor-md img { max-width: 100%; border-radius: 8px; }
        
        /* Ajustes de código */
        code[class*="language-"], pre[class*="language-"] { font-size: 0.9rem !important; }
    </style>
</head>
<body>

<nav id="menu">
    <h3>Documentación</h3>
    <?php
    $archivos = glob("*.md");
    if (count($archivos) > 0) {
        foreach ($archivos as $archivo) {
            // No listar readme.md en el menú
            if (strtolower($archivo) === 'readme.md') continue;
            
            $nombreLimpio = htmlspecialchars(str_replace(".md", "", $archivo));
            echo "<a id='menu-$archivo' class='menu-item' onclick='cargarContenido(\"$archivo\", this)'>$nombreLimpio</a>";
        }
    } else {
        echo "<p>No hay archivos .md</p>";
    }
    ?>
</nav>

<main id="wrapper-contenido">
    <div id="contenido">
        <article id="visor-md">Selecciona un documento para comenzar.</article>
    </div>
</main>

<script>
function cargarContenido(archivo, elemento, bypassHistory = false) {
    let archivoReal = archivo;
    if (!archivoReal.toLowerCase().endsWith('.md')) {
        archivoReal += '.md';
    }

    const nombreBase = archivoReal.split(/[\\\/]/).pop();
    
    if (!nombreBase.toLowerCase().endsWith('.md')) {
        document.getElementById('visor-md').innerHTML = "Acceso denegado.";
        return;
    }

    document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
    let menuLink = elemento || document.getElementById('menu-' + nombreBase);
    if (menuLink) menuLink.classList.add('active');

    fetch(nombreBase)
        .then(response => {
            if (!response.ok) throw new Error('No encontrado');
            return response.text();
        })
        .then(text => {
            const visor = document.getElementById('visor-md');
            visor.innerHTML = marked.parse(text);
            Prism.highlightAllUnder(visor);

            if (!bypassHistory) {
                const nombreSinExtension = nombreBase.replace('.md', '');
                const urlEstetica = encodeURIComponent(nombreSinExtension).replace(/%20/g, '+');
                history.pushState({ archivo: nombreBase }, "", "?file=" + urlEstetica);
            }
            document.title = nombreBase.replace('.md', '').toUpperCase() + " | Visor";
            document.getElementById('wrapper-contenido').scrollTop = 0;
        })
        .catch(err => {
            document.getElementById('visor-md').innerHTML = "Error al cargar el documento.";
        });
}

window.onpopstate = function(event) {
    if (event.state && event.state.archivo) {
        cargarContenido(event.state.archivo, null, true);
    } else {
        location.reload();
    }
};

window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    const fileParam = urlParams.get('file');
    if (fileParam) {
        cargarContenido(fileParam, null, true);
    } else {
        // Si no hay parámetro en la URL, cargar readme.md por defecto
        cargarContenido('readme.md', null, true);
    }
};
</script>

</body>
</html>
