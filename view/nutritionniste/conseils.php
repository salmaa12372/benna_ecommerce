<?php
$pageTitle = "Mes Conseils";
include "partials/nutri_header.php";
$conseils = getAllConseils($cnx);
$produits = getAllProduits($cnx);
?>
<div class="topbar">
  <h1>Conseils Nutritionnels</h1>
  <button class="btn btn-green" onclick="openAddModal()">+ Nouveau conseil</button>
</div>

<div class="card">
  <table>
    <thead>
      <tr>
        <th>Titre</th>
        <th>Produit lié</th>
        <th>Auteur</th>
        <th>Visibilité</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($conseils as $c): ?>
    <tr>
      <td>
        <div style="font-weight:600;"><?= htmlspecialchars($c['titre']) ?></div>
        <div style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars(mb_substr($c['contenu'],0,70)) ?>...</div>
      </td>
      <td><?= $c['produit_nom'] ? htmlspecialchars($c['produit_nom']) : '–' ?></td>
      <td><?= htmlspecialchars($c['nutri_nom']) ?></td>
      <td><span class="badge <?= $c['public'] ? 'badge-green' : 'badge-yellow' ?>"><?= $c['public'] ? 'Public' : 'Privé' ?></span></td>
      <td style="font-size:.82rem;"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
      <td style="white-space:nowrap;">
        <?php if ($c['nutritionniste_id'] == $_SESSION['user_id'] || $_SESSION['role'] === 'admin'): ?>
          <button class="btn btn-yellow" style="font-size:.8rem;padding:.3rem .7rem;"
                  onclick="openEditModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)">
            Modifier
          </button>
          <a href="<?= BASE ?>/controller/conseil_controller.php?action=delete&id=<?= $c['id'] ?>"
             onclick="return confirm('Supprimer ce conseil ?')"
             class="btn btn-red" style="font-size:.8rem;padding:.3rem .7rem;">
            Supprimer
          </a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($conseils)): ?>
      <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:1.5rem;">Aucun conseil publié.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- MODAL AJOUTER -->
<div id="addModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;overflow-y:auto;">
  <div style="background:white;max-width:580px;width:100%;border-radius:16px;padding:2rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
      <h2 style="font-family:'Playfair Display',serif;">Nouveau conseil</h2>
      <button onclick="document.getElementById('addModal').style.display='none'" style="border:none;background:none;font-size:1.5rem;cursor:pointer;">×</button>
    </div>
    <form action="<?= BASE ?>/controller/conseil_controller.php?action=add" method="POST">
      <div class="form-group"><label>Titre *</label><input class="form-control" name="titre" placeholder="Ex: Bienfaits du sésame" required/></div>
      <div class="form-group"><label>Produit lié (optionnel)</label>
        <select class="form-control" name="produit_id">
          <option value="">– Conseil général –</option>
          <?php foreach ($produits as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Contenu *</label>
        <textarea class="form-control" name="contenu" rows="6" placeholder="Rédigez votre conseil..." required style="resize:vertical;"></textarea>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;text-transform:none;font-size:.9rem;font-weight:normal;">
          <input type="checkbox" name="public" value="1" checked/> Rendre ce conseil public (visible sur le site)
        </label>
      </div>
      <div style="display:flex;gap:.8rem;margin-top:1rem;">
        <button type="submit" class="btn btn-green" style="flex:1;padding:.8rem;">Publier</button>
        <button type="button" onclick="document.getElementById('addModal').style.display='none'" class="btn btn-gray">Annuler</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL MODIFIER -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;overflow-y:auto;">
  <div style="background:white;max-width:580px;width:100%;border-radius:16px;padding:2rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
      <h2 style="font-family:'Playfair Display',serif;">Modifier le conseil</h2>
      <button onclick="document.getElementById('editModal').style.display='none'" style="border:none;background:none;font-size:1.5rem;cursor:pointer;">×</button>
    </div>
    <form action="<?= BASE ?>/controller/conseil_controller.php?action=edit" method="POST">
      <input type="hidden" name="id" id="edit_id"/>
      <div class="form-group"><label>Titre *</label>
        <input class="form-control" name="titre" id="edit_titre" required/>
      </div>
      <div class="form-group"><label>Produit lié (optionnel)</label>
        <select class="form-control" name="produit_id" id="edit_produit_id">
          <option value="">– Conseil général –</option>
          <?php foreach ($produits as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Contenu *</label>
        <textarea class="form-control" name="contenu" id="edit_contenu" rows="6" required style="resize:vertical;"></textarea>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;text-transform:none;font-size:.9rem;font-weight:normal;">
          <input type="checkbox" name="public" id="edit_public" value="1"/> Rendre ce conseil public
        </label>
      </div>
      <div style="display:flex;gap:.8rem;margin-top:1rem;">
        <button type="submit" class="btn btn-green" style="flex:1;padding:.8rem;">Enregistrer</button>
        <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn btn-gray">Annuler</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddModal() {
  document.getElementById('addModal').style.display = 'flex';
}

function openEditModal(c) {
  document.getElementById('edit_id').value         = c.id;
  document.getElementById('edit_titre').value      = c.titre;
  document.getElementById('edit_contenu').value    = c.contenu;
  document.getElementById('edit_produit_id').value = c.produit_id ?? '';
  document.getElementById('edit_public').checked   = c.public == 1;
  document.getElementById('editModal').style.display = 'flex';
}

// Close on backdrop click
['addModal','editModal'].forEach(id => {
  document.getElementById(id).addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
  });
});
</script>

<?php include "partials/nutri_footer.php"; ?>