<?php
// download-model.php
$modelFiles = [
    'model.json' => 'https://storage.googleapis.com/tfjs-models/savedmodel/blazeface/model.json',
    'group1-shard1of1.bin' => 'https://storage.googleapis.com/tfjs-models/savedmodel/blazeface/group1-shard1of1.bin'
];

if (!file_exists('models')) {
    mkdir('models', 0777, true);
}

foreach ($modelFiles as $filename => $url) {
    $filepath = 'models/' . $filename;
    if (!file_exists($filepath)) {
        echo "Downloading $filename...\n";
        $content = file_get_contents($url);
        file_put_contents($filepath, $content);
        echo "Downloaded $filename successfully.\n";
    } else {
        echo "$filename already exists.\n";
    }
}

echo "All model files downloaded successfully!";
?>