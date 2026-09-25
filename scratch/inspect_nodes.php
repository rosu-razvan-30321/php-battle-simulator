<?php
// Script to inspect GLTF model node names in warrior.glb and monster.glb
$warriorData = json_decode(file_get_contents('assets/warrior.glb'));
echo "Warrior file size: " . filesize('assets/warrior.glb') . " bytes\n";
