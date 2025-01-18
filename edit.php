<?php
$dataFile = 'data.json';

// Lire les données existantes
$data = json_decode(file_get_contents($dataFile), true);

// Récupérer les données envoyées par la requête POST
$input = json_decode(file_get_contents('php://input'), true);

// Vérifier si l'ID est fourni
if (isset($input['id'])) {
    $id = $input['id'];

    // Rechercher l'entrée correspondante dans le fichier
    foreach ($data as &$bill) {
        if ($bill['id'] == $id) {
            // Mettre à jour les champs modifiables
            foreach ($input as $key => $value) {
                $bill[$key] = $value; // Ne pas reformater ici
            }
            break;
        }
    }

    // Sauvegarder les modifications dans le fichier
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));

    // Rediriger vers la page principale
    header('Location: logs.php');
    exit;
} else {
    // Si l'ID est manquant, afficher une erreur
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
}
?>