-- Création de la base de données
DROP TABLE IF EXISTS HORRAIRE, EXCEPTION, LANGUEPRINCIPALE, ARRET_DESSERVI, TRAJET, SERVICE,  ARRET, ITINERAIRE, AGENCE;

CREATE TABLE AGENCE (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    NOM VARCHAR(255) NOT NULL,
    URL VARCHAR(255) NOT NULL,
    FUSEAU_HORAIRE VARCHAR(64) NOT NULL,
    TELEPHONE VARCHAR(32) NOT NULL,
    SIEGE TEXT NOT NULL,
    UNIQUE(NOM)
);

CREATE TABLE ARRET(
    ID INT PRIMARY KEY,
    NOM VARCHAR(255) NOT NULL,
    LATITUDE DECIMAL(11, 8) NOT NULL,
    LONGITUDE DECIMAL (11, 8) NOT NULL,
    CONSTRAINT CHK_ARRET CHECK (LATITUDE BETWEEN -90 AND 90),
    CONSTRAINT CHK_ARRET2 CHECK (LONGITUDE BETWEEN -180 AND 180)
);

CREATE TABLE ITINERAIRE (
    ID INT PRIMARY KEY,
    AGENCE_ID INT NOT NULL,
    TYPE VARCHAR(10) NOT NULL,
    NOM VARCHAR(255) NOT NULL,
    FOREIGN KEY (AGENCE_ID) REFERENCES AGENCE(ID),
    CONSTRAINT UC_ITI UNIQUE KEY(NOM, TYPE)
);

CREATE TABLE ARRET_DESSERVI (
    ITINERAIRE_ID INT NOT NULL,
    ARRET_ID INT NOT NULL,
    SEQUENCE INT NOT NULL,
    PRIMARY KEY (ITINERAIRE_ID, ARRET_ID),
    FOREIGN KEY (ITINERAIRE_ID) REFERENCES ITINERAIRE(ID),
    FOREIGN KEY (ARRET_ID) REFERENCES ARRET(ID)
);

CREATE TABLE SERVICE (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    NOM VARCHAR(100) UNIQUE NOT NULL,
    LUNDI BOOLEAN DEFAULT FALSE,
    MARDI BOOLEAN DEFAULT FALSE,
    MERCREDI BOOLEAN DEFAULT FALSE,
    JEUDI BOOLEAN DEFAULT FALSE,
    VENDREDI BOOLEAN DEFAULT FALSE,
    SAMEDI BOOLEAN DEFAULT FALSE,
    DIMANCHE BOOLEAN DEFAULT FALSE,
    DATE_DEBUT DATE NOT NULL,
    DATE_FIN DATE,
    CONSTRAINT SERVICE_DATE CHECK (DATE_FIN IS NULL OR DATE_DEBUT <= DATE_FIN)
);

CREATE TABLE TRAJET (
    ID VARCHAR(100) NOT NULL PRIMARY KEY,
    SERVICE_ID INT NOT NULL,
    ITINERAIRE_ID INT NOT NULL,
    DIRECTION TINYINT NOT NULL CHECK (DIRECTION IN (0, 1)),
    FOREIGN KEY (SERVICE_ID) REFERENCES SERVICE(ID),
    FOREIGN KEY (ITINERAIRE_ID) REFERENCES ITINERAIRE(ID)
);

CREATE TABLE HORRAIRE (
    TRAJET_ID VARCHAR(100) NOT NULL,
    ITINERAIRE_ID INT NOT NULL,
    ARRET_ID INT NOT NULL,
    HEURE_ARRIVEE TIME,
    HEURE_DEPART TIME,
    PRIMARY KEY (TRAJET_ID, ITINERAIRE_ID, ARRET_ID),
    FOREIGN KEY (TRAJET_ID) REFERENCES TRAJET(ID),
    FOREIGN KEY (ITINERAIRE_ID) REFERENCES ITINERAIRE(ID),
    FOREIGN KEY (ARRET_ID) REFERENCES ARRET(ID),
    CONSTRAINT HORRAIRE_HEURE CHECK (HEURE_ARRIVEE <= HEURE_DEPART)
);

CREATE TABLE EXCEPTION (
    SERVICE_ID INT NOT NULL,
    DATE DATE NOT NULL,
    CODE TINYINT NOT NULL CHECK (CODE IN (1, 2)), -- 1 = ajout et 2 = supprime
    PRIMARY KEY (SERVICE_ID, DATE),
    FOREIGN KEY (SERVICE_ID) REFERENCES SERVICE(ID)
);

CREATE TABLE LANGUEPRINCIPALE (
    AGENCE_ID INT NOT NULL,
    LANGUE CHAR(2) NOT NULL, -- Code ISO 639-1 à 2 lettres
    PRIMARY KEY (AGENCE_ID, LANGUE),
    FOREIGN KEY (AGENCE_ID) REFERENCES AGENCE(ID)
);



-- Vue pour calcul temps d'arrêt moyen/trajet & itineraire (pour la question 5)
DROP VIEW IF EXISTS TEMPS_ARRET_MOYEN;
CREATE VIEW TEMPS_ARRET_MOYEN AS
SELECT 
    t.ITINERAIRE_ID,
    i.NOM AS NOM_ITINERAIRE,
    t.ID AS TRAJET_ID,
    AVG(TIMESTAMPDIFF(MINUTE, h.HEURE_ARRIVEE, h.HEURE_DEPART)) AS TEMPS_ARRET_MOYEN
FROM 
    TRAJET t
JOIN 
    HORRAIRE h ON t.ID = h.TRAJET_ID
JOIN 
    ITINERAIRE i ON t.ITINERAIRE_ID = i.ID
WHERE 
    h.HEURE_ARRIVEE IS NOT NULL AND h.HEURE_DEPART IS NOT NULL
GROUP BY 
    t.ITINERAIRE_ID, t.ID;

-- Vue pour les services actifs (pour afficher les services disponibles -> question 4)
-- création de la vue 

drop view if exists all_jours_services; 
create view all_jours_services as
WITH RECURSIVE dates as( 
    SELECT id,nom,date_debut,date_fin 
    FROM SERVICE 
    UNION ALL 
    SELECT id,nom,date_add(date_debut,INTERVAL 1 DAY),date_fin 
    FROM dates WHERE date_add(date_debut, INTERVAL 1 DAY) <= date_fin 
) 
select id,nom,date_debut as jour from dates; 

-- seconde vue pour la Q4 
drop view if exists dates_services; 
create view dates_services as 
select ajs.id, ajs.nom, ajs.jour
from all_jours_services as ajs
join service as s on s.id = ajs.id
where ((weekday(ajs.jour) = 0 and s.LUNDI = 1) OR
       (weekday(ajs.jour) = 1 and s.MARDI = 1) OR 
       (weekday(ajs.jour) = 2 and s.MERCREDI = 1) OR 
       (weekday(ajs.jour) = 3 and s.JEUDI = 1) OR 
       (weekday(ajs.jour) = 4 and s.VENDREDI = 1) OR 
       (weekday(ajs.jour) = 5 and s.SAMEDI = 1) OR 
       (weekday(ajs.jour) = 6 and s.DIMANCHE = 1))
and not exists(
    select 1 
    from exception as e 
    where e.code = 2 and ajs.jour = e.date)
union 
select e.service_id, s.nom, e.date 
from exception as e
join service as s on e.SERVICE_ID = s.ID 
where e.CODE = 1;

-- Vue pour les arrêts avec nombre de trajets (pour la recherche de gares -> question 6)
DROP VIEW IF EXISTS ARRETS_AVEC_TRAFIC;
CREATE VIEW ARRETS_AVEC_TRAFIC AS
SELECT 
    a.ID,
    a.NOM,
    COUNT(DISTINCT h.TRAJET_ID) AS NB_TRAJETS,
    SUM(CASE WHEN h.HEURE_ARRIVEE IS NOT NULL THEN 1 ELSE 0 END) AS NB_ARRIVEES,
    SUM(CASE WHEN h.HEURE_DEPART IS NOT NULL THEN 1 ELSE 0 END) AS NB_DEPARTS
FROM 
    ARRET a
LEFT JOIN 
    HORRAIRE h ON a.ID = h.ARRET_ID
GROUP BY 
    a.ID, a.NOM;


-- 2. Chargement des données depuis les CSV
LOAD DATA INFILE 'C:/csv_files/AGENCE.csv'
IGNORE INTO TABLE AGENCE
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(ID,NOM, URL, FUSEAU_HORAIRE, TELEPHONE, SIEGE);

LOAD DATA INFILE 'C:/csv_files/ARRET.csv'
IGNORE INTO TABLE ARRET
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"'
ESCAPED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(ID, NOM, LATITUDE, LONGITUDE);

LOAD DATA INFILE 'C:/csv_files/ITINERAIRE.csv'
IGNORE INTO TABLE ITINERAIRE
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"'
ESCAPED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(ID,AGENCE_ID,TYPE,NOM);

LOAD DATA INFILE 'C:/csv_files/ARRET_DESSERVI.csv'
IGNORE INTO TABLE ARRET_DESSERVI
FIELDS TERMINATED BY ','
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(ITINERAIRE_ID,ARRET_ID,SEQUENCE);

LOAD DATA INFILE 'C:/csv_files/SERVICE.csv'
IGNORE INTO TABLE SERVICE
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
ESCAPED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(ID,NOM, LUNDI, MARDI, MERCREDI, JEUDI, VENDREDI, SAMEDI, DIMANCHE, DATE_DEBUT, DATE_FIN);

LOAD DATA INFILE 'C:/csv_files/TRAJET.csv'
IGNORE INTO TABLE TRAJET
FIELDS TERMINATED BY ',' 
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(ID,SERVICE_ID, ITINERAIRE_ID, DIRECTION);

LOAD DATA INFILE 'C:/csv_files/HORRAIRE.csv'
IGNORE INTO TABLE HORRAIRE
FIELDS TERMINATED BY ',' 
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(TRAJET_ID, ITINERAIRE_ID, ARRET_ID,  @HEURE_ARRIVEE, @HEURE_DEPART)
SET 
    HEURE_ARRIVEE = NULLIF(@HEURE_ARRIVEE, ''),
    HEURE_DEPART = NULLIF(@HEURE_DEPART, '');

LOAD DATA INFILE 'C:/csv_files/EXCEPTION.csv'
IGNORE INTO TABLE EXCEPTION
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(SERVICE_ID, DATE, CODE);

LOAD DATA INFILE 'C:/csv_files/LANGUEPRINCIPALE.csv'
IGNORE INTO TABLE LANGUEPRINCIPALE
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(AGENCE_ID, LANGUE);
