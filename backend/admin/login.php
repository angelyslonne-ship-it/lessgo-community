<?php
session_start();
require_once __DIR__.'/../config/config.php';
if (isset($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $email=trim($_POST['email']??'');
    $password=$_POST['password']??'';
    $st=db()->prepare("SELECT * FROM admins WHERE email=? LIMIT 1");
    $st->execute([$email]); $admin=$st->fetch();
    if ($admin && password_verify($password,$admin['password_hash'])) {
        $_SESSION['admin_id']=$admin['id'];
        $_SESSION['admin_name']=$admin['name'];
        header('Location: dashboard.php'); exit;
    }
    $error='Identifiants incorrects.';
}
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Connexion — LessGo Admin</title><link rel="stylesheet" href="assets/css/login.css"></head>
<body class="login-page">
<div class="login-card"><img src="../../frontend/assets/images/logo.png" alt="LessGo Community"><h1>Administration</h1><p>Connectez-vous pour gérer le site.</p>
<?php if($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post"><label>Email<input type="email" name="email" required value="admin@lessgo.cm"></label>
<label>Mot de passe<input type="password" name="password" required></label><button>Se connecter</button></form>
