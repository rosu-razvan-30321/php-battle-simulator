<?php
function parseGltfJson($filePath) {
    $handle = fopen($filePath, 'rb');
    $magic = fread($handle, 4);
    $version = unpack('V', fread($handle, 4))[1];
    $length = unpack('V', fread($handle, 4))[1];
    $chunkLength = unpack('V', fread($handle, 4))[1];
    $chunkType = fread($handle, 4);
    $jsonContent = fread($handle, $chunkLength);
    fclose($handle);
    return json_decode($jsonContent, true);
}

$warrior = parseGltfJson('assets/warrior.glb');
echo "WARRIOR NODES:\n";
if (isset($warrior['nodes'])) {
    foreach ($warrior['nodes'] as $i => $n) {
        echo "[$i] " . ($n['name'] ?? 'unnamed') . "\n";
    }
}

$monster = parseGltfJson('assets/monster.glb');
echo "\nMONSTER NODES:\n";
if (isset($monster['nodes'])) {
    foreach ($monster['nodes'] as $i => $n) {
        echo "[$i] " . ($n['name'] ?? 'unnamed') . "\n";
    }
}
