#!/usr/local/bin/php
<?php

/* --- PARAMETERS --- */
define("POSTS_BASE_PATH", '/frontend/src/_posts/');
define("COCKPIT_PATH", '/var/www/html/bootstrap.php');
/* ------------------ */

date_default_timezone_set('Europe/Vienna');

function stripspecialchars($string)
{
    $search = array("Ä", "Ö", "Ü", "ä", "ö", "ü", "ß", "´");
    $replace = array("Ae", "Oe", "Ue", "ae", "oe", "ue", "ss", "");
    return str_replace($search, $replace, $string);
}

function addWatermark($image) {

}

function copyImages($images) {
    foreach ($images as $image) {
        addWatermark($image);
    }
}

function createPost($title, $slug, $date, $category, $text, $images) {
    copyImages($images);
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
    if(!file_exists(POSTS_BASE_PATH)) {
        mkdir(POSTS_BASE_PATH, 0777, true);
    }
    file_put_contents(
        POSTS_BASE_PATH.$filename,
        "---\r\n".implode("\r\n", array_map(
            function ($v, $k) { return sprintf("%s: %s", $k, $v); },
            $frontmatter,
            array_keys($frontmatter)
        ))."\r\n---\r\n".$content
    );
}

// require cockpit
require_once(COCKPIT_PATH);

// load posts
$posts = cockpit('collections')->collection('Posts')->find()->toArray();
// @next: $posts = cockpit('collections:entries', 'Posts')->find()->toArray();

// create posts
foreach ($posts as $post){
    createPost($post['title'], $post['title_slug'], $post['created'], $post['category'], $post['text'], $post['images']);
}
?>
