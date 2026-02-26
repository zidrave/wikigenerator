# 📚 WikiMD & DocViewer 🚀

Una solución integral y ligera desarrollada en **PHP** para extraer, convertir y visualizar artículos de Wikipedia en formato **Markdown**. Este proyecto permite transformar la vasta información de la web en una base de conocimientos local, limpia y estéticamente agradable.

---

## 🛠️ Componentes del Sistema

### 1. WikiMD (`wikimd.php`)
Es el motor de adquisición y procesamiento de datos. Se encarga de la comunicación con la API de Wikipedia y la transformación del contenido.

* **Búsqueda Integrada:** Interfaz directa para consultar temas mediante la API oficial de Wikipedia.
* **Conversión Inteligente:** Transforma el HTML complejo en Markdown puro, eliminando elementos ruidosos como tablas de datos, scripts, estilos CSS y secciones de referencias.
* **Auto-Guardado:** Al seleccionar un resultado, el script procesa el contenido y genera automáticamente un archivo `.md` persistente en el servidor.
* **Validación de Calidad:** Incluye una restricción de seguridad que impide guardar artículos con menos de 20 palabras, asegurando la relevancia del contenido.
* **Navegación Local:** Reestructura los hipervínculos internos para que la navegación entre temas se mantenga dentro de tu propia instancia.

  ### 1. WikiMD (`wikimanual.php`)
Con esto genera los primeros Articulos manualmente ya luego desde el index.php explorando los enlaces se generan nuevos articulos.

* **Búsqueda Integrada:** Interfaz directa para consultar temas mediante la API oficial de Wikipedia.
* **Conversión Inteligente:** Transforma el HTML complejo en Markdown puro, eliminando elementos ruidosos como tablas de datos, scripts, estilos CSS y secciones de referencias.
* **Navegación Local:** Reestructura los hipervínculos internos para que la navegación entre temas se mantenga dentro de tu propia instancia.


### 2. DocViewer (`index.php`)
Es una interfaz de usuario (UI) moderna y minimalista diseñada específicamente para la lectura técnica y documentación.

* **Renderizado en Tiempo Real:** Utiliza la librería [Marked.js](https://marked.js.org/) para convertir Markdown a HTML dinámicamente.
* **Resaltado de Sintaxis:** Integra [Prism.js](https://prismjs.com/) para ofrecer soporte de coloreado de código en bloques de PHP, Bash, JavaScript y más.
* **Experiencia SPA:** Sistema de carga de archivos mediante `fetch` (AJAX) que permite alternar entre documentos sin recargas de página.
* **Menú Dinámico:** Escanea automáticamente el directorio raíz, detecta archivos `.md` y los lista en el panel lateral de forma automática.
* **Gestión de Historial:** Implementa `pushState` para permitir URLs estéticas (ej: `?file=Nombre_Articulo`) y compatibilidad con los botones de atrás/adelante del navegador.

---

## 📋 Flujo de Trabajo

1.  **Captura:** Accedes a `wikimd.php` y buscas un tema (ej: "Python").
2.  **Procesamiento:** El script descarga, limpia y guarda el archivo `Python.md`.
3.  **Visualización:** El sistema te redirige automáticamente al visor principal.
4.  **Lectura:** En `index.php`, consumes la información con un diseño optimizado para la lectura técnica.

---

## 🔧 Requisitos e Instalación

* **Servidor:** Apache / Nginx con **PHP 7.4** o superior.
* **Extensiones:** PHP `cURL` habilitado.
* **Permisos:** El servidor debe tener permisos de escritura en la carpeta del proyecto para crear los archivos `.md`.

### Instalación rápida:
1. Clona este repositorio en tu servidor local o hosting.
2. Asegúrate de que los archivos `wikimd.php` e `index.php` estén en la misma carpeta.
3. Crea un archivo llamado `readme.md` para la pantalla de bienvenida inicial.
4. ¡Empieza a buscar y guardar documentación!

---

## 🎨 Tecnologías Utilizadas

- ![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)
- ![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black)
- ![Markdown](https://img.shields.io/badge/Markdown-000000?style=flat-square&logo=markdown&logoColor=white)
- ![Wikipedia API](https://img.shields.io/badge/Wikipedia_API-000000?style=flat-square&logo=wikipedia&logoColor=white)
