<?php
$dataFile = 'data.json';
$data = json_decode(file_get_contents($dataFile), true);

// Calculer le total des montants pour la semaine en cours
$currentDate = new DateTime();
$startOfWeek = (clone $currentDate)->modify('last sunday');
$endOfWeek = (clone $currentDate)->modify('next saturday');

$totalWeekAmount = array_reduce($data, function ($carry, $bill) use ($startOfWeek, $endOfWeek) {
    $billedDate = DateTime::createFromFormat('Y-m-d', $bill['billed_date']);
    if ($billedDate && $billedDate >= $startOfWeek && $billedDate <= $endOfWeek) {
        $carry += $bill['amount'];
    }
    return $carry;
}, 0);

// Inverser l'ordre des données pour afficher les plus récentes en premier
$data = array_reverse($data);

// Lire les paramètres de tri
$sort = $_GET['sort'] ?? '';
$order = $_GET['order'] ?? 'asc';

// Fonction de tri dynamique
if ($sort) {
    usort($data, function($a, $b) use ($sort, $order) {
        if (!isset($a[$sort]) || !isset($b[$sort])) {
            return 0;
        }

        if ($order === 'asc') {
            return $a[$sort] <=> $b[$sort];
        } else {
            return $b[$sort] <=> $a[$sort];
        }
    });
}

// Filtrage basé sur la recherche
$search_query = $_GET['search'] ?? '';
if ($search_query) {
    $data = array_filter($data, function($bill) use ($search_query) {
        return stripos($bill['client_name'], $search_query) !== false ||
               stripos($bill['logged_by'], $search_query) !== false ||
               stripos($bill['client_bill_number'], $search_query) !== false ||
               stripos((string)$bill['amount'], $search_query) !== false ||
               stripos($bill['billed_date'], $search_query) !== false ||
               stripos((string)$bill['bill_number'], $search_query) !== false;
    });
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Factures Enregistrées</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Factures Enregistrées</h1>
    <a href="index.php">Ajouter une Nouvelle Facture</a>

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
    <div>
        <strong>Total des achats (semaine en cours) :</strong> 
        <span><?= "$ " . number_format($totalWeekAmount, 2, '.', ',') ?></span>
    </div>
    <form method="GET" action="logs.php" class="search-form" id="form" style="margin: 0;">
        <input type="text" name="search" placeholder="Rechercher..." value="<?= htmlspecialchars($search_query) ?>">
        <button type="submit">Rechercher</button>
    </form>
</div>

    <table>
        <tr>
            <th><a href="?sort=client_name&order=<?= $sort === 'client_name' && $order === 'asc' ? 'desc' : 'asc' ?>">FOURNISSEUR</a></th>
            <th><a href="?sort=amount&order=<?= $sort === 'amount' && $order === 'asc' ? 'desc' : 'asc' ?>">MONTANT</a></th>
            <th><a href="?sort=bill_number&order=<?= $sort === 'bill_number' && $order === 'asc' ? 'desc' : 'asc' ?>">CODE</a></th>
            <th><a href="?sort=client_bill_number&order=<?= $sort === 'client_bill_number' && $order === 'asc' ? 'desc' : 'asc' ?>"># FACTURE CLIENT</a></th>
            <th><a href="?sort=billed_date&order=<?= $sort === 'billed_date' && $order === 'asc' ? 'desc' : 'asc' ?>">DATE DE FACTURATION</a></th>
            <th><a href="?sort=logged_by&order=<?= $sort === 'logged_by' && $order === 'asc' ? 'desc' : 'asc' ?>">PAR</a></th>
            <th><a href="?sort=created_at&order=<?= $sort === 'created_at' && $order === 'asc' ? 'desc' : 'asc' ?>">DATE DU LOG</a></th>
            <th></th>
        </tr>
        <?php if (!empty($data)) { ?>
            <?php foreach ($data as $bill) { ?>
                <tr>
                    <td><?= htmlspecialchars($bill['client_name']) ?></td>
                    <td><?= htmlspecialchars($bill['amount']) ?></td>
                    <td><?= htmlspecialchars($bill['bill_number']) ?></td>
                    <td><?= htmlspecialchars($bill['client_bill_number']) ?></td>
                    <td><?= htmlspecialchars($bill['billed_date']) ?></td>
                    <td><?= htmlspecialchars($bill['logged_by']) ?></td>
                    <td><?= htmlspecialchars($bill['created_at']) ?></td>
                    <td class="delete-cell">
    <form method="POST" action="delete.php" style="margin: 0; padding: 0; background-color:none;">
        <input type="hidden" name="id" value="<?= $bill['id'] ?>">
        <button type="submit" class="flat" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette entrée ?');">
            x
        </button>
    </form>
</td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="8">Aucun résultat trouvé pour "<?= htmlspecialchars($search_query) ?>"</td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>