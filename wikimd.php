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
    return $json ? json_decode($json, true) : null;
}

function getArticleHTML($title) {
    $url = "https://es.wikipedia.org/w/api.php?action=parse&format=json&page=" . urlencode($title) . "&prop=text&utf8=1";
    $json = httpGet($url);
    if (!$json) return false;
    $data = json_decode($json, true);
    return $data['parse']['text']['*'] ?? false;
}

function htmlToMarkdown($html) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//div[contains(@class,"mw-parser-output")]');
    if ($nodes->length == 0) return "";
    $contentNode = $nodes->item(0);

    foreach ($xpath->query('.//table|.//span[contains(@class,"mw-editsection")]|.//sup|.//script|.//style', $contentNode) as $node) {
        $node->parentNode->removeChild($node);
    }

    $html = '';
    foreach ($contentNode->childNodes as $child) { $html .= $dom->saveHTML($child); }

    $html = preg_replace('/<h1[^>]*>(.*?)<\/h1>/i', "# $1\n\n", $html);
    $html = preg_replace('/<h2[^>]*>(.*?)<\/h2>/i', "## $1\n\n", $html);
    $html = preg_replace('/<h3[^>]*>(.*?)<\/h3>/i', "### $1\n\n", $html);
    $html = preg_replace('/<(strong|b)>(.*?)<\/(strong|b)>/i', "**$2**", $html);
    $html = preg_replace('/<(em|i)>(.*?)<\/(em|i)>/i', "*$2*", $html);
    $html = preg_replace_callback('/<a[^>]*href="\/wiki\/([^"#]+)[^"]*"[^>]*>(.*?)<\/a>/is', function ($matches) {
        $titulo = str_replace('_', ' ', explode('#', urldecode($matches[1]))[0]);
        if (strpos($titulo, ':') !== false) return strip_tags($matches[2]);
        $texto = trim(strip_tags($matches[2]));
        return $texto === '' ? '' : '[' . $texto . '](wikimd.php?title=' . urlencode($titulo) . ')'; //aqui estaba el index.php?tit
    }, $html);

    $html = preg_replace('/<li>(.*?)<\/li>/i', "- $1\n", $html);
    $html = preg_replace('/<\/p>/i', "\n\n", $html);
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
    $html = strip_tags($html);
    $html = preg_replace('/\[\d+\]/', '', $html);
    $html = preg_replace("/\n{3,}/", "\n\n", $html);
    $markdown = preg_split('/## (Referencias|Bibliografía|Enlaces externos|Véase también)/i', $html)[0];
    return trim(html_entity_decode($markdown, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

$results = null;
$articleMarkdown = null;
$title = null;
$message = null;
$error = null;

// 1. Procesar búsqueda
if (isset($_POST['search'])) {
    $results = searchWikipedia($_POST['query']);
}

// 2. Cargar artículo de Wikipedia y auto-guardar
if (isset($_GET['title'])) {
    $title = $_GET['title'];
    $html = getArticleHTML($title);
    if ($html === false) {
        $error = "No se pudo obtener el artículo.";
    } else {
        $articleMarkdown = htmlToMarkdown($html);
        $wordCount = count(preg_split('/\s+/u', trim($articleMarkdown), -1, PREG_SPLIT_NO_EMPTY));

        if ($wordCount < 20) {
            echo "<script>alert('Artículo muy pequeño para ser guardado.'); window.history.back();</script>";
            exit;
        } else {
            $cleanTitle = preg_replace('/[\/:*?"<>|]/', '', $title);
            $cleanTitle = mb_convert_case(str_replace('_', ' ', $cleanTitle), MB_CASE_TITLE, "UTF-8");
            file_put_contents($cleanTitle . ".md", $articleMarkdown);
            // REDIRECCIÓN TRAS GUARDADO EXITOSO
            header("Location: index.php?file=" . urlencode($cleanTitle)); //aqui estaba el index.php
            exit;
        }
    }
}

// 3. Cargar el artículo guardado para visualizarlo (Visualización limpia)
if (isset($_GET['file'])) {
    $title = $_GET['file'];
    $filename = $title . ".md";
    if (file_exists($filename)) {
        $articleMarkdown = file_get_contents($filename);
        $message = "Artículo '" . htmlspecialchars($title) . "' guardado y cargado con éxito.";
    } else {
        $error = "El archivo solicitado no existe.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>WikiMD</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f4f4f4; line-height: 1.6; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 900px; margin: auto; }
        button { padding: 10px 20px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px; }
        a { display:block; margin:8px 0; color: #007bff; text-decoration: none; }
        .error { color:red; font-weight: bold; }
        .success { color:green; font-weight: bold; margin-bottom: 20px; }
        .content-view { background: #fafafa; padding: 20px; border: 1px solid #eee; border-radius: 4px; white-space: pre-wrap; font-family: monospace; }
    </style>
</head>
<body>
<div class="container">
    <h1>Wiki → Markdown</h1>
    
    <?php if (!isset($_GET['file'])): ?>
    <form method="POST">
        <input type="text" name="query" placeholder="Buscar tema..." required style="padding: 8px; width: 250px;">
        <button type="submit" name="search">Buscar</button>
    </form>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <p class="success"><?php echo $message; ?> <a href="index.php" style="display:inline; margin-left:15px;">[Nueva búsqueda]</a></p>
    <?php endif; ?>

    <?php if ($results && isset($results['query']['search'])): ?>
        <hr>
        <h2>Resultados:</h2>
        <?php foreach ($results['query']['search'] as $item): ?>
            <a href="?title=<?php echo urlencode($item['title']); ?>">
                <?php echo htmlspecialchars($item['title']); ?>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error"><?php echo $error; ?></p>
        <a href="index.php">Volver</a>
    <?php endif; ?>

    <?php if ($articleMarkdown && isset($_GET['file'])): ?>
        <hr>
        <h2><?php echo htmlspecialchars($title); ?></h2>
        
<div class="content-view"><?php //echo htmlspecialchars($articleMarkdown); 
?></div>

    <?php endif; ?>
</div>
</body>
</html>
