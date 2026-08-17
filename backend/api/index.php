<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$pdo = db();

$resource = $_GET['resource'] ?? 'home';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    switch ($resource) {

        case 'home':
            $settings = $pdo->query(
                "SELECT setting_key, setting_value FROM settings"
            )->fetchAll();

            $s = [];
            foreach ($settings as $row) {
                $s[$row['setting_key']] = $row['setting_value'];
            }

            $formations = $pdo->query(
                "SELECT * FROM formations
                 ORDER BY featured DESC, sort_order ASC, id DESC"
            )->fetchAll();

            $testimonials = $pdo->query(
                "SELECT * FROM testimonials
                 ORDER BY id DESC LIMIT 12"
            )->fetchAll();

            $gallery = $pdo->query(
                "SELECT * FROM gallery
                 ORDER BY id DESC LIMIT 12"
            )->fetchAll();

            json_response([
                's' => $s,
                'formations' => $formations,
                'testimonials' => $testimonials,
                'gallery' => $gallery
            ]);
            break;


        case 'formations':
            $slug = trim((string)($_GET['slug'] ?? ''));

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    "SELECT * FROM formations WHERE id = ? LIMIT 1"
                );
                $stmt->execute([$id]);
                $formation = $stmt->fetch();

                if (!$formation) {
                    json_response([
                        'error' => 'Formation introuvable.'
                    ], 404);
                }

                json_response($formation);
            }

            if ($slug !== '') {
                $stmt = $pdo->prepare(
                    "SELECT * FROM formations WHERE slug = ? LIMIT 1"
                );
                $stmt->execute([$slug]);
                $formation = $stmt->fetch();

                if (!$formation) {
                    json_response([
                        'error' => 'Formation introuvable.'
                    ], 404);
                }

                json_response($formation);
            }

            json_response(
                $pdo->query(
                    "SELECT * FROM formations
                     ORDER BY featured DESC, sort_order ASC, id DESC"
                )->fetchAll()
            );
            break;


        case 'gallery':
            json_response(
                $pdo->query(
                    "SELECT * FROM gallery ORDER BY id DESC"
                )->fetchAll()
            );
            break;


        case 'testimonials':
            json_response(
                $pdo->query(
                    "SELECT * FROM testimonials ORDER BY id DESC"
                )->fetchAll()
            );
            break;


        case 'settings':
            $rows = $pdo->query(
                "SELECT setting_key, setting_value FROM settings"
            )->fetchAll();

            $out = [];

            foreach ($rows as $row) {
                $out[$row['setting_key']] = $row['setting_value'];
            }

            json_response($out);
            break;


        case 'register':

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_response([
                    'error' => 'Méthode non autorisée.'
                ], 405);
            }

            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!is_array($data)) {
                $data = $_POST;
            }

            $firstName = trim((string)($data['first_name'] ?? ''));
            $lastName = trim((string)($data['last_name'] ?? ''));
            $phone = trim((string)($data['phone'] ?? ''));
            $email = trim((string)($data['email'] ?? ''));
            $gender = trim((string)($data['gender'] ?? ''));
            $birthDate = $data['birth_date'] ?? null;
            $address = trim((string)($data['address'] ?? ''));
            $level = trim((string)($data['level'] ?? ''));
            $formation = trim((string)($data['formation'] ?? ''));
            $startDate = $data['start_date'] ?? null;
            $message = trim((string)($data['message'] ?? ''));

            if ($firstName === '') {
                json_response(['error' => 'Le prénom est obligatoire.'], 422);
            }

            if ($lastName === '') {
                json_response(['error' => 'Le nom est obligatoire.'], 422);
            }

            if ($phone === '') {
                json_response(['error' => 'Le téléphone est obligatoire.'], 422);
            }

            if ($formation === '') {
                json_response([
                    'error' => 'La formation est obligatoire.'
                ], 422);
            }

            if (
                $email !== '' &&
                !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {
                json_response([
                    'error' => 'Adresse email invalide.'
                ], 422);
            }

            /*
             * 1. ENREGISTREMENT DANS POSTGRESQL
             */
            $stmt = $pdo->prepare(
                "INSERT INTO registrations
                (
                    first_name,
                    last_name,
                    phone,
                    email,
                    gender,
                    birth_date,
                    address,
                    level,
                    formation,
                    start_date,
                    message
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                RETURNING id"
            );

            $stmt->execute([
                $firstName,
                $lastName,
                $phone,
                $email !== '' ? $email : null,
                $gender !== '' ? $gender : null,
                $birthDate ?: null,
                $address !== '' ? $address : null,
                $level !== '' ? $level : null,
                $formation,
                $startDate ?: null,
                $message !== '' ? $message : null
            ]);

            $registrationId = (int)$stmt->fetchColumn();

            /*
             * 2. EMAIL
             */
            $smtpHost = trim((string)getenv('SMTP_HOST'));
            $smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
            $smtpUser = trim((string)getenv('SMTP_USERNAME'));
            $smtpPassword = (string)getenv('SMTP_PASSWORD');
            $smtpFrom = trim(
                (string)(getenv('SMTP_FROM') ?: $smtpUser)
            );

            if (
                $smtpHost === '' ||
                $smtpUser === '' ||
                $smtpPassword === '' ||
                $smtpFrom === ''
            ) {
                error_log('SMTP incomplet dans Render.');

                json_response([
                    'success' => true,
                    'message' =>
                        'Inscription enregistrée. Configuration email à terminer.',
                    'registration_id' => $registrationId
                ], 202);
            }

            require_once __DIR__ .
                '/../lib/PHPMailer/PHPMailer-7.1.1/src/Exception.php';

            require_once __DIR__ .
                '/../lib/PHPMailer/PHPMailer-7.1.1/src/PHPMailer.php';

            require_once __DIR__ .
                '/../lib/PHPMailer/PHPMailer-7.1.1/src/SMTP.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPassword;
            $mail->Port = $smtpPort;
            $mail->SMTPSecure =
                PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

            $mail->CharSet = 'UTF-8';

            $mail->setFrom(
                $smtpFrom,
                'LessGo Community'
            );

            /*
             * LES DEUX DESTINATAIRES
             */
            $mail->addAddress('lessgooo.ai26@gmail.com');
            $mail->addAddress('angelyslonne@gmail.com');

            if ($email !== '') {
                $mail->addReplyTo($email);
            }

            $mail->isHTML(true);

            $mail->Subject =
                'Nouvelle inscription - LessGo Community';

            $mail->Body = '
                <h2>Nouvelle inscription LessGo Community</h2>

                <p>
                    <strong>ID :</strong>
                    ' . htmlspecialchars((string)$registrationId) . '
                </p>

                <p>
                    <strong>Prénom :</strong>
                    ' . htmlspecialchars($firstName) . '
                </p>

                <p>
                    <strong>Nom :</strong>
                    ' . htmlspecialchars($lastName) . '
                </p>

                <p>
                    <strong>Téléphone :</strong>
                    ' . htmlspecialchars($phone) . '
                </p>

                <p>
                    <strong>Email :</strong>
                    ' . htmlspecialchars($email) . '
                </p>

                <p>
                    <strong>Formation :</strong>
                    ' . htmlspecialchars($formation) . '
                </p>

                <p>
                    <strong>Niveau :</strong>
                    ' . htmlspecialchars($level) . '
                </p>

                <p>
                    <strong>Adresse :</strong>
                    ' . htmlspecialchars($address) . '
                </p>

                <p>
                    <strong>Date souhaitée :</strong>
                    ' . htmlspecialchars((string)$startDate) . '
                </p>

                <h3>Message</h3>

                <p>
                    ' . nl2br(htmlspecialchars($message)) . '
                </p>
            ';

            $mail->AltBody =
                "Nouvelle inscription LessGo Community\n\n" .
                "ID : {$registrationId}\n" .
                "Prénom : {$firstName}\n" .
                "Nom : {$lastName}\n" .
                "Téléphone : {$phone}\n" .
                "Email : {$email}\n" .
                "Formation : {$formation}\n" .
                "Niveau : {$level}\n" .
                "Adresse : {$address}\n" .
                "Date souhaitée : {$startDate}\n\n" .
                "Message :\n{$message}";

            try {

                $mail->send();

                json_response([
                    'success' => true,
                    'message' =>
                        'Inscription envoyée avec succès.',
                    'registration_id' => $registrationId
                ]);

            } catch (Throwable $mailError) {

                error_log(
                    'EMAIL INSCRIPTION : ' .
                    $mailError->getMessage()
                );

                /*
                 * L'inscription reste enregistrée
                 * même si l'email échoue.
                 */
                json_response([
                    'success' => true,
                    'message' =>
                        'Inscription enregistrée, mais notification email impossible.',
                    'registration_id' => $registrationId
                ], 202);
            }

            break;


        case 'contact':

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_response([
                    'error' => 'Méthode non autorisée.'
                ], 405);
            }

            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!is_array($data)) {
                $data = $_POST;
            }

            $name = trim((string)($data['name'] ?? ''));
            $email = trim((string)($data['email'] ?? ''));
            $phone = trim((string)($data['phone'] ?? ''));
            $subject = trim((string)($data['subject'] ?? ''));
            $message = trim((string)($data['message'] ?? ''));

            if ($name === '' || $message === '') {
                json_response([
                    'error' => 'Nom et message obligatoires.'
                ], 422);
            }

            $stmt = $pdo->prepare(
                "INSERT INTO contacts
                (name, email, phone, subject, message)
                VALUES (?, ?, ?, ?, ?)
                RETURNING id"
            );

            $stmt->execute([
                $name,
                $email !== '' ? $email : null,
                $phone !== '' ? $phone : null,
                $subject !== '' ? $subject : null,
                $message
            ]);

            $contactId = (int)$stmt->fetchColumn();

            json_response([
                'success' => true,
                'message' => 'Message enregistré.',
                'contact_id' => $contactId
            ]);

            break;


        default:

            json_response([
                'error' => 'Ressource inconnue.'
            ], 404);
    }

} catch (Throwable $e) {

    error_log(
        'LESSGO API ERROR: ' .
        $e->getMessage()
    );

    json_response([
        'error' => 'Erreur serveur.',
        'details' => $e->getMessage()
    ], 500);
}

