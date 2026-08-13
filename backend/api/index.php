<?php
// backend/api/index.php
declare(strict_types=1);
require_once __DIR__.'/../config/config.php';

$pdo = db();
$resource = $_GET['resource'] ?? 'home';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    switch ($resource) {
        case 'home':
            $settings = $pdo->query("SELECT setting_key,setting_value FROM settings")->fetchAll();
            $s = [];
            foreach ($settings as $row) $s[$row['setting_key']] = $row['setting_value'];
            $formations = $pdo->query("SELECT * FROM formations ORDER BY featured DESC, sort_order ASC, id DESC")->fetchAll();
            $testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY id DESC LIMIT 12")->fetchAll();
            $gallery = $pdo->query("SELECT * FROM gallery ORDER BY id DESC LIMIT 12")->fetchAll();
            json_response(compact('s','formations','testimonials','gallery'));
        case 'formations':
            $slug = trim((string)($_GET['slug'] ?? ''));
            if ($id || $slug !== '') {
                if ($id) {
                    $st=$pdo->prepare("SELECT * FROM formations WHERE id=? LIMIT 1");
                    $st->execute([$id]);
                } else {
                    $st=$pdo->prepare("SELECT * FROM formations WHERE slug=? LIMIT 1");
                    $st->execute([$slug]);
                }
                $row=$st->fetch();
                if (!$row) json_response(['error'=>'Formation introuvable'],404);
                json_response($row);
            }
            json_response($pdo->query("SELECT * FROM formations ORDER BY featured DESC, sort_order ASC, id DESC")->fetchAll());
        case 'gallery':
            json_response($pdo->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll());
        case 'testimonials':
            json_response($pdo->query("SELECT * FROM testimonials ORDER BY id DESC")->fetchAll());
        case 'settings':
            $rows=$pdo->query("SELECT setting_key,setting_value FROM settings")->fetchAll();
            $out=[]; foreach($rows as $r)$out[$r['setting_key']]=$r['setting_value'];
            json_response($out);
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error'=>'Méthode non autorisée'],405);
            $data=json_decode(file_get_contents('php://input'),true) ?: $_POST;
            $required=['first_name','last_name','phone','formation'];
            foreach($required as $k) if(empty(trim((string)($data[$k]??''))) ) json_response(['error'=>"Champ obligatoire: $k"],422);
            $st=$pdo->prepare("INSERT INTO registrations(first_name,last_name,phone,email,gender,birth_date,address,level,formation,start_date,message) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
            $st->execute([
                trim($data['first_name']),trim($data['last_name']),trim($data['phone']),
                trim($data['email']??''),trim($data['gender']??''),$data['birth_date']??null,
                trim($data['address']??''),trim($data['level']??''),trim($data['formation']),
                $data['start_date']??null,trim($data['message']??'')
            ]);
            json_response(['success'=>true,'message'=>'Inscription enregistrée.']);
        case 'contact':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error'=>'Méthode non autorisée'],405);
            $data=json_decode(file_get_contents('php://input'),true) ?: $_POST;
            if(empty(trim((string)($data['name']??''))) || empty(trim((string)($data['message']??'')))) json_response(['error'=>'Nom et message obligatoires.'],422);
            $st=$pdo->prepare("INSERT INTO contacts(name,email,phone,subject,message) VALUES(?,?,?,?,?)");
            $st->execute([trim($data['name']),trim($data['email']??''),trim($data['phone']??''),trim($data['subject']??''),trim($data['message'])]);
            json_response(['success'=>true,'message'=>'Message envoyé.']);
        default:
            json_response(['error'=>'Ressource inconnue'],404);
    }
} catch(Throwable $e) {
    json_response(['error'=>'Erreur serveur','details'=>$e->getMessage()],500);
}
