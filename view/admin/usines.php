<?php
$pageTitle = "Usines & Fournisseurs";
include "partials/admin_header.php";

// CRUD inline
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['form_action']??'';
    if ($act==='create') {
        $data = $_POST;
        $data['user_name'] = $data['nom'];
        $ok = registerUser($cnx, $data);
        if ($ok) {
            $cnx->prepare("UPDATE users SET role=:r WHERE email=:e")->execute([':r'=>$data['role'],':e'=>$data['email']]);
            $_SESSION['success'] = "Compte créé.";
        } else { $_SESSION['error'] = "Email déjà utilisé."; }
        header("Location: ".BASE."/view/admin/usines.php"); exit();
    }
    if ($act==='update') {
        $ok = updateUser($cnx, $_POST);
        $_SESSION[$ok?'success':'error'] = $ok?"Mis à jour.":"Erreur.";
        header("Location: ".BASE."/view/admin/usines.php"); exit();
    }
    if ($act==='delete') {
        $ok = deleteUser($cnx, (int)$_POST['uid']);
        $_SESSION[$ok?'success':'error'] = $ok?"Supprimé.":"Erreur.";
        header("Location: ".BASE."/view/admin/usines.php"); exit();
    }
    if ($act==='toggle') {
        toggleUserActif($cnx,(int)$_POST['uid']);
        header("Location: ".BASE."/view/admin/usines.php"); exit();
    }
}

$usines = getAllUsers($cnx,'usine');

?>


<!-- USINES -->
<div class="card">
  <div class="card-title">
    <i class="fas fa-industry"></i> Unités de production
    <span class="card-title-count"><?= count($usines) ?> usine(s)</span>
  </div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Nom</th>
          <th>Email</th>
          <th>Téléphone</th>
          <th>Inscrit le</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($usines as $u): ?>
      <tr class="usine-row">
        <td><?= $u['id'] ?></td>
        <td><strong><?= htmlspecialchars($u['nom']) ?></strong></td>
        <td class="usine-email"><?= htmlspecialchars($u['email']) ?></td>
        <td class="usine-phone"><?= htmlspecialchars($u['telephone']??'–') ?></td>
        <td class="usine-date"><?= date('d/m/Y',strtotime($u['created_at'])) ?></td>
        <td>
          <span class="badge <?= $u['actif']?'badge-success':'badge-danger' ?>">
            <?= $u['actif']?'Actif':'Inactif' ?>
          </span>
        </td>
        <td class="usine-actions">
          <button class="btn-usine-edit" onclick="openEdit(<?= htmlspecialchars(json_encode($u)) ?>,'usine')">
            <i class="fas fa-edit"></i>
          </button>
          <form method="POST" class="form-inline">
            <input type="hidden" name="form_action" value="toggle"/>
            <input type="hidden" name="uid" value="<?= $u['id'] ?>"/>
            <button type="submit" class="btn-usine-toggle">
              <i class="fas <?= $u['actif'] ? 'fa-lock' : 'fa-lock-open' ?>"></i>
              <?= $u['actif'] ? 'Désactiver' : 'Activer' ?>
            </button>
          </form>
          <form method="POST" class="form-inline">
            <input type="hidden" name="form_action" value="delete"/>
            <input type="hidden" name="uid" value="<?= $u['id'] ?>"/>
            <button type="submit" class="btn-usine-delete" onclick="return confirm('Supprimer cet utilisateur ?')">
              <i class="fas fa-trash-alt"></i>
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($usines)): ?>
        <tr class="empty-row">
          <td colspan="7"><i class="fas fa-inbox"></i> Aucune usine enregistrée.</td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>



<!-- MODAL CRÉER -->
<div id="createModal" class="user-modal">
  <div class="user-modal-content">
    <div class="user-modal-header">
      <h2><i class="fas fa-user-plus"></i> Créer un compte</h2>
      <button class="user-modal-close" onclick="document.getElementById('createModal').style.display='none'">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="form_action" value="create"/>
      <div class="form-group">
        <label><i class="fas fa-tag"></i> Rôle</label>
        <select class="form-control" name="role">
          <option value="usine">🏭 Usine / Production</option>
          <option value="livreur">🚚 Livreur</option>
        </select>
      </div>
      <div class="form-group">
        <label><i class="fas fa-user"></i> Nom complet *</label>
        <input class="form-control" name="nom" required/>
      </div>
      <div class="form-group">
        <label><i class="fas fa-envelope"></i> Email *</label>
        <input class="form-control" type="email" name="email" required/>
      </div>
      <div class="form-group">
        <label><i class="fas fa-phone"></i> Téléphone</label>
        <input class="form-control" name="telephone" placeholder="+216 XX XXX XXX"/>
      </div>
      <div class="form-group">
        <label><i class="fas fa-lock"></i> Mot de passe *</label>
        <input class="form-control" type="password" name="password" required/>
      </div>
      <div class="user-modal-actions">
        <button type="submit" class="btn btn-green"><i class="fas fa-save"></i> Créer</button>
        <button type="button" onclick="document.getElementById('createModal').style.display='none'" class="btn btn-gray"><i class="fas fa-times"></i> Annuler</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL MODIFIER -->
<div id="editModal" class="user-modal">
  <div class="user-modal-content">
    <div class="user-modal-header">
      <h2><i class="fas fa-edit"></i> Modifier l'utilisateur</h2>
      <button class="user-modal-close" onclick="document.getElementById('editModal').style.display='none'">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="form_action" value="update"/>
      <input type="hidden" name="idu" id="em_id"/>
      <input type="hidden" name="role" id="em_role"/>
      <div class="form-group">
        <label><i class="fas fa-user"></i> Nom</label>
        <input class="form-control" name="user_name" id="em_nom" required/>
      </div>
      <div class="form-group">
        <label><i class="fas fa-envelope"></i> Email</label>
        <input class="form-control" type="email" name="email" id="em_email" required/>
      </div>
      <div class="form-group">
        <label><i class="fas fa-phone"></i> Téléphone</label>
        <input class="form-control" name="telephone" id="em_tel"/>
      </div>
      <div class="form-group">
        <label><i class="fas fa-lock"></i> Nouveau mot de passe <small>(vide = inchangé)</small></label>
        <input class="form-control" type="password" name="password"/>
      </div>
      <div class="user-modal-actions">
        <button type="submit" class="btn btn-green"><i class="fas fa-save"></i> Enregistrer</button>
        <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn btn-gray"><i class="fas fa-times"></i> Annuler</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(u, role) {
  document.getElementById('em_id').value   = u.id;
  document.getElementById('em_nom').value  = u.nom;
  document.getElementById('em_email').value = u.email;
  document.getElementById('em_tel').value  = u.telephone || '';
  document.getElementById('em_role').value = role;
  document.getElementById('editModal').style.display = 'flex';
}
</script>

<?php include "partials/admin_footer.php"; ?>