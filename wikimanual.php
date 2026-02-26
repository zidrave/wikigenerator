<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

function httpGet($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "WikiMD/2.0");
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return false;
    }

    curl_close($ch);
    return $response;
}

function searchWikipedia($query) {
    $url = "https://es.wikipedia.org/w/api.php?action=query&list=search&format=json&utf8=1&srsearch=" . urlencode($query);
    $json = httpGet($url);

    if (!$json) return null;

    return json_decode($json, true);
}

function getArticleHTML($title) {
    $url = "https://es.wikipedia.org/w/api.php?action=parse&format=json&page=" . urlencode($title) . "&prop=text&utf8=1";
    $json = httpGet($url);

    if (!$json) return false;

    $data = json_decode($json, true);

    if (!isset($data['parse']['text']['*'])) {
        return false;
    }

    return $data['parse']['text']['*'];
}

function htmlToMarkdown($html) {

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);

    // Obtener contenido principal real
    $nodes = $xpath->query('//div[contains(@class,"mw-parser-output")]');
    if ($nodes->length == 0) {
        return "No se pudo procesar el contenido.";
    }

    $contentNode = $nodes->item(0);

    // ❌ Eliminar infobox
    foreach ($xpath->query('.//table[contains(@class,"infobox")]', $contentNode) as $node) {
        $node->parentNode->removeChild($node);
    }

    // ❌ Eliminar todas las tablas restantes
    foreach ($xpath->query('.//table', $contentNode) as $node) {
        $node->parentNode->removeChild($node);
    }

    // ❌ Eliminar bloques editar
    foreach ($xpath->query('.//span[contains(@class,"mw-editsection")]', $contentNode) as $node) {
        $node->parentNode->removeChild($node);
    }

    // ❌ Eliminar referencias
    foreach ($xpath->query('.//sup', $contentNode) as $node) {
        $node->parentNode->removeChild($node);
    }

    // ❌ Eliminar scripts y estilos
    foreach ($xpath->query('.//script|.//style', $contentNode) as $node) {
        $node->parentNode->removeChild($node);
    }

    // Reconstruir HTML limpio
    $html = '';
    foreach ($contentNode->childNodes as $child) {
        $html .= $dom->saveHTML($child);
    }

    // =========================
    // CONVERSIONES A MARKDOWN
    // =========================

    // Encabezados
    $html = preg_replace('/<h1[^>]*>(.*?)<\/h1>/i', "# $1\n\n", $html);
    $html = preg_replace('/<h2[^>]*>(.*?)<\/h2>/i', "## $1\n\n", $html);
    $html = preg_replace('/<h3[^>]*>(.*?)<\/h3>/i', "### $1\n\n", $html);

    // Negritas y cursivas
    $html = preg_replace('/<(strong|b)>(.*?)<\/(strong|b)>/i', "**$2**", $html);
    $html = preg_replace('/<(em|i)>(.*?)<\/(em|i)>/i', "*$2*", $html);

    // Enlaces internos de Wikipedia
    $html = preg_replace_callback(
        '/<a[^>]*href="\/wiki\/([^"#]+)[^"]*"[^>]*>(.*?)<\/a>/is',
        function ($matches) {

            $titulo = urldecode($matches[1]);
            $titulo = explode('#', $titulo)[0];
            $titulo = str_replace('_', ' ', $titulo);

            // Ignorar páginas especiales (Archivo:, Categoría:, etc.)
            if (strpos($titulo, ':') !== false) {
                return strip_tags($matches[2]);
            }

            $texto = trim(strip_tags($matches[2]));

            if ($texto === '') {
                return '';
            }
#           return '[' . $texto . '](wikimd.php?title=' . $titulo . ')';
$urlTitulo = urlencode($titulo);
return '[' . $texto . '](wikimd.php?title=' . $urlTitulo . ')';

        },
        $html
    );

    // Listas
    $html = preg_replace('/<li>(.*?)<\/li>/i', "- $1\n", $html);

    // Párrafos
    $html = preg_replace('/<\/p>/i', "\n\n", $html);

    // Saltos
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);

    // Quitar etiquetas restantes
    $html = strip_tags($html);

    // Eliminar referencias tipo [1]
    $html = preg_replace('/\[\d+\]/', '', $html);

    // Limpiar saltos excesivos
    $html = preg_replace("/\n{3,}/", "\n\n", $html);

    $markdown = trim(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    // Cortar secciones finales no deseadas
    $markdown = preg_split('/## (Referencias|Bibliografía|Enlaces externos|Véase también)/i', $markdown)[0];

    return trim($markdown);
}

$results = null;
$articleMarkdown = null;
$title = null;
$message = null;
$error = null;

if (isset($_POST['search'])) {
    $results = searchWikipedia($_POST['query']);
}

if (isset($_GET['title'])) {
    $title = $_GET['title'];
    $html = getArticleHTML($title);

    if ($html === false) {
        $error = "No se pudo obtener el artículo desde Wikipedia.";
    } else {
        $articleMarkdown = htmlToMarkdown($html);
    }
}

if (isset($_POST['save_article'])) {

    $title = $_POST['article_title'];
    $content = $_POST['article_content'];

    $cleanTitle = preg_replace('/[\/:*?"<>|]/', '', $title);
    $cleanTitle = str_replace('_', ' ', $cleanTitle);

    $cleanTitle = mb_strtolower($cleanTitle, 'UTF-8');
    $cleanTitle = mb_convert_case($cleanTitle, MB_CASE_TITLE, "UTF-8");

    $filename = $cleanTitle . ".md";

    file_put_contents($filename, $content);

    $message = "Artículo guardado como $filename";
    $articleMarkdown = $content;
}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>WikiMD</title>
<style>
body { font-family: Arial; margin: 40px; }
textarea { width: 100%; height: 500px; }
button { padding: 8px 15px; }
a { display:block; margin:5px 0; }
.error { color:red; }
.success { color:green; }
</style>
</head>
<body>

<h1>Wiki → Markdown</h1>

<form method="POST">
    <input type="text" name="query" placeholder="Buscar tema..." required>
    <button type="submit" name="search">Buscar</button>
</form>

<hr>

<?php if ($results && isset($results['query']['search'])): ?>
    <h2>Resultados:</h2>
    <?php foreach ($results['query']['search'] as $item): ?>
        <a href="?title=<?php echo urlencode($item['title']); ?>">
            <?php echo htmlspecialchars($item['title']); ?>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($error): ?>
    <p class="error"><?php echo $error; ?></p>
<?php endif; ?>

<?php if ($articleMarkdown): ?>
    <hr>
    <h2>Artículo: <?php echo htmlspecialchars($title); ?></h2>

    <?php if ($message): ?>
        <p class="success"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="article_title" value="<?php echo htmlspecialchars($title); ?>">
        <textarea name="article_content"><?php echo htmlspecialchars($articleMarkdown); ?></textarea>
        <br><br>
        <button type="submit" name="save_article">Guardar artículo</button>
    </form>
<?php endif; ?>

</body>
</html>
