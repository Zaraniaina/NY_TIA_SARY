DROP DATABASE IF EXISTS ny_tia_sary_db;

CREATE DATABASE IF NOT EXISTS ny_tia_sary_db;
USE ny_tia_sary_db;
# -----------------------------------------------------------------------------
#       TABLE : AUTHENTIFICATION
# -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS AUTHENTIFICATION
 (
   ID_AUTH BIGINT(4) NOT NULL  AUTO_INCREMENT,
   EMAIL_AUTH VARCHAR(255) NOT NULL UNIQUE ,
   MDP_AUTH VARCHAR(128) NOT NULL  ,
   ROLE_AUTH ENUM('ADMIN','CLIENT') NOT NULL DEFAULT 'CLIENT'
   , PRIMARY KEY (ID_AUTH) 
 ) 
 comment = "";

# -----------------------------------------------------------------------------
#       TABLE : FACTURE
# -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS FACTURE
 (
   ID_FACTURE BIGINT(4) NOT NULL  AUTO_INCREMENT,
   ID_CONTRAT BIGINT(4) NOT NULL  ,
   NUM_FACTURE VARCHAR(128) NOT NULL  ,
   DATE_FACTURE DATE NOT NULL  DEFAULT CURRENT_DATE
   , PRIMARY KEY (ID_FACTURE) 
 ) 
 comment = "";

# -----------------------------------------------------------------------------
#       INDEX DE LA TABLE FACTURE
# -----------------------------------------------------------------------------


CREATE UNIQUE INDEX I_FK_FACTURE_CONTRAT
     ON FACTURE (ID_CONTRAT ASC);

# -----------------------------------------------------------------------------
#       TABLE : SECUTITE
# -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS SECUTITE
 (
   ID_SECURITE BIGINT(4) NOT NULL  AUTO_INCREMENT,
   ID_AUTH BIGINT(4) NOT NULL  ,
   QUESTION VARCHAR(128) NOT NULL  ,
   REPONSE VARCHAR(128) NOT NULL  
   , PRIMARY KEY (ID_SECURITE) 
 ) 
 comment = "";

# -----------------------------------------------------------------------------
#       INDEX DE LA TABLE SECUTITE
# -----------------------------------------------------------------------------


CREATE  INDEX I_FK_SECUTITE_AUTHENTIFICATION
     ON SECUTITE (ID_AUTH ASC);

# -----------------------------------------------------------------------------
#       TABLE : DEVIS
# -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS DEVIS
 (
   ID_DEVIS BIGINT(4) NOT NULL  AUTO_INCREMENT,
   ID_PRESTATION BIGINT(4) NOT NULL  ,
   NOM VARCHAR(255) NOT NULL  ,
   PRENOMS VARCHAR(255) NOT NULL  ,
   TELEPHONE VARCHAR(128) NOT NULL  ,
   ENTREPRISE VARCHAR(128) NOT NULL  ,
   BUGET_ESTIMATIF VARCHAR(128) NOT NULL  ,
   DATE_SOUHAITE DATE NOT NULL  ,
   DESCRIPTION VARCHAR(255) NOT NULL  
   , PRIMARY KEY (ID_DEVIS) 
 ) 
 comment = "";

# -----------------------------------------------------------------------------
#       INDEX DE LA TABLE DEVIS
# -----------------------------------------------------------------------------


CREATE  INDEX I_FK_DEVIS_PRESTATIONS
     ON DEVIS (ID_PRESTATION ASC);

# -----------------------------------------------------------------------------
#       TABLE : TYPE_BLOG
# -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS TYPE_BLOG
 (
   ID_TYPE_BLOG BIGINT(4) NOT NULL  AUTO_INCREMENT,
   LIB_TYPE_BLOG VARCHAR(128) NOT NULL  
   , PRIMARY KEY (ID_TYPE_BLOG) 
 ) 
 comment = "";

# -----------------------------------------------------------------------------
#       TABLE : PIECES_JOINTES
# -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS PIECES_JOINTES
 (
   ID_PIECE BIGINT(4) NOT NULL  AUTO_INCREMENT,
   ID_DEVIS BIGINT(4) NOT NULL  ,
   PATH_PIECE VARCHAR(255) NOT NULL  
   , PRIMARY KEY (ID_PIECE) 
 ) 
 comment = "";

# -----------------------------------------------------------------------------
#       INDEX DE LA TABLE PIECES_JOINTES
# -----------------------------------------------------------------------------


CREATE  INDEX I_FK_PIECES_JOINTES_DEVIS
     ON PIECES_JOINTES (ID_DEVIS ASC);

# -----------------------------------------------------------------------------
#       TABLE : BLOG
# -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS BLOG
 (
   ID_BLOG BIGINT(4) NOT NULL  AUTO_INCREMENT,
   ID_TYPE_BLOG BIGINT(4) NOT NULL  ,
   TITRE_BLOG VARCHAR(128) NOT NULL  ,
   CONTENU VARCHAR(255) NOT NULL  ,
   IMAGE_COURVERTURE VARCHAR(255) NOT NULL  ,
   DATE_PUBLICATION DATE NOT NULL  DEFAULT CURRENT_DATE,
   DATE_MODIFICATION DATE NOT NULL DEFAULT CURRENT_DATE ,
   STATUS_BLOG VARCHAR(128) NOT NULL  
   , PRIMARY KEY (ID_BLOG) 
 ) 
 comment = "";

# -----------------------------------------------------------------------------
#       INDEX DE LA TABLE BLOG
# -----------------------------------------------------------------------------


CREATE  INDEX I_FK_BLOG_TYPE_BLOG
     ON BLOG (ID_TYPE_BLOG ASC);

# -----------------------------------------------------------------------------
#       TABLE : CONTRAT
# -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS CONTRAT
 (
   ID_CONTRAT BIGINT(4) NOT NULL AUTO_INCREMENT ,
   ID_RESERVATION BIGINT(4) NOT NULL  ,
   DATE_CONTRAT DATE NOT NULL  
   , PRIMARY KEY (ID_CONTRAT) 
 ) 
 comment = "";

# -----------------------------------------------------------------------------
#       INDEX DE LA TABLE CONTRAT
# -----------------------------------------------------------------------------


CREATE UNIQUE INDEX I_FK_CONTRAT_RESERVATION
     ON CONTRAT (ID_RESERVATION ASC);

# -----------------------------------------------------------------------------
#       TABLE : CLIENT
# -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS CLIENT
 (
   ID_CLIENT BIGINT(4) NOT NULL  AUTO_INCREMENT,
   ID_AUTH BIGINT(4) NOT NULL  ,
   NOM_CLIENT VARCHAR(255) NOT NULL  ,
   PRENOM_CLIENT VARCHAR(255) NOT NULL  ,
   TEL_CLIENT VARCHAR(128) NOT NULL  UNIQUE,
   TYPE_CLIENT VARCHAR(128) NOT NULL ,
   PHOTO_CLIENT VARCHAR(255) NOT NULL DEFAULT 'assets/images/avatar.png'
   , PRIMARY KEY (ID_CLIENT) 
 ) 
 comment = "";

# -----------------------------------------------------------------------------
#       INDEX DE LA TABLE CLIENT
# -----------------------------------------------------------------------------


CREATE UNIQUE INDEX I_FK_CLIENT_AUTHENTIFICATION
     ON CLIENT (ID_AUTH ASC);

# -----------------------------------------------------------------------------
#       TABLE : MEDIA
# -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS MEDIA
 (
   ID_MEDIA BIGINT(4) NOT NULL  AUTO_INCREMENT,
   ID_RESERVATION BIGINT(4) NOT NULL  ,
   PATH_MEDIA VARCHAR(255) NOT NULL  ,
   TYPE_MEDIA VARCHAR(128) NOT NULL  
   , PRIMARY KEY (ID_MEDIA) 
 ) 
 comment = "";

# -----------------------------------------------------------------------------
#       INDEX DE LA TABLE MEDIA
# -----------------------------------------------------------------------------


CREATE  INDEX I_FK_MEDIA_RESERVATION
     ON MEDIA (ID_RESERVATION ASC);

# -----------------------------------------------------------------------------
#       TABLE : RESERVATION
# -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS RESERVATION
 (
   ID_RESERVATION BIGINT(4) NOT NULL AUTO_INCREMENT ,
   ID_PRESTATION BIGINT(4) NOT NULL  ,
   ID_CLIENT BIGINT(4) NOT NULL  ,
   DATE_RESERVATION DATE NOT NULL  ,
   HEURE_RESERVATION TIME NOT NULL  ,
   LIEU_RESERVATION VARCHAR(255) NOT NULL  ,
   COMME_RESERVATION VARCHAR(255) NOT NULL  ,
   STATUS_RESERVATION ENUM('EN ATTENTE','CONFIRMEE','ANNULEE','TERMINEE')  NOT NULL DEFAULT 'EN ATTENTE'
   , PRIMARY KEY (ID_RESERVATION) 
 ) 
 comment = "";

# -----------------------------------------------------------------------------
#       INDEX DE LA TABLE RESERVATION
# -----------------------------------------------------------------------------


CREATE  INDEX I_FK_RESERVATION_PRESTATIONS
     ON RESERVATION (ID_PRESTATION ASC);

CREATE  INDEX I_FK_RESERVATION_CLIENT
     ON RESERVATION (ID_CLIENT ASC);

# -----------------------------------------------------------------------------
#       TABLE : CATEGORIE
# -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS CATEGORIE
 (
   ID_CATEGORIE BIGINT(4) NOT NULL AUTO_INCREMENT ,
   ID_PRESTATION BIGINT(4) NOT NULL  ,
   LIB_CATEGORIE VARCHAR(128) NOT NULL,  
   TARIF_CATEGORIE BIGINT(4) NOT NULL,  
    PRIMARY KEY (ID_CATEGORIE)
 ) 
 comment = "";

# -----------------------------------------------------------------------------
#       INDEX DE LA TABLE CATEGORIE
# -----------------------------------------------------------------------------


CREATE  INDEX I_FK_CATEGORIE_PRESTATIONS
     ON CATEGORIE (ID_PRESTATION ASC);

# -----------------------------------------------------------------------------
#       TABLE : PRESTATIONS
# -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS PRESTATIONS
 (
   ID_PRESTATION BIGINT(4) NOT NULL AUTO_INCREMENT ,
   LIB_PRESTATION VARCHAR(128) NOT NULL  
   , PRIMARY KEY (ID_PRESTATION) 
 ) 
 comment = "";


# -----------------------------------------------------------------------------
#       CREATION DES REFERENCES DE TABLE
# -----------------------------------------------------------------------------


ALTER TABLE FACTURE 
  ADD FOREIGN KEY FK_FACTURE_CONTRAT (ID_CONTRAT)
      REFERENCES CONTRAT (ID_CONTRAT) 
       ON DELETE RESTRICT ON UPDATE CASCADE;


ALTER TABLE SECUTITE 
  ADD FOREIGN KEY FK_SECUTITE_AUTHENTIFICATION (ID_AUTH)
      REFERENCES AUTHENTIFICATION (ID_AUTH) 
      ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE DEVIS 
  ADD FOREIGN KEY FK_DEVIS_PRESTATIONS (ID_PRESTATION)
      REFERENCES PRESTATIONS (ID_PRESTATION) 
      ON DELETE RESTRICT ON UPDATE CASCADE;


ALTER TABLE PIECES_JOINTES 
  ADD FOREIGN KEY FK_PIECES_JOINTES_DEVIS (ID_DEVIS)
      REFERENCES DEVIS (ID_DEVIS) 
      ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE BLOG 
  ADD FOREIGN KEY FK_BLOG_TYPE_BLOG (ID_TYPE_BLOG)
      REFERENCES TYPE_BLOG (ID_TYPE_BLOG) 
      ON DELETE RESTRICT ON UPDATE CASCADE;


ALTER TABLE CONTRAT 
  ADD FOREIGN KEY FK_CONTRAT_RESERVATION (ID_RESERVATION)
      REFERENCES RESERVATION (ID_RESERVATION) 
      ON DELETE RESTRICT ON UPDATE CASCADE;


ALTER TABLE CLIENT 
  ADD FOREIGN KEY FK_CLIENT_AUTHENTIFICATION (ID_AUTH)
      REFERENCES AUTHENTIFICATION (ID_AUTH) 
      ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE MEDIA 
  ADD FOREIGN KEY FK_MEDIA_RESERVATION (ID_RESERVATION)
      REFERENCES RESERVATION (ID_RESERVATION) 
      ON DELETE CASCADE ON UPDATE CASCADE; 


ALTER TABLE RESERVATION 
  ADD FOREIGN KEY FK_RESERVATION_PRESTATIONS (ID_PRESTATION)
      REFERENCES PRESTATIONS (ID_PRESTATION)
      ON DELETE RESTRICT ON UPDATE CASCADE;


ALTER TABLE RESERVATION 
  ADD FOREIGN KEY FK_RESERVATION_CLIENT (ID_CLIENT)
      REFERENCES CLIENT (ID_CLIENT) 
       ON DELETE RESTRICT ON UPDATE CASCADE;


ALTER TABLE CATEGORIE 
  ADD FOREIGN KEY FK_CATEGORIE_PRESTATIONS (ID_PRESTATION)
      REFERENCES PRESTATIONS (ID_PRESTATION) 
      ON DELETE RESTRICT ON UPDATE CASCADE;

