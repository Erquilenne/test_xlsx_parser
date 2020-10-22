<?php
require_once __DIR__.'/SimpleXLSX.php';



function getRows($file) {
    if ( $xlsx = SimpleXLSX::parse($file) ) {
        return $xlsx->rows();
    } else {
        echo SimpleXLSX::parseError();
    }
}
// sections - [id, pid, name, deep]
function makeSections($id = 0, $file = 'sections') {
    $file = $file;
    $xmlx = getRows($file);
    $sections = [];
    $quote = chr(39);
    $i = 0;
    $startId = $id;
    foreach($xmlx as $key1 => $array) {
        if($key1 === 0) {
            continue;
        }
        $i++;
        foreach ($array as $key => $section) {
            if($section) {
                if($key === 0) {
                    $sections[] = [$id, 0, $quote . addslashes(strval($section)) . $quote, $key];
                    $id++;
                } else {
                    $sections[] = [$id, findParent($id, $sections, $key, $startId), $quote . addslashes(strval($section)) . $quote, $key];
                    $id++;
                }

            }
        }
    }
    echo 'next id is - ' . (sizeof($sections) + $startId) . PHP_EOL;
    return $sections;
}

function findParent($id, $sections, $deep, $startId) {

    for($i = $id - $startId - 1; $i >= 0; $i--) {

        if ($sections[$i][3] === $deep - 1) {
            return $sections[$i][0];
        }
        if($i == $startId + 10) {
        }
    }
}

function makeNewParents($parents , $deep) {
    $newParents = [];
    foreach ($parents as $key => $parent) {
        if($key < $deep) {
            $newParents[] = $parent;
        }
    }
    return $newParents;
}

function toQuery($sections) {
    $quote = chr(39);
    $url = $quote . "https://" . substr($sections[0][2], 1);
    $newSections = [];
    foreach($sections as $section) {
        $section[3] = $section[2]; //name
        $section[2] = 0;  //ref_cat_id
        $section[4] = $url; //url
        $section[5] = 0; //css_path
        $section[6] = 0; //xpath
        $newSections[] = $section;
    }
    return $newSections;
}


function makeCsv($csvName = 'sections', $sections) {
    $csvName = $csvName . ".sql";
    $file = fopen($csvName, 'w');
    $sections = toQuery($sections);
    $query = "insert into sections (id,pid,ref_cat_id,name,url,css_path,xpath) values" . PHP_EOL;
    fwrite($file, $query);
    foreach ($sections as $key => $section) {
        if($key === 0) {
            fwrite($file, '(' . implode(',', $section) . ')' . PHP_EOL);
        } else {
            fwrite($file, ',(' . implode(',', $section) . ')' . PHP_EOL);
        }
    }
    fclose($file);
    echo 'done! made ' . sizeof($sections) . ' strings.';
}


function start() {
    $startId = readline("Start id (int): ");
    if(ctype_digit($startId)) {
        $intId = intval($startId);
        $file = readline("Which file needs to refine?: ") . ".xlsx";
        if(!file_exists($file) & $file !== "") {
            echo 'Here is no such file!';
            exit();
        }
        $filename = readline("What the name of new file?: ");
        if(!$file) {
            makeCsv($filename, makeSections($intId));
        } else {
            makeCsv($filename, makeSections($intId, $file));
        }

    } else {
        echo 'This is not integer';
    }
}


start();