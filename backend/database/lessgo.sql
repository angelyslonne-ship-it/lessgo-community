CREATE DATABASE IF NOT EXISTS lessgo_community CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lessgo_community;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS formations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  short_description TEXT,
  description LONGTEXT,
  duration VARCHAR(80),
  level VARCHAR(80),
  skills TEXT,
  outcomes TEXT,
  image VARCHAR(255),
  featured TINYINT(1) DEFAULT 0,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS gallery (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  image VARCHAR(255) NOT NULL,
  category VARCHAR(80) DEFAULT 'Centre',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS testimonials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  role VARCHAR(120),
  message TEXT NOT NULL,
  rating TINYINT DEFAULT 5,
  photo VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS partners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  logo VARCHAR(255),
  website VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(190),
  gender VARCHAR(30),
  birth_date DATE NULL,
  address VARCHAR(255),
  level VARCHAR(100),
  formation VARCHAR(180) NOT NULL,
  start_date DATE NULL,
  message TEXT,
  status ENUM('Nouveau','Contacté','Confirmé','Archivé') DEFAULT 'Nouveau',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190),
  phone VARCHAR(40),
  subject VARCHAR(190),
  message TEXT NOT NULL,
  status ENUM('Nouveau','Lu','Traité') DEFAULT 'Nouveau',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admins (name,email,password_hash)
SELECT 'Administrateur','admin@lessgo.cm','$2y$12$roICtKWgkGH0LDNb.MGi7uxohugdXQj/RjZZsHGCeeTEvZ.poEc5O'
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE email='admin@lessgo.cm');

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name','LessGo Community'),
('tagline','Développez • Créez • Impactez'),
('phone','+237 656 84 48 82'),
('whatsapp','237656844882'),
('address','Douala – Bonabéri, derrière la station Bocom de Mambanda'),
('email','contact@lessgo.cm'),
('hero_title','Transformez votre potentiel en compétences qui comptent.'),
('hero_text','Formations pratiques en développement, DevOps, Cloud, IA et création numérique.')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

INSERT INTO formations (title,slug,short_description,description,duration,level,skills,outcomes,image,featured,sort_order)
SELECT tmp.title,tmp.slug,tmp.short_description,tmp.description,tmp.duration,tmp.level,tmp.skills,tmp.outcomes,tmp.image,tmp.featured,tmp.sort_order
FROM (
  SELECT 'Développement Web' AS title,'developpement-web' AS slug,'Construisez des sites et applications modernes.' AS short_description,'HTML, CSS, JavaScript, Bootstrap, PHP, MySQL, Git et mise en ligne.' AS description,'Selon programme' AS duration,'Débutant à intermédiaire' AS level,'HTML, CSS, JavaScript, Bootstrap, PHP, MySQL, Git' AS skills,'Développeur web junior, freelance, intégrateur' AS outcomes,'flyer.png' AS image,1 AS featured,1 AS sort_order
  UNION ALL SELECT 'DevOps','devops','Automatisez et industrialisez vos projets.','Linux, Git, Docker, CI/CD, conteneurs, bases Kubernetes et pratiques DevOps.','Selon programme','Intermédiaire','Linux, Git, Docker, CI/CD, Kubernetes','DevOps junior, cloud/automation assistant','flyer.png',1,2
  UNION ALL SELECT 'AWS Cloud','aws-cloud','Maîtrisez les fondamentaux du Cloud AWS.','Découverte des services AWS et des architectures Cloud modernes.','Selon programme','Débutant à intermédiaire','EC2, S3, IAM, RDS, Cloud fundamentals','Cloud junior, support Cloud','flyer.png',1,3
  UNION ALL SELECT 'Intelligence Artificielle','intelligence-artificielle','Découvrez l’IA et ses usages concrets.','Python, données, machine learning et découverte des outils d’IA.','Selon programme','Intermédiaire','Python, data, machine learning, IA générative','Assistant data/IA, créateur de solutions IA','flyer.png',1,4
  UNION ALL SELECT 'Community Management','community-management','Développez une présence digitale efficace.','Stratégie éditoriale, publication, engagement, reporting et animation de communautés.','Selon programme','Débutant','Stratégie social media, contenu, reporting','Community manager, social media assistant','flyer.png',1,5
  UNION ALL SELECT 'Création de contenu','creation-contenu','Créez des visuels et vidéos attractifs.','Conception de visuels, vidéos courtes, storytelling et outils créatifs.','Selon programme','Débutant','Canva, montage, storytelling, design','Créateur de contenu, assistant communication','flyer.png',1,6
  UNION ALL SELECT 'Bureautique','bureautique','Maîtrisez les outils numériques essentiels.','Word, Excel, PowerPoint, Internet et bonnes pratiques numériques.','Selon programme','Tous niveaux','Word, Excel, PowerPoint, Internet','Assistant administratif, productivité numérique','flyer.png',0,7
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM formations LIMIT 1);

INSERT INTO testimonials (name,role,message,rating)
SELECT t.name,t.role,t.message,t.rating
FROM (
  SELECT 'Étudiant LessGo' AS name,'Apprenant' AS role,'Une approche pratique et motivante pour progresser rapidement.' AS message,5 AS rating
  UNION ALL SELECT 'Participant','Ancien apprenant','J’ai apprécié les ateliers et l’accompagnement pendant la formation.',5
  UNION ALL SELECT 'Parent d’apprenant','Parent','Un cadre sérieux pour développer les compétences numériques.',5
) AS t
WHERE NOT EXISTS (SELECT 1 FROM testimonials LIMIT 1);
