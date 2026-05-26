<?php
$pageTitle = "Produits";
include "partials/admin_header.php";
$produits = getAllProduitsAdmin($cnx);
$categories = getAllCategories($cnx);
$allergenes = getAllAllergenes($cnx);
?>

<div class="topbar">
  <h1><i class="fas fa-leaf"></i> Gestion des Produits</h1>
  <button class="btn btn-green" onclick="toggleModal('addModal')">
    <i class="fas fa-plus"></i> Ajouter un produit
  </button>
</div>

<div class="card">
  <div class="card-title">
    <i class="fas fa-boxes"></i> Catalogue des produits
  </div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>Image</th>
          <th>Nom</th>
          <th>Catégorie</th>
          <th>Prix</th>
          <th>Stock</th>
          <th>Régime</th>
          <th>Validation</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($produits as $p): ?>
      <tr class="product-row">
        <td><img src="<?= BASE ?>/<?= htmlspecialchars($p['image']??'') ?>" class="product-img" onerror="this.src='https://placehold.co/48x48/e8f0e3/2c5e2e?text=🌿'"/></td>
        <td>
          <strong><?= htmlspecialchars($p['nom']) ?></strong>
          <?php if ($p['est_bestseller']): ?>
            <span class="badge badge-warning">⭐</span>
          <?php endif; ?>
          <?php if ($p['est_nouveau']): ?>
            <span class="badge badge-info">NEW</span>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($p['cat_nom']??'–') ?></td>
        <td><strong><?= number_format($p['prix'],3) ?> TND</strong></td>
        <td>
          <span class="badge <?= $p['stock']<=10?'badge-danger':($p['stock']<=30?'badge-warning':'badge-success') ?>">
            <?= $p['stock'] ?>
          </span>
        </td>
        <td class="regime-cell"><?= htmlspecialchars($p['regime']??'–') ?></td>
        <td>
          <span class="badge <?= $p['est_valide']?'badge-success':'badge-warning' ?>">
            <?= $p['est_valide'] ? 'Validé' : 'Non validé' ?>
          </span>
        </td>
        <td class="product-actions">
          <button class="btn-product-edit" onclick="openEdit(<?= htmlspecialchars(json_encode($p)) ?>)">
            <i class="fas fa-edit"></i> Modifier
          </button>
          <a href="<?= BASE ?>/controller/produit_controller.php?action=delete&id=<?= $p['id'] ?>"
             onclick="return confirm('Supprimer ce produit ?')" 
             class="btn-product-delete">
            <i class="fas fa-trash-alt"></i> Supprimer
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL AJOUTER -->
<div id="addModal" class="product-modal">
  <div class="product-modal-content">
    <div class="product-modal-header">
      <h2><i class="fas fa-plus-circle"></i> Ajouter un produit</h2>
      <button class="product-modal-close" onclick="toggleModal('addModal')">&times;</button>
    </div>
    <form action="<?= BASE ?>/controller/produit_controller.php?action=add" method="POST" enctype="multipart/form-data">
      <div class="form-grid">
        <div class="form-group"><label>Nom *</label><input class="form-control" name="nom" required/></div>
        <div class="form-group"><label>Catégorie</label>
          <select class="form-control" name="categorie_id">
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Prix (TND) *</label><input class="form-control" name="prix" type="number" step=".001" required/></div>
        <div class="form-group"><label>Stock *</label><input class="form-control" name="stock" type="number" value="0" required/></div>
        <div class="form-group"><label>Calories</label><input class="form-control" name="calories" type="number" value="0"/></div>
        <div class="form-group"><label>Protéines (g)</label><input class="form-control" name="proteines" type="number" step=".01" value="0"/></div>
        <div class="form-group"><label>Glucides (g)</label><input class="form-control" name="glucides" type="number" step=".01" value="0"/></div>
        <div class="form-group"><label>Lipides (g)</label><input class="form-control" name="lipides" type="number" step=".01" value="0"/></div>
      </div>
      <div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="3"></textarea></div>
      <div class="form-group"><label>Régime</label><input class="form-control" name="regime" placeholder="sans-gluten,bio,vegan"/></div>
      <div class="form-group"><label>Image</label><input class="form-control" name="image" type="file" accept="image/*"/></div>
      <div class="form-group">
        <label>Allergènes</label>
        <div class="checkbox-group">
          <?php foreach ($allergenes as $a): ?>
          <label class="checkbox-label">
            <input type="checkbox" name="allergenes[]" value="<?= $a['id'] ?>"/> 
            <?= $a['icone'] ?> <?= htmlspecialchars($a['nom']) ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="checkbox-inline">
        <label class="checkbox-label"><input type="checkbox" name="est_bestseller" value="1"/> ⭐ Bestseller</label>
        <label class="checkbox-label"><input type="checkbox" name="est_nouveau" value="1"/> 🆕 Nouveau</label>
      </div>
      <div class="modal-actions">
        <button type="submit" class="btn btn-green"><i class="fas fa-save"></i> Ajouter</button>
        <button type="button" onclick="toggleModal('addModal')" class="btn btn-gray"><i class="fas fa-times"></i> Annuler</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL MODIFIER -->
<div id="editModal" class="product-modal">
  <div class="product-modal-content">
    <div class="product-modal-header">
      <h2><i class="fas fa-edit"></i> Modifier le produit</h2>
      <button class="product-modal-close" onclick="toggleModal('editModal')">&times;</button>
    </div>
    <form action="<?= BASE ?>/controller/produit_controller.php?action=update" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" id="edit_id"/>
      <div class="form-grid">
        <div class="form-group"><label>Nom *</label><input class="form-control" name="nom" id="edit_nom" required/></div>
        <div class="form-group"><label>Catégorie</label>
          <select class="form-control" name="categorie_id" id="edit_cat">
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Prix (TND)</label><input class="form-control" name="prix" id="edit_prix" type="number" step=".001"/></div>
        <div class="form-group"><label>Stock</label><input class="form-control" name="stock" id="edit_stock" type="number"/></div>
        <div class="form-group"><label>Calories</label><input class="form-control" name="calories" id="edit_cal" type="number"/></div>
        <div class="form-group"><label>Protéines</label><input class="form-control" name="proteines" id="edit_prot" type="number" step=".01"/></div>
        <div class="form-group"><label>Glucides</label><input class="form-control" name="glucides" id="edit_gluc" type="number" step=".01"/></div>
        <div class="form-group"><label>Lipides</label><input class="form-control" name="lipides" id="edit_lip" type="number" step=".01"/></div>
      </div>
      <div class="form-group"><label>Description</label><textarea class="form-control" name="description" id="edit_desc" rows="3"></textarea></div>
      <div class="form-group"><label>Régime</label><input class="form-control" name="regime" id="edit_regime"/></div>
      <div class="form-group"><label>Nouvelle image</label><input class="form-control" name="image" type="file" accept="image/*"/></div>
      <div class="checkbox-inline">
        <label class="checkbox-label"><input type="checkbox" name="est_bestseller" id="edit_best" value="1"/> ⭐ Bestseller</label>
        <label class="checkbox-label"><input type="checkbox" name="est_nouveau" id="edit_new" value="1"/> 🆕 Nouveau</label>
      </div>
      <div class="modal-actions">
        <button type="submit" class="btn btn-green"><i class="fas fa-save"></i> Enregistrer</button>
        <button type="button" onclick="toggleModal('editModal')" class="btn btn-green"><i class="fas fa-times"></i> Annuler</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleModal(id){
  const m = document.getElementById(id);
  m.style.display = m.style.display === 'flex' ? 'none' : 'flex';
}

function openEdit(p){
  document.getElementById('edit_id').value    = p.id;
  document.getElementById('edit_nom').value   = p.nom;
  document.getElementById('edit_desc').value  = p.description || '';
  document.getElementById('edit_prix').value  = p.prix;
  document.getElementById('edit_stock').value = p.stock;
  document.getElementById('edit_cat').value   = p.categorie_id;
  document.getElementById('edit_regime').value = p.regime || '';
  document.getElementById('edit_cal').value   = p.calories || 0;
  document.getElementById('edit_prot').value  = p.proteines || 0;
  document.getElementById('edit_gluc').value  = p.glucides || 0;
  document.getElementById('edit_lip').value   = p.lipides || 0;
  document.getElementById('edit_best').checked = p.est_bestseller == 1;
  document.getElementById('edit_new').checked = p.est_nouveau == 1;
  document.getElementById('editModal').style.display = 'flex';
}
</script>

<?php include "partials/admin_footer.php"; ?>
