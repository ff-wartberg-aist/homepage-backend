#!/usr/local/bin/php
<?php
/* --- PARAMETERS --- */
define("FRONTEND_BASE_PATH", '/frontend/src');
define("POSTS_PATH", FRONTEND_BASE_PATH.'/_posts');
define("IMAGES_PATH", FRONTEND_BASE_PATH.'/assets');
define("COCKPIT_BASE_PATH", '/var/www/html');
define("COCKPIT_BOOTSTRAP_PATH", COCKPIT_BASE_PATH.'/bootstrap.php');
/* ------------------ */

date_default_timezone_set('Europe/Vienna');

function assureDirectoryExists($directory) {
    if(!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
}

// make sure that folders exist
assureDirectoryExists(POSTS_PATH);
assureDirectoryExists(IMAGES_PATH);

function stripspecialchars($string)
{
    $search = array("Ä", "Ö", "Ü", "ä", "ö", "ü", "ß", "´");
    $replace = array("Ae", "Oe", "Ue", "ae", "oe", "ue", "ss", "");
    return str_replace($search, $replace, $string);
}

function addWatermark($image) {
    
}

function processImages($images) {
    $names = [];
    foreach ($images as $image) {
        $imagepath = COCKPIT_BASE_PATH.substr($image['path'], 5);
        $hash = hash_file('sha1', $imagepath);
        $newimagename = $hash.'.'.pathinfo($imagepath, PATHINFO_EXTENSION);
        $newimagepath = IMAGES_PATH.'/'.$newimagename;
        copy($imagepath, $newimagepath);
        $names[] = $newimagename;
        addWatermark($newimagepath, 'watermark.png');
    }
    return $names;
}

function createPost($title, $slug, $date, $category, $text, $images) {
    $images = processImages($images);
    writePost($title, $slug, $date, $category, $text, $images, 'desktop');
    writePost($title, $slug, $date, $category, $text, $images, 'mobile', '.m', ['m']);
}

function writePost($title, $slug, $date, $category, $text, $images, $layoutprefix, $filenamepostfix = "", $additionalCategories = []) {
    writeFile(
        date("Y-m-d", $date).'-'.$slug.$filenamepostfix.'.md',
        [
            "layout" => $layoutprefix.'-post',
            "title" => $title,
            "slug" => $slug,
            "date" => date("Y-m-d H:i:s +0000", $date),
            "categories" => join(' ', array_merge($additionalCategories, [strtolower(stripspecialchars($category))])),
            "images" => join(' ', $images)
        ],
        $text
    );
}

function writeFile($filename, $frontmatter, $content) {
    file_put_contents(
        POSTS_PATH.'/'.$filename,
        "---\r\n".implode("\r\n", array_map(
            function ($v, $k) { return sprintf("%s: %s", $k, $v); },
            $frontmatter,
            array_keys($frontmatter)
        ))."\r\n---\r\n".$content
    );
}

function executeGitCommand($command) {
    exec('cd '.FRONTEND_BASE_PATH.' && '.$command);
}

// fetch git changes
executeGitCommand('git fetch --all');

// require cockpit
require_once(COCKPIT_BOOTSTRAP_PATH);

// load posts
$posts = cockpit('collections')->collection('Posts')->find()->toArray();
// @next: $posts = cockpit('collections:entries', 'Posts')->find()->toArray();

// create posts
foreach ($posts as $post){
    createPost($post['title'], $post['title_slug'], $post['created'], $post['category'], $post['text'], $post['images']);
}
?>
