-- LessGo Community - PostgreSQL schema for Render
-- Safe to run repeatedly. Do NOT create a database here; Render creates it.

CREATE TABLE IF NOT EXISTS admins (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
  id BIGSERIAL PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS formations (
  id BIGSERIAL PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  short_description TEXT,
  description TEXT,
  duration VARCHAR(80),
  level VARCHAR(80),
  skills TEXT,
  outcomes TEXT,
  image VARCHAR(255),
  featured SMALLINT NOT NULL DEFAULT 0,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS gallery (
  id BIGSERIAL PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  image VARCHAR(255) NOT NULL,
  category VARCHAR(80) NOT NULL DEFAULT 'Centre',
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS testimonials (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  role VARCHAR(120),
  message TEXT NOT NULL,
  rating SMALLINT NOT NULL DEFAULT 5 CHECK (rating BETWEEN 1 AND 5),
  photo VARCHAR(255),
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS partners (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  logo VARCHAR(255),
  website VARCHAR(255),
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS registrations (
  id BIGSERIAL PRIMARY KEY,
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
  status VARCHAR(30) NOT NULL DEFAULT 'Nouveau' CHECK (status IN ('Nouveau','Contacté','Confirmé','Archivé')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contacts (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190),
  phone VARCHAR(40),
  subject VARCHAR(190),
  message TEXT NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'Nouveau' CHECK (status IN ('Nouveau','Lu','Traité')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admins (name,email,password_hash) VALUES
('Administrateur','admin@lessgo.cm','$2y$12$roICtKWgkGH0LDNb.MGi7uxohugdXQj/RjZZsHGCeeTEvZ.poEc5O')
ON CONFLICT (email) DO NOTHING;

INSERT INTO settings(setting_key,setting_value) VALUES
('site_name','LessGo Community'),('tagline','Développez • Créez • Impactez'),('phone','+237 656 84 48 82'),('whatsapp','237656844882'),('address','Douala – Bonabéri, derrière la station Bocom de Mambanda'),('email','contact@lessgo.cm'),('hero_title','Transformez votre potentiel en compétences qui comptent.'),('hero_text','Formations pratiques en développement, DevOps, Cloud, IA et création numérique.')
ON CONFLICT (setting_key) DO UPDATE SET setting_value=EXCLUDED.setting_value, updated_at=CURRENT_TIMESTAMP;

INSERT INTO formations(title,slug,short_description,description,duration,level,skills,outcomes,image,featured,sort_order) VALUES
('Développement Web','developpement-web','Construisez des sites et applications modernes.','HTML, CSS, JavaScript, Bootstrap, PHP, MySQL, Git et mise en ligne.','Selon programme','Débutant à intermédiaire','HTML, CSS, JavaScript, Bootstrap, PHP, MySQL, Git','Développeur web junior, freelance, intégrateur','flyer.png',1,1),
('DevOps','devops','Automatisez et industrialisez vos projets.','Linux, Git, Docker, CI/CD, conteneurs, bases Kubernetes et pratiques DevOps.','Selon programme','Intermédiaire','Linux, Git, Docker, CI/CD, Kubernetes','DevOps junior, cloud/automation assistant','flyer.png',1,2),
('AWS Cloud','aws-cloud','Maîtrisez les fondamentaux du Cloud AWS.','Découverte des services AWS et des architectures Cloud modernes.','Selon programme','Débutant à intermédiaire','EC2, S3, IAM, RDS, Cloud fundamentals','Cloud junior, support Cloud','flyer.png',1,3),
('Intelligence Artificielle','intelligence-artificielle','Découvrez l’IA et ses usages concrets.','Python, données, machine learning et découverte des outils d’IA.','Selon programme','Intermédiaire','Python, data, machine learning, IA générative','Assistant data/IA, créateur de solutions IA','flyer.png',1,4),
('Community Management','community-management','Développez une présence digitale efficace.','Stratégie éditoriale, publication, engagement, reporting et animation de communautés.','Selon programme','Débutant','Stratégie social media, contenu, reporting','Community manager, social media assistant','flyer.png',1,5),
('Création de contenu','creation-contenu','Créez des visuels et vidéos attractifs.','Conception de visuels, vidéos courtes, storytelling et outils créatifs.','Selon programme','Débutant','Canva, montage, storytelling, design','Créateur de contenu, assistant communication','flyer.png',1,6),
('Bureautique','bureautique','Maîtrisez les outils numériques essentiels.','Word, Excel, PowerPoint, Internet et bonnes pratiques numériques.','Selon programme','Tous niveaux','Word, Excel, PowerPoint, Internet','Assistant administratif, productivité numérique','flyer.png',0,7)
ON CONFLICT (slug) DO NOTHING;

INSERT INTO testimonials(name,role,message,rating)
SELECT 'Étudiant LessGo','Apprenant','Une approche pratique et motivante pour progresser rapidement.',5
WHERE NOT EXISTS (SELECT 1 FROM testimonials WHERE name='Étudiant LessGo' AND message='Une approche pratique et motivante pour progresser rapidement.');
INSERT INTO testimonials(name,role,message,rating)
SELECT 'Participant','Ancien apprenant','J’ai apprécié les ateliers et l’accompagnement pendant la formation.',5
WHERE NOT EXISTS (SELECT 1 FROM testimonials WHERE name='Participant' AND message='J’ai apprécié les ateliers et l’accompagnement pendant la formation.');
INSERT INTO testimonials(name,role,message,rating)
SELECT 'Parent d’apprenant','Parent','Un cadre sérieux pour développer les compétences numériques.',5
WHERE NOT EXISTS (SELECT 1 FROM testimonials WHERE name='Parent d’apprenant' AND message='Un cadre sérieux pour développer les compétences numériques.');
