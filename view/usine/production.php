<?php
$pageTitle = "Ordres de Production";
include "partials/usine_header.php";
$ordres   = getOrdresProduction($cnx);
$produits = getAllProduits($cnx);
$badgeMap = [
  'demande'  => 'badge-yellow',
  'en_cours' => 'badge-blue',
  'termine'  => 'badge-green',
  'annule'   => 'badge-red' 
];?>

<div class="topbar">
  <h1> Ordres de Production</h1>
  <button class="btn btn-green" onclick="document.getElementById('ordreModal').classList.add('open')">
    + Nouvel ordre
  </button>
</div>

<div class="card">
  <table>
    <thead>
      <tr>
        <th>Produit</th>
        <th>Quantité</th>
        <th>Demandé par</th>
        <th>Statut</th>
        <th>Créé le</th>
        <th>Terminé le</th>
        <th>Action</th>
      </tr>
    </thead>

    <tbody>
    <?php foreach ($ordres as $o): 

$statut = $o['statut'] ?? 'demande';


    
 ?>
      
    <tr>
      <td><strong><?= htmlspecialchars($o['produit_nom']) ?></strong></td>
      <td><?= $o['quantite'] ?> unités</td>
      <td><?= htmlspecialchars($o['demande_par_nom'] ?? '–') ?></td>

      <td>
        <span class="badge <?= $badgeMap[$statut] ?? 'badge-red' ?>">
  <?= ucfirst(str_replace('_',' ',$statut)) ?>
</span>
      </td>

      <td class="text-sm"><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
      <td class="text-sm">
        <?= $o['termine_at'] ? date('d/m/Y', strtotime($o['termine_at'])) : '–' ?>
      </td>

      <td>
        <form action="<?= BASE ?>/controller/stock_controller.php?action=update_ordre"
              method="POST"
              class="flex gap-sm">

          <input type="hidden" name="ordre_id" value="<?= $o['id'] ?>"/>

          <?php if ($statut === 'demande'): ?>

  <button name="statut" value="en_cours" class="btn btn-blue btn-sm">
    ▶ Démarrer
  </button>

<?php elseif ($statut === 'en_cours'): ?>

  <button name="statut" value="termine" class="btn btn-green btn-sm">
    ✓ Terminer
  </button>

  <button name="statut" value="annule"
          class="btn btn-red btn-sm"
          onclick="return confirm('Annuler cet ordre ?')">
    ✕ Annuler
  </button>

<?php elseif ($statut === 'termine'): ?>

  <span class="text-green fw-bold">✔ Terminé</span>

<?php elseif ($statut === 'annule'): ?>

  <span class="text-muted">✕ Annulé</span>

  

<?php else: ?>

  <span class="text-muted">—</span>

<?php endif; ?>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>

    <?php if (empty($ordres)): ?>
      <tr>
        <td colspan="7" class="text-center text-muted p-3">
          Aucun ordre de production.
        </td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- MODAL -->
<div id="ordreModal" class="modal">
  <div class="modal-content">

    <div class="modal-header-flex">
      <h2 class="modal-title">Nouvel ordre de production</h2>

      <button class="modal-close"
              onclick="document.getElementById('ordreModal').classList.remove('open')">
        ×
      </button>
    </div>

    <form action="<?= BASE ?>/controller/stock_controller.php?action=creer_ordre" method="POST">

      <div class="form-group">
        <label>Produit</label>
        <select class="form-control" name="produit_id" required>
          <option value="">Sélectionner...</option>
          <?php foreach ($produits as $p): ?>
            <option value="<?= $p['id'] ?>">
              <?= htmlspecialchars($p['nom']) ?> (stock: <?= $p['stock'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Quantité à produire</label>
        <input class="form-control" name="quantite" type="number" min="1" value="50" required/>
      </div>

      <div class="flex gap-md mt-1">
        <button type="submit" class="btn btn-green flex-1 btn-lg">
          Créer l'ordre
        </button>

        <button type="button"
                class="btn btn-gray"
                onclick="document.getElementById('ordreModal').classList.remove('open')">
          Annuler
        </button>
      </div>

    </form>
  </div>
</div>
<?php include "partials/usine_footer.php"; ?>
