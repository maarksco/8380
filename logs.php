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
                    <td data-name="client_name"><?= htmlspecialchars($bill['client_name']) ?></td>
                    <td data-name="amount"><?= htmlspecialchars($bill['amount']) ?></td>
                    <td><?= htmlspecialchars($bill['bill_number']) ?></td>
                    <td data-name="client_bill_number"><?= htmlspecialchars($bill['client_bill_number']) ?></td>
                    <td data-name="billed_date"><?= htmlspecialchars($bill['billed_date']) ?></td>
                    <td data-name="logged_by"><?= htmlspecialchars($bill['logged_by']) ?></td>
                    <td><?= htmlspecialchars($bill['created_at']) ?></td>
                    <td class="edit-cell">
                        <button type="button" class="edit-btn" onclick="enableEditMode(this);">Éditer</button>
                        <form method="POST" action="delete.php" style="margin: 0; padding: 0;">
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

<script>
function enableEditMode(button) {
    const row = button.closest('tr'); // Trouve la ligne correspondante
    const cells = row.querySelectorAll('td');
    const id = row.querySelector('input[name="id"]').value;

    // Vérifier si le mode édition est déjà activé
    if (button.textContent === "Enregistrer") {
        saveEdit(row, id);
        return;
    }

    // Masquer le bouton "x" et ajouter le bouton "Annuler"
    const deleteButton = row.querySelector('.flat');
    deleteButton.style.display = "none";

    const cancelButton = document.createElement('button');
    cancelButton.textContent = "Annuler";
    cancelButton.className = "cancel-btn";
    cancelButton.onclick = function () {
        cancelEdit(row, button, deleteButton, cancelButton);
    };
    row.querySelector('.edit-cell').appendChild(cancelButton);

    // Transformer chaque cellule en champ de saisie sauf le "code", la date du log, et les boutons
    cells.forEach((cell, index) => {
        if (index === 2 || index === 6 || cell.classList.contains('edit-cell')) return;

        const originalContent = cell.textContent.trim();
        if (index === 4) { // Pour la date de facturation
            cell.innerHTML = `<input type="date" name="billed_date" value="${originalContent}">`;
        } else {
            cell.innerHTML = `<input type="text" name="${cell.dataset.name}" value="${originalContent}">`;
        }
    });

    // Transformer le bouton "Éditer" en "Enregistrer"
    button.textContent = "Enregistrer";
    button.style.color = "#27ae60"; // Vert pour indiquer une action positive
}

function saveEdit(row, id) {
    const inputs = row.querySelectorAll('input');
    const form = document.createElement('form');

    form.method = 'POST';
    form.action = 'edit.php';

    // Ajouter l'ID
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = id;
    form.appendChild(idInput);

    // Ajouter les champs modifiés
    inputs.forEach(input => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = input.name;
        hiddenInput.value = input.value;
        form.appendChild(hiddenInput);
    });

    // Soumettre le formulaire
    document.body.appendChild(form);
    form.submit();
}

    // Envoyer les données au serveur pour mise à jour
    fetch('edit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Mettre à jour la ligne avec les nouvelles valeurs
            inputs.forEach(input => {
                const cell = input.closest('td');
                const inputName = input.name;

                if (inputName === "amount") {
                    // Formatter en affichage monétaire
                    cell.textContent = formatCurrency(input.value);
                } else {
                    cell.textContent = input.value;
                }
            });

            // Mettre à jour le total des achats de la semaine
            updateWeeklyTotal(result.newTotal);

            // Restaurer les boutons et l'état
            resetEditMode(row);
        } else {
            alert('Erreur lors de la mise à jour');
        }
    })
    .catch(err => console.error(err));
}

function cancelEdit(row, editButton, deleteButton, cancelButton) {
    // Réinitialiser la ligne avec les valeurs actuelles
    const cells = row.querySelectorAll('td');
    cells.forEach((cell, index) => {
        if (index === 2 || index === 6 || cell.classList.contains('edit-cell')) return;

        const input = cell.querySelector('input');
        if (input) {
            if (input.name === "amount") {
                // Restaurer et reformater le montant en affichage monétaire
                cell.textContent = formatCurrency(input.defaultValue);
            } else {
                cell.textContent = input.defaultValue;
            }
        }
    });

    // Réafficher le bouton "x" et supprimer le bouton "Annuler"
    deleteButton.style.display = "inline";
    cancelButton.remove();

    // Restaurer le bouton "Enregistrer" en "Éditer"
    editButton.textContent = "Éditer";
    editButton.style.color = "#e74c3c";
}

function resetEditMode(row) {
    const editButton = row.querySelector('.edit-btn');
    const deleteButton = row.querySelector('.flat');
    const cancelButton = row.querySelector('.cancel-btn');

    // Supprimer le bouton "Annuler"
    if (cancelButton) {
        cancelButton.remove();
    }

    // Réafficher le bouton "x"
    deleteButton.style.display = "inline";

    // Restaurer le bouton "Enregistrer" en "Éditer"
    editButton.textContent = "Éditer";
    editButton.style.color = "#e74c3c";
}

// Fonction pour formater un montant en affichage monétaire
function formatCurrency(value) {
    const amount = parseFloat(value).toFixed(2);
    return `$ ${amount}`;
}

// Mettre à jour dynamiquement le total des achats de la semaine
function updateWeeklyTotal(newTotal) {
    const totalElement = document.querySelector("span");
    totalElement.textContent = `$ ${parseFloat(newTotal).toFixed(2)}`;
}
</script>

</body>
</html>