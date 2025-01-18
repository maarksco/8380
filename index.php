<?php
$dataFile = 'data.json';

// Charger les données existantes ou créer un fichier vide si nécessaire
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}
$data = json_decode(file_get_contents($dataFile), true);

// Recalculer les IDs et les numéros de facture
$data = array_values($data); // Réindexer les entrées
foreach ($data as $index => &$bill) {
    $bill['id'] = $index + 1;
    $bill['bill_number'] = $index + 1;
}
file_put_contents($dataFile, json_encode($data));

// Calculer le prochain numéro séquentiel interne
$next_bill_number = count($data) > 0 ? end($data)['bill_number'] + 1 : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = $_POST['client_name'];
    $amount = number_format((float)$_POST['amount'], 2, '.', ''); // Formatage en 2 décimales
    $billed_date = $_POST['billed_date'];
    $client_bill_number = $_POST['client_bill_number'];
    $logged_by = $_POST['logged_by'];

    $newBill = [
        'id' => $next_bill_number,
        'client_name' => $client_name,
        'amount' => $amount,
        'bill_number' => $next_bill_number,
        'client_bill_number' => $client_bill_number,
        'billed_date' => $billed_date,
        'logged_by' => $logged_by,
        'created_at' => date('Y-m-d H:i:s')
    ];
    $data[] = $newBill;

    // Enregistrer dans le fichier JSON
    file_put_contents($dataFile, json_encode($data));

    // Rediriger vers la page des logs
    header('Location: logs.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Log Facture Beauport</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Log Facture (IGA BEAUPORT 8380)</h1>
    <a href="logs.php">Visualiser les Logs</a>
    <form method="POST" action="" id="form">
        <label>CODE DE FACTURE (généré automatiquement)</label>
        <h1><?= $next_bill_number ?></h1>
        <label># Facture Fournisseur : 
            <input type="text" name="client_bill_number" required>
        </label>
        <label>Fournisseur: <input type="text" name="client_name" required></label>
        <label>Montant: <input type="number" name="amount" step="0.01" required></label>
        <label>Date de facturation: <input type="date" name="billed_date" required></label>
        <label>Enregistré par: 
            <select name="logged_by">
                <option value="marc-antoine">Marc-Antoine</option>
                <option value="martin">Martin</option>
                <option value="keven">Keven</option>
                <option value="other">Autre</option>
            </select>
        </label>
        <button type="submit">Soumettre</button>
    </form>
</body>
</html>