<?php
require_once __DIR__.'/auth.php';
$pdo = db();

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];

function go(string $msg=''): never {
    header('Location: dashboard.php'.($msg ? '?msg='.urlencode($msg) : ''));
    exit;
}
function slugify(string $text): string {
    $text = iconv('UTF-8','ASCII//TRANSLIT',$text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/','-', $text);
    return trim($text,'-') ?: 'formation-'.time();
}
function uploadImage(string $field): string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return '';
    $allowed = ['jpg','jpeg','png','webp','gif'];
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext,$allowed,true)) return '';
    if ($_FILES[$field]['size'] > 8*1024*1024) return '';
    $name = 'media_'.bin2hex(random_bytes(10)).'.'.$ext;
    move_uploaded_file($_FILES[$field]['tmp_name'], __DIR__.'/../uploads/'.$name);
    return $name;
}
function deleteUpload(?string $file): void {
    if (!$file || in_array($file,['flyer.png','logo.png'],true)) return;
    $path = __DIR__.'/../uploads/'.$file;
    if (is_file($path)) @unlink($path);
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) { http_response_code(403); exit('Action non autorisée.'); }
    $action = $_POST['action'] ?? '';

    if ($action === 'settings') {
        $allowed = ['site_name','tagline','phone','whatsapp','address','email','hero_title','hero_text'];
        $find = $pdo->prepare("SELECT id FROM settings WHERE setting_key=? LIMIT 1");
        $update = $pdo->prepare("UPDATE settings SET setting_value=?, updated_at=CURRENT_TIMESTAMP WHERE setting_key=?");
        $insert = $pdo->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES(?,?,CURRENT_TIMESTAMP)");
        foreach ($allowed as $key) {
            $value = trim($_POST[$key] ?? '');
            $find->execute([$key]);
            if ($find->fetch()) $update->execute([$value, $key]);
            else $insert->execute([$key, $value]);
        }
        go('settings');
    }

    if ($action === 'formation_save') {
        $id=(int)($_POST['id']??0);
        $title=trim($_POST['title']??'');
        if (!$title) go('error');
        $slug=slugify($_POST['slug']??$title);
        $image=trim($_POST['old_image']??'');
        $newImage=uploadImage('image');
        if ($newImage) $image=$newImage;
        if (!$image) $image='flyer.png';
        if ($id) {
            $st=$pdo->prepare("UPDATE formations SET title=?,slug=?,short_description=?,description=?,duration=?,level=?,skills=?,outcomes=?,image=?,featured=?,sort_order=? WHERE id=?");
            $st->execute([$title,$slug,trim($_POST['short_description']??''),trim($_POST['description']??''),trim($_POST['duration']??''),trim($_POST['level']??''),trim($_POST['skills']??''),trim($_POST['outcomes']??''),$image,isset($_POST['featured'])?1:0,(int)($_POST['sort_order']??0),$id]);
        } else {
            $st=$pdo->prepare("INSERT INTO formations(title,slug,short_description,description,duration,level,skills,outcomes,image,featured,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
            $st->execute([$title,$slug,trim($_POST['short_description']??''),trim($_POST['description']??''),trim($_POST['duration']??''),trim($_POST['level']??''),trim($_POST['skills']??''),trim($_POST['outcomes']??''),$image,isset($_POST['featured'])?1:0,(int)($_POST['sort_order']??0)]);
        }
        go('formation');
    }

    if ($action === 'gallery_save') {
        $image=uploadImage('image');
        if (!$image) go('error');
        $pdo->prepare("INSERT INTO gallery(title,image,category) VALUES(?,?,?)")
            ->execute([trim($_POST['title']??'Sans titre'),$image,trim($_POST['category']??'Centre')]);
        go('gallery');
    }

    if ($action === 'testimonial_save') {
        $id=(int)($_POST['id']??0);
        $photo=trim($_POST['old_photo']??'');
        $new=uploadImage('photo'); if($new)$photo=$new;
        if($id){
            $pdo->prepare("UPDATE testimonials SET name=?,role=?,message=?,rating=?,photo=? WHERE id=?")
                ->execute([trim($_POST['name']),trim($_POST['role']??''),trim($_POST['message']),max(1,min(5,(int)$_POST['rating'])), $photo ?: null,$id]);
        } else {
            $pdo->prepare("INSERT INTO testimonials(name,role,message,rating,photo) VALUES(?,?,?,?,?)")
                ->execute([trim($_POST['name']),trim($_POST['role']??''),trim($_POST['message']),max(1,min(5,(int)$_POST['rating'])), $photo ?: null]);
        }
        go('testimonial');
    }

    if ($action === 'partner_save') {
        $id=(int)($_POST['id']??0);
        $logo=trim($_POST['old_logo']??'');
        $new=uploadImage('logo_file'); if($new)$logo=$new;
        if($id){
            $pdo->prepare("UPDATE partners SET name=?,logo=?,website=? WHERE id=?")
                ->execute([trim($_POST['name']),$logo ?: null,trim($_POST['website']??''),$id]);
        } else {
            $pdo->prepare("INSERT INTO partners(name,logo,website) VALUES(?,?,?)")
                ->execute([trim($_POST['name']),$logo ?: null,trim($_POST['website']??'')]);
        }
        go('partner');
    }

    if ($action === 'registration_status') {
        $statuses=['Nouveau','Contacté','Confirmé','Archivé'];
        $status=$_POST['status']??'Nouveau';
        if(in_array($status,$statuses,true))
            $pdo->prepare("UPDATE registrations SET status=? WHERE id=?")->execute([$status,(int)$_POST['id']]);
        go('registration');
    }

    if ($action === 'contact_status') {
        $statuses=['Nouveau','Lu','Traité'];
        $status=$_POST['status']??'Nouveau';
        if(in_array($status,$statuses,true))
            $pdo->prepare("UPDATE contacts SET status=? WHERE id=?")->execute([$status,(int)$_POST['id']]);
        go('contact');
    }
}

if (isset($_GET['delete'],$_GET['table'])) {
    $map=['formations'=>'formations','gallery'=>'gallery','testimonials'=>'testimonials','partners'=>'partners'];
    $table=$_GET['table'];
    if(isset($map[$table])){
        $id=(int)$_GET['delete'];
        $st=$pdo->prepare("SELECT * FROM $table WHERE id=?"); $st->execute([$id]); $row=$st->fetch();
        if($row){
            foreach(['image','photo','logo'] as $col) if(isset($row[$col])) deleteUpload($row[$col]);
            $pdo->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);
        }
    }
    go('deleted');
}

$settings=[];
foreach($pdo->query("SELECT setting_key,setting_value FROM settings") as $r) $settings[$r['setting_key']]=$r['setting_value'];

$stats=[
 'formations'=>$pdo->query("SELECT COUNT(*) FROM formations")->fetchColumn(),
 'gallery'=>$pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn(),
 'testimonials'=>$pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn(),
 'inscriptions'=>$pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn(),
 'messages'=>$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn(),
];
$formations=$pdo->query("SELECT * FROM formations ORDER BY sort_order ASC,id DESC")->fetchAll();
$gallery=$pdo->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll();
$testimonials=$pdo->query("SELECT * FROM testimonials ORDER BY id DESC")->fetchAll();
$partners=$pdo->query("SELECT * FROM partners ORDER BY id DESC")->fetchAll();
$inscriptions=$pdo->query("SELECT * FROM registrations ORDER BY id DESC")->fetchAll();
$messages=$pdo->query("SELECT * FROM contacts ORDER BY id DESC")->fetchAll();
$editId=(int)($_GET['edit']??0); $editFormation=null; $editTestimonial=null; $editPartner=null;
if($editId && ($_GET['edit_table']??'')==='formations'){ $s=$pdo->prepare("SELECT * FROM formations WHERE id=?");$s->execute([$editId]);$editFormation=$s->fetch(); }
if($editId && ($_GET['edit_table']??'')==='testimonials'){ $s=$pdo->prepare("SELECT * FROM testimonials WHERE id=?");$s->execute([$editId]);$editTestimonial=$s->fetch(); }
if($editId && ($_GET['edit_table']??'')==='partners'){ $s=$pdo->prepare("SELECT * FROM partners WHERE id=?");$s->execute([$editId]);$editPartner=$s->fetch(); }
$msg=$_GET['msg']??'';
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Administration | LessGo Community</title><link rel="stylesheet" href="assets/admin.css"></head>
<body>
<aside class="sidebar">
  <div class="admin-brand"><img src="../../frontend/assets/images/logo.png" alt="LessGo"><div><b>LESSGO</b><small>CONTROL CENTER</small></div></div>
  <a href="#overview">📊 Tableau de bord</a><a href="#settings">⚙️ Paramètres</a><a href="#formations">🎓 Formations</a><a href="#gallery">🖼️ Galerie</a><a href="#testimonials">💬 Témoignages</a><a href="#partners">🤝 Partenaires</a><a href="#inscriptions">📝 Inscriptions</a><a href="#messages">✉️ Messages</a>
  <div class="side-bottom">
  <a class="site-link" href="../../frontend/index.html">🌐 Voir le site</a>
  <a class="danger" href="logout.php">↪ Déconnexion</a>
</div>
</aside>
<main class="main">
<header class="topbar"><div><span class="muted">Centre d’administration</span><h1>Bonjour, <?=htmlspecialchars($_SESSION['admin_name']??'Administrateur')?> 👋</h1></div><div class="top-actions"><span class="live">● EN LIGNE</span><a href="../../frontend/index.html" target="_blank">Ouvrir le site ↗</a></div></header>
<?php if($msg): ?><div class="alert">✓ Action enregistrée avec succès.</div><?php endif; ?>

<section id="overview" class="stats">
<?php $labels=['formations'=>'Formations','gallery'=>'Images','testimonials'=>'Témoignages','inscriptions'=>'Inscriptions','messages'=>'Messages']; foreach($stats as $k=>$v): ?>
<div class="stat"><span><?=$labels[$k]?></span><strong><?=number_format((int)$v,0,'',' ')?></strong><small>contenus</small></div>
<?php endforeach; ?>
</section>

<section id="settings" class="panel"><div class="panel-head"><div><span class="eyebrow">CONFIGURATION</span><h2>Paramètres du site</h2></div><span class="hint">Modifiez ici les informations affichées sur le site.</span></div>
<form method="post" class="grid-form"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="action" value="settings">
<label>Nom du site<input name="site_name" value="<?=htmlspecialchars($settings['site_name']??'LessGo Community')?>"></label>
<label>Slogan<input name="tagline" value="<?=htmlspecialchars($settings['tagline']??'Développez • Créez • Impactez')?>"></label>
<label>Téléphone<input name="phone" value="<?=htmlspecialchars($settings['phone']??'')?>"></label>
<label>WhatsApp<input name="whatsapp" value="<?=htmlspecialchars($settings['whatsapp']??'')?>"></label>
<label>Email<input name="email" value="<?=htmlspecialchars($settings['email']??'')?>"></label>
<label>Adresse<input name="address" value="<?=htmlspecialchars($settings['address']??'')?>"></label>
<label class="wide">Titre principal<textarea name="hero_title" rows="2"><?=htmlspecialchars($settings['hero_title']??'')?></textarea></label>
<label class="wide">Texte principal<textarea name="hero_text" rows="3"><?=htmlspecialchars($settings['hero_text']??'')?></textarea></label>
<button>💾 Enregistrer les paramètres</button></form></section>

<section id="formations" class="panel"><div class="panel-head"><div><span class="eyebrow">CATALOGUE</span><h2><?= $editFormation?'Modifier une formation':'Ajouter une formation'?></h2></div><span class="hint">Nombre illimité</span></div>
<form method="post" enctype="multipart/form-data" class="grid-form"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="action" value="formation_save"><input type="hidden" name="id" value="<?=htmlspecialchars($editFormation['id']??0)?>"><input type="hidden" name="old_image" value="<?=htmlspecialchars($editFormation['image']??'')?>">
<label>Titre<input name="title" required value="<?=htmlspecialchars($editFormation['title']??'')?>"></label>
<label>Slug<input name="slug" value="<?=htmlspecialchars($editFormation['slug']??'')?>"></label>
<label>Niveau<input name="level" value="<?=htmlspecialchars($editFormation['level']??'')?>"></label>
<label>Durée<input name="duration" value="<?=htmlspecialchars($editFormation['duration']??'')?>"></label>
<label>Ordre<input type="number" name="sort_order" value="<?=htmlspecialchars($editFormation['sort_order']??0)?>"></label>
<label>Image<input type="file" name="image" accept="image/*"></label>
<label class="wide">Résumé<input name="short_description" value="<?=htmlspecialchars($editFormation['short_description']??'')?>"></label>
<label class="wide">Description<textarea name="description" rows="5"><?=htmlspecialchars($editFormation['description']??'')?></textarea></label>
<label>Compétences<textarea name="skills" rows="4"><?=htmlspecialchars($editFormation['skills']??'')?></textarea></label>
<label>Débouchés<textarea name="outcomes" rows="4"><?=htmlspecialchars($editFormation['outcomes']??'')?></textarea></label>
<label class="check"><input type="checkbox" name="featured" <?=!empty($editFormation['featured'])?'checked':''?>> ⭐ Mettre en avant</label>
<button><?= $editFormation?'💾 Enregistrer la modification':'＋ Ajouter la formation'?></button>
<?php if($editFormation): ?><a class="cancel" href="dashboard.php#formations">Annuler</a><?php endif; ?></form>
<div class="table-wrap"><table><tr><th>Formation</th><th>Niveau</th><th>Image</th><th>Ordre</th><th>Actions</th></tr>
<?php foreach($formations as $f): ?><tr><td><b><?=htmlspecialchars($f['title'])?></b><small><?=htmlspecialchars($f['slug'])?></small></td><td><?=htmlspecialchars($f['level'])?></td><td><img class="table-img" src="../uploads/<?=htmlspecialchars($f['image']?:'flyer.png')?>"></td><td><?=$f['sort_order']?></td><td><a class="edit" href="?edit=<?=$f['id']?>&edit_table=formations#formations">Modifier</a> <a class="delete" href="?table=formations&delete=<?=$f['id']?>" onclick="return confirm('Supprimer cette formation ?')">Supprimer</a></td></tr><?php endforeach;?>
</table></div></section>

<section id="gallery" class="panel"><div class="panel-head"><div><span class="eyebrow">MÉDIATHÈQUE</span><h2>Importer des images</h2></div><span class="hint">JPG, PNG, WEBP, GIF · max 8 Mo</span></div>
<form method="post" enctype="multipart/form-data" class="grid-form"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="action" value="gallery_save">
<label>Titre<input name="title" required></label><label>Catégorie<input name="category" value="Centre"></label><label class="wide">Image<input type="file" name="image" accept="image/*" required></label><button>⬆ Importer l’image</button></form>
<div class="thumbs"><?php foreach($gallery as $g): ?><div class="thumb"><img src="../uploads/<?=htmlspecialchars($g['image'])?>" alt=""><div><b><?=htmlspecialchars($g['title'])?></b><small><?=htmlspecialchars($g['category'])?></small></div><a class="delete" href="?table=gallery&delete=<?=$g['id']?>" onclick="return confirm('Supprimer cette image ?')">×</a></div><?php endforeach;?></div></section>

<section id="testimonials" class="panel"><div class="panel-head"><div><span class="eyebrow">PREUVES SOCIALES</span><h2><?= $editTestimonial?'Modifier':'Ajouter'?> un témoignage</h2></div></div>
<form method="post" enctype="multipart/form-data" class="grid-form"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="action" value="testimonial_save"><input type="hidden" name="id" value="<?=htmlspecialchars($editTestimonial['id']??0)?>"><input type="hidden" name="old_photo" value="<?=htmlspecialchars($editTestimonial['photo']??'')?>">
<label>Nom<input name="name" required value="<?=htmlspecialchars($editTestimonial['name']??'')?>"></label><label>Rôle<input name="role" value="<?=htmlspecialchars($editTestimonial['role']??'')?>"></label><label>Note<input type="number" min="1" max="5" name="rating" value="<?=htmlspecialchars($editTestimonial['rating']??5)?>"></label><label>Photo<input type="file" name="photo" accept="image/*"></label><label class="wide">Message<textarea name="message" required><?=htmlspecialchars($editTestimonial['message']??'')?></textarea></label><button>💬 <?= $editTestimonial?'Enregistrer':'Ajouter'?></button></form>
<div class="simple-grid"><?php foreach($testimonials as $t): ?><div class="mini-card"><b><?=htmlspecialchars($t['name'])?></b><small><?=htmlspecialchars($t['role']??'')?> · <?=str_repeat('★',(int)$t['rating'])?></small><p><?=htmlspecialchars($t['message'])?></p><a class="edit" href="?edit=<?=$t['id']?>&edit_table=testimonials#testimonials">Modifier</a> <a class="delete" href="?table=testimonials&delete=<?=$t['id']?>" onclick="return confirm('Supprimer ?')">Supprimer</a></div><?php endforeach;?></div></section>

<section id="partners" class="panel"><div class="panel-head"><div><span class="eyebrow">RÉSEAU</span><h2><?= $editPartner?'Modifier':'Ajouter'?> un partenaire</h2></div></div>
<form method="post" enctype="multipart/form-data" class="grid-form"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="action" value="partner_save"><input type="hidden" name="id" value="<?=htmlspecialchars($editPartner['id']??0)?>"><input type="hidden" name="old_logo" value="<?=htmlspecialchars($editPartner['logo']??'')?>">
<label>Nom<input name="name" required value="<?=htmlspecialchars($editPartner['name']??'')?>"></label><label>Site web<input name="website" value="<?=htmlspecialchars($editPartner['website']??'')?>"></label><label class="wide">Logo<input type="file" name="logo_file" accept="image/*"></label><button>🤝 <?= $editPartner?'Enregistrer':'Ajouter'?></button></form>
<div class="simple-grid"><?php foreach($partners as $p): ?><div class="mini-card"><b><?=htmlspecialchars($p['name'])?></b><small><?=htmlspecialchars($p['website']??'')?></small><?php if($p['logo']): ?><img class="partner-img" src="../uploads/<?=htmlspecialchars($p['logo'])?>"><?php endif; ?><p><a class="edit" href="?edit=<?=$p['id']?>&edit_table=partners#partners">Modifier</a> <a class="delete" href="?table=partners&delete=<?=$p['id']?>" onclick="return confirm('Supprimer ?')">Supprimer</a></p></div><?php endforeach;?></div></section>

<section id="inscriptions" class="panel"><div class="panel-head"><div><span class="eyebrow">LEADS</span><h2>Inscriptions reçues</h2></div><span class="hint"><?=count($inscriptions)?> demandes</span></div>
<div class="table-wrap"><table><tr><th>Date</th><th>Nom</th><th>Téléphone</th><th>Formation</th><th>Statut</th></tr>
<?php foreach($inscriptions as $i): ?><tr><td><?=date('d/m/Y H:i',strtotime($i['created_at']))?></td><td><b><?=htmlspecialchars($i['first_name'].' '.$i['last_name'])?></b><small><?=htmlspecialchars($i['email']??'')?></small></td><td><?=htmlspecialchars($i['phone'])?></td><td><?=htmlspecialchars($i['formation'])?></td><td><form method="post" class="inline"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="action" value="registration_status"><input type="hidden" name="id" value="<?=$i['id']?>"><select name="status" onchange="this.form.submit()"><?php foreach(['Nouveau','Contacté','Confirmé','Archivé'] as $st): ?><option <?=$i['status']===$st?'selected':''?>><?=$st?></option><?php endforeach;?></select></form></td></tr><?php endforeach;?></table></div></section>

<section id="messages" class="panel"><div class="panel-head"><div><span class="eyebrow">MESSAGERIE</span><h2>Messages reçus</h2></div></div>
<div class="table-wrap"><table><tr><th>Date</th><th>Nom</th><th>Sujet</th><th>Message</th><th>Statut</th></tr>
<?php foreach($messages as $m): ?><tr><td><?=date('d/m/Y H:i',strtotime($m['created_at']))?></td><td><?=htmlspecialchars($m['name'])?><small><?=htmlspecialchars($m['email']??'')?></small></td><td><?=htmlspecialchars($m['subject']??'')?></td><td><?=htmlspecialchars(mb_strimwidth($m['message'],0,100,'…'))?></td><td><form method="post" class="inline"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="action" value="contact_status"><input type="hidden" name="id" value="<?=$m['id']?>"><select name="status" onchange="this.form.submit()"><?php foreach(['Nouveau','Lu','Traité'] as $st): ?><option <?=$m['status']===$st?'selected':''?>><?=$st?></option><?php endforeach;?></select></form></td></tr><?php endforeach;?></table></div></section>
</main></body></html>