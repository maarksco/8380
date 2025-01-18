<?php
$dataFile = 'data.json';
$data = json_decode(file_get_contents($dataFile), true);

// Récupérer l'ID envoyé pour suppression
$idToDelete = $_POST['id'];

// Filtrer les données pour supprimer l'entrée correspondante
$data = array_filter($data, function ($bill) use ($idToDelete) {
    return $bill['id'] != $idToDelete;
});

// Recalculer les `id` et `bill_number` pour les entrées restantes
$data = array_values($data); // Réindexer les tableaux
foreach ($data as $index => &$bill) {
    $bill['id'] = $index + 1;
    $bill['bill_number'] = $index + 1;
}

// Sauvegarder les données mises à jour
file_put_contents($dataFile, json_encode($data));

header('Location: logs.php');
exit;
?>