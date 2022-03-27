--
-- PostgreSQL database dump
--

-- Dumped from database version 12.4
-- Dumped by pg_dump version 12.4

-- Started on 2021-11-05 14:43:26

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 3132 (class 1262 OID 13318)
-- Name: postgres; Type: DATABASE; Schema: -; Owner: -
--


SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 3133 (class 0 OID 0)
-- Dependencies: 3132
-- Name: DATABASE postgres; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON DATABASE "EHA" IS 'default administrative connection database';

SET default_table_access_method = heap;

--
-- TOC entry 202 (class 1259 OID 24788)
-- Name: administrators; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".administrators (
    email character varying(100) NOT NULL,
    living boolean NOT NULL
);


--
-- TOC entry 203 (class 1259 OID 24791)
-- Name: committee; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".committee (
    code integer NOT NULL,
    label text
);


--
-- TOC entry 204 (class 1259 OID 24797)
-- Name: committee_code_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".committee_code_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3134 (class 0 OID 0)
-- Dependencies: 204
-- Name: committee_code_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".committee_code_seq OWNED BY "EHA".committee.code;


--
-- TOC entry 205 (class 1259 OID 24799)
-- Name: contract; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".contract (
    contract_system character varying(2) NOT NULL,
    contract_system_label character varying(50) NOT NULL,
    contract_code character varying(20) NOT NULL,
    contract_label character varying(250) NOT NULL,
    contract_date_start date NOT NULL,
    contract_date_end date NOT NULL,
    provider_code character varying(10) NOT NULL,
    provider_label character varying(50),
    entity_code character varying(10),
    entity_label character varying(50),
    organisation_achat_code character varying(10),
    organisation_achat_label character varying(50),
    contract_post_code character varying(5) NOT NULL,
    contract_post_label character varying(90),
    item_code character varying(20) NOT NULL,
    item_label character varying(90) NOT NULL,
    agency_code character varying(10),
    agency_label character varying(50),
    jauge smallint NOT NULL,
    search2 character varying(300) NOT NULL,
    search1 character varying(400) NOT NULL,
    search0 character varying(600) NOT NULL
);


--
-- TOC entry 206 (class 1259 OID 24805)
-- Name: dauphin; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".dauphin (
    code integer NOT NULL,
    url character varying(200) NOT NULL,
    label character varying(5000) NOT NULL,
    contract character varying(200) NOT NULL,
    contract_begin_date date NOT NULL,
    contract_end_date date NOT NULL,
    search character varying(800) NOT NULL
);


--
-- TOC entry 207 (class 1259 OID 24811)
-- Name: externes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".externes (
    externe_email character varying(100) NOT NULL,
    demandeur_email character varying(100) NOT NULL,
    date_fin date NOT NULL
);


--
-- TOC entry 208 (class 1259 OID 24814)
-- Name: fiche_dait; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".fiche_dait (
    etat_approbation text,
    application_creee_par text,
    application_modifiee_par text,
    approvisionnement_sous_dauphin text,
    archivage text,
    archivage_fiches_dapi text,
    commentaires_approbateur text,
    contenu_prive_dait text,
    contenu_prive_dait_id text,
    cree text,
    cree_par text,
    da_groupe text,
    date_de_debut text,
    date_de_fin text,
    description_du_marche text,
    fournisseurs text,
    id text,
    image text,
    kit_de_deploiement_dait text,
    kit_de_deploiement_dait_id text,
    lien_aide text,
    marche_ouvert_au_personnel_edf text,
    ouvert_aux_salaries text,
    partenariat_productivite text,
    secteur_protege text,
    segment_dait text,
    sous_ensemble_dait text,
    stipulation_pour_autrui text,
    titre text,
    type_de_contenu text,
    zone_geographique text,
    contenu text,
    modifie text,
    modifie_par text,
    nbre_d_enfant_du_dossier text,
    nbre_d_elements_enfants text,
    beneficiaires text,
    type_d_element text,
    chemin_d_acces text
);


--
-- TOC entry 209 (class 1259 OID 24820)
-- Name: fiche_dapi; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".fiche_dapi (
    etat_approbation text,
    id text,
    application_creee_par text,
    application_modifiee_par text,
    approvisionnement_sous_dauphin text,
    archivage text,
    archivage_fiches_dapi text,
    beneficiaires text,
    commentaires_approbateur text,
    contenu_prive_dapi text,
    contenu_prive_dapi_id text,
    da_groupe text,
    date_de_debut text,
    date_de_fin text,
    description_du_marche text,
    fournisseurs text,
    image text,
    kit_de_deploiement_dapi text,
    kit_de_deploiement_dapi_id text,
    lien_aide text,
    marche_ouvert_au_personnel_edf text,
    ouvert_aux_salaries text,
    partenariat_productivite text,
    secteur_protege text,
    segment_achat_dapi text,
    sous_ensemble_achat_dapi text,
    stipulation_pour_autrui text,
    titre text,
    type_de_contenu text,
    zone_geographique text,
    contenu text,
    cree text,
    cree_par text,
    modifie text,
    modifie_par text,
    nbre_d_enfant_du_dossier text,
    nbre_d_element_enfants text,
    type_d_element text,
    chemin_d_acces text
);


--
-- TOC entry 210 (class 1259 OID 24826)
-- Name: fiche_datpa; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".fiche_datpa (
    id text,
    commentaires_approbateur text,
    contenu text ,
    contrat_banalise text,
    contrats_enedis text,
    contrats_enedis_id text,
    contrats_groupe_edf_enedis text,
    contrats_groupe_edf_enedis_id text,
    cree_par text,
    da_groupe text,
    da_groupe_agances text,
    date_debut text,
    date_fin text,
    application_cree_par text,
    application_modifee_par text,
    approvisionnement_sous_dauphin text,
    archivage text,
    archivage_fiches_datp text,
    beneficiaires text,
    besoin text,
    cree text,
    descritpion_du_marche text,
    etat_d_approbation text,
    filiales text,
    fournisseurs text,
    type_d_element text,
    chemin_d_acces text 
);


--
-- TOC entry 211 (class 1259 OID 24832)
-- Name: fiche_datpb; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".fiche_datpb (
    id text,
    image text,
    kit_de_deploiement_datp text,
    kit_de_deploiement_datp_id text,
    lien_aide text,
    marche_ouvert_au_personnel_edf text,
    modifie text,
    modifie_par text,
    nbre_d_enfant_du_dossier text,
    nbre_d_element_enfants text,
    objet_achat_datp_cgs text,
    partenariat_productivite text,
    propositions text,
    secteur_protege text,
    segment_datp_cgs text,
    sous_ensemble_datp_cgs text,
    stipulation_pour_autrui text,
    titre text ,
    type_de_contenu text,
    zone_geographique text,
    type_d_element text,
    chemin_d_acces text
);


--
-- TOC entry 212 (class 1259 OID 24838)
-- Name: filiale; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".filiale (
    id integer NOT NULL,
    name text NOT NULL
);


--
-- TOC entry 213 (class 1259 OID 24844)
-- Name: filiale_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".filiale_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3135 (class 0 OID 0)
-- Dependencies: 213
-- Name: filiale_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".filiale_id_seq OWNED BY "EHA".filiale.id;


--
-- TOC entry 214 (class 1259 OID 24846)
-- Name: french_cities; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".french_cities (
    id_city integer NOT NULL,
    zipcode character varying(20) NOT NULL,
    latitude double precision NOT NULL,
    longitude double precision NOT NULL,
    x integer NOT NULL,
    y integer NOT NULL
);


--
-- TOC entry 215 (class 1259 OID 24849)
-- Name: french_cities_id_city_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".french_cities_id_city_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3136 (class 0 OID 0)
-- Dependencies: 215
-- Name: french_cities_id_city_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".french_cities_id_city_seq OWNED BY "EHA".french_cities.id_city;


--
-- TOC entry 216 (class 1259 OID 24851)
-- Name: group_direction; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA"."group_direction" (
    id integer NOT NULL,
    name text NOT NULL
);


--
-- TOC entry 217 (class 1259 OID 24857)
-- Name: group_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".group_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3137 (class 0 OID 0)
-- Dependencies: 217
-- Name: group_id_seq; Type: SEQUENCE OWNED BY; Schema: EHA; Owner: -
--

ALTER SEQUENCE "EHA".group_id_seq OWNED BY "EHA"."group_direction".id;


--
-- TOC entry 218 (class 1259 OID 24859)
-- Name: hotline; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".hotline (
    discussion integer NOT NULL,
    opened_at timestamp without time zone NOT NULL,
    email_from character varying(100) NOT NULL,
    info_entity character varying(500) NOT NULL,
    category_id smallint NOT NULL,
    question character varying(500) NOT NULL,
    sha_demandeur character varying(500) NOT NULL,
    sha_hotline character varying(500) NOT NULL,
    closed_at timestamp without time zone,
    closed_note smallint,
    theme_id smallint NOT NULL
);


--
-- TOC entry 219 (class 1259 OID 24862)
-- Name: hotline_discussion_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".hotline_discussion_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3138 (class 0 OID 0)
-- Dependencies: 219
-- Name: hotline_discussion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".hotline_discussion_seq OWNED BY "EHA".hotline.discussion;


--
-- TOC entry 220 (class 1259 OID 24864)
-- Name: hotline_email; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".hotline_email (
    id smallint NOT NULL,
    groupe character varying(5) NOT NULL,
    category character varying(100) NOT NULL,
    email character varying(100) NOT NULL,
    living boolean NOT NULL
);


--
-- TOC entry 221 (class 1259 OID 24867)
-- Name: hotline_fichiers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".hotline_fichiers (
    "row" integer NOT NULL,
    discussion integer NOT NULL,
    file_code character varying(100) NOT NULL,
    file_name character varying(250) NOT NULL
);


--
-- TOC entry 222 (class 1259 OID 24870)
-- Name: hotline_fichiers_row_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".hotline_fichiers_row_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3139 (class 0 OID 0)
-- Dependencies: 222
-- Name: hotline_fichiers_row_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".hotline_fichiers_row_seq OWNED BY "EHA".hotline_fichiers."row";


--
-- TOC entry 223 (class 1259 OID 24872)
-- Name: hotline_fil; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".hotline_fil (
    "row" integer NOT NULL,
    discussion integer NOT NULL,
    nature character(1) NOT NULL,
    quand timestamp without time zone NOT NULL,
    is_hotline boolean NOT NULL,
    email character varying(100) NOT NULL,
    message character varying(5000) NOT NULL
);


--
-- TOC entry 224 (class 1259 OID 24878)
-- Name: hotline_fil_row_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".hotline_fil_row_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3140 (class 0 OID 0)
-- Dependencies: 224
-- Name: hotline_fil_row_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".hotline_fil_row_seq OWNED BY "EHA".hotline_fil."row";


--
-- TOC entry 225 (class 1259 OID 24880)
-- Name: hotline_groupe; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".hotline_groupe (
    id character varying(5) NOT NULL,
    label character varying(100) NOT NULL,
    living boolean NOT NULL
);


--
-- TOC entry 226 (class 1259 OID 24883)
-- Name: hotline_theme; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".hotline_theme (
    id smallint NOT NULL,
    label character varying(100) NOT NULL,
    living boolean NOT NULL
);


--
-- TOC entry 227 (class 1259 OID 24886)
-- Name: item_generic; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".item_generic (
    item_code character varying(20) NOT NULL,
    item_label character varying(90) NOT NULL,
    item_label_long character varying(180) NOT NULL,
    item_notice character varying(5000),
    item_level_1 character varying(50) NOT NULL,
    item_level_2 character varying(50) NOT NULL,
    item_level_3 character varying(50) NOT NULL,
    comptes_e character varying(30),
    comptes_i character varying(30),
    tva character varying(30) NOT NULL,
    search2 character varying(110) NOT NULL,
    search1 character varying(200) NOT NULL,
    search0 character varying(1500) NOT NULL
);


--
-- TOC entry 252 (class 1259 OID 32770)
-- Name: menu_contacts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".menu_contacts (
    id integer NOT NULL,
    label text NOT NULL,
    "position" integer NOT NULL,
    parent integer NOT NULL,
    link text,
    external boolean DEFAULT true NOT NULL,
    breadcrumb text NOT NULL,
    description text,
    warning text
);


--
-- TOC entry 251 (class 1259 OID 32768)
-- Name: menu_contacts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".menu_contacts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3141 (class 0 OID 0)
-- Dependencies: 251
-- Name: menu_contacts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".menu_contacts_id_seq OWNED BY "EHA".menu_contacts.id;


--
-- TOC entry 254 (class 1259 OID 32782)
-- Name: menu_documentations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".menu_documentations (
    id integer NOT NULL,
    label text NOT NULL,
    "position" integer NOT NULL,
    parent integer NOT NULL,
    link text,
    external boolean DEFAULT true NOT NULL,
    breadcrumb text NOT NULL,
    warning text,
    description text
);


--
-- TOC entry 253 (class 1259 OID 32780)
-- Name: menu_documentations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".menu_documentations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3142 (class 0 OID 0)
-- Dependencies: 253
-- Name: menu_documentations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".menu_documentations_id_seq OWNED BY "EHA".menu_documentations.id;


--
-- TOC entry 256 (class 1259 OID 40982)
-- Name: news; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".news (
    title character varying(150),
    date date,
    description text,
    active boolean,
    id integer NOT NULL
);


--
-- TOC entry 257 (class 1259 OID 40988)
-- Name: news_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".news_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3143 (class 0 OID 0)
-- Dependencies: 257
-- Name: news_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".news_id_seq OWNED BY "EHA".news.id;


--
-- TOC entry 228 (class 1259 OID 24892)
-- Name: provider; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".provider (
    provider_code character varying(10) NOT NULL,
    provider_label character varying(50) NOT NULL,
    provider_country character varying(9) NOT NULL,
    provider_zipcode character varying(20) NOT NULL,
    provider_town character varying(50) NOT NULL,
    provider_siret character varying(20) NOT NULL,
    provider_nace character varying(20) NOT NULL,
    search1 character varying(60) NOT NULL,
    search0 character varying(130) NOT NULL,
    x integer,
    y integer,
    activities character varying(255),
    weight integer DEFAULT 0 NOT NULL
);


--
-- TOC entry 229 (class 1259 OID 24896)
-- Name: provider_item; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".provider_item (
    "row" integer NOT NULL,
    provider_code character varying(10) NOT NULL,
    item_code character varying(20) NOT NULL
);


--
-- TOC entry 230 (class 1259 OID 24899)
-- Name: provider_item_row_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".provider_item_row_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3144 (class 0 OID 0)
-- Dependencies: 230
-- Name: provider_item_row_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".provider_item_row_seq OWNED BY "EHA".provider_item."row";


--
-- TOC entry 231 (class 1259 OID 24901)
-- Name: repository; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".repository (
    family character varying(9) NOT NULL,
    code character varying(20) NOT NULL,
    label character varying(50) NOT NULL
);


--
-- TOC entry 232 (class 1259 OID 24904)
-- Name: sheet_contract_lots; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".sheet_contract_lots (
    sheet_id bigint NOT NULL,
    contract_code character varying(255) NOT NULL,
    lot_name character varying(255) NOT NULL
);


--
-- TOC entry 233 (class 1259 OID 24907)
-- Name: sheet_contracts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".sheet_contracts (
    id integer NOT NULL,
    sheet_id integer NOT NULL,
    contract_code character varying(20) NOT NULL,
    provider text,
    contacts text
);


--
-- TOC entry 234 (class 1259 OID 24913)
-- Name: sheet_contracts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".sheet_contracts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3145 (class 0 OID 0)
-- Dependencies: 234
-- Name: sheet_contracts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".sheet_contracts_id_seq OWNED BY "EHA".sheet_contracts.id;


--
-- TOC entry 235 (class 1259 OID 24915)
-- Name: sheet_files; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".sheet_files (
    id integer NOT NULL,
    sheet_id integer NOT NULL,
    file_name character varying(255),
    file_url text,
    file_size integer,
    node_id character varying(250),
    type integer DEFAULT 1
);


--
-- TOC entry 236 (class 1259 OID 24922)
-- Name: sheet_files_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".sheet_files_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3146 (class 0 OID 0)
-- Dependencies: 236
-- Name: sheet_files_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".sheet_files_id_seq OWNED BY "EHA".sheet_files.id;


--
-- TOC entry 237 (class 1259 OID 24924)
-- Name: sheet_filiales; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".sheet_filiales (
    sheet_id bigint NOT NULL,
    filiale_id bigint NOT NULL
);


--
-- TOC entry 238 (class 1259 OID 24927)
-- Name: sheet_groups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".sheet_groups (
    sheet_id bigint NOT NULL,
    group_id bigint NOT NULL
);


--
-- TOC entry 239 (class 1259 OID 24930)
-- Name: sheet_headers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".sheet_headers (
    id integer NOT NULL,
    label text NOT NULL,
    description text,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone,
    created_by character varying(10) NOT NULL,
    updated_by character varying(10),
    status smallint NOT NULL,
    created_by_full_name character varying(255),
    updated_by_full_name character varying(255),
    start_date date,
    end_date date,
    dauphin boolean NOT NULL,
    edf_open boolean NOT NULL,
    stipulation_for_others boolean NOT NULL,
    partnership boolean DEFAULT false NOT NULL,
    protected_sector boolean DEFAULT false NOT NULL,
    committee integer,
    showcase boolean DEFAULT false NOT NULL
);


--
-- TOC entry 240 (class 1259 OID 24939)
-- Name: sheet_headers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".sheet_headers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3147 (class 0 OID 0)
-- Dependencies: 240
-- Name: sheet_headers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".sheet_headers_id_seq OWNED BY "EHA".sheet_headers.id;


--
-- TOC entry 241 (class 1259 OID 24941)
-- Name: sheet_lot; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".sheet_lot (
    name character varying(255) NOT NULL,
    description text NOT NULL,
    sheet_id bigint NOT NULL
);


--
-- TOC entry 242 (class 1259 OID 24947)
-- Name: sheet_options; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".sheet_options (
    sheet_id bigint,
    start_date date,
    end_date date,
    id bigint NOT NULL
);


--
-- TOC entry 243 (class 1259 OID 24950)
-- Name: sheet_options_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".sheet_options_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3148 (class 0 OID 0)
-- Dependencies: 243
-- Name: sheet_options_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".sheet_options_id_seq OWNED BY "EHA".sheet_options.id;


--
-- TOC entry 244 (class 1259 OID 24952)
-- Name: sheet_providers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".sheet_providers (
    sheet_id bigint NOT NULL,
    provider_code character varying(10) NOT NULL
);


--
-- TOC entry 245 (class 1259 OID 24955)
-- Name: sheet_zones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".sheet_zones (
    sheet_id bigint NOT NULL,
    zone_id bigint NOT NULL
);


--
-- TOC entry 246 (class 1259 OID 24958)
-- Name: stats; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".stats (
    table_name character varying(19) NOT NULL,
    updated_date timestamp without time zone
);


--
-- TOC entry 247 (class 1259 OID 24961)
-- Name: tenders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".tenders (
    "row" integer NOT NULL,
    tender_code character(13) NOT NULL,
    description_short text,
    description_long text,
    provider_label text,
    buyer_contact text,
    buyer_team text,
    max_date date,
    search1 text,
    search0 text
);


--
-- TOC entry 248 (class 1259 OID 24967)
-- Name: tenders_row_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".tenders_row_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3149 (class 0 OID 0)
-- Dependencies: 248
-- Name: tenders_row_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".tenders_row_seq OWNED BY "EHA".tenders."row";


--
-- TOC entry 255 (class 1259 OID 40969)
-- Name: user_news; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".user_news (
    nni_utilisateur character varying(20),
    id_news integer,
    id integer NOT NULL
);


--
-- TOC entry 258 (class 1259 OID 49159)
-- Name: user_news_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".user_news_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3150 (class 0 OID 0)
-- Dependencies: 258
-- Name: user_news_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".user_news_id_seq OWNED BY "EHA".user_news.id;


--
-- TOC entry 249 (class 1259 OID 24969)
-- Name: zone; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE "EHA".zone (
    id integer NOT NULL,
    name text NOT NULL
);


--
-- TOC entry 250 (class 1259 OID 24975)
-- Name: zone_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE "EHA".zone_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3151 (class 0 OID 0)
-- Dependencies: 250
-- Name: zone_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE "EHA".zone_id_seq OWNED BY "EHA".zone.id;


--
-- TOC entry 2893 (class 2604 OID 24977)
-- Name: committee code; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".committee ALTER COLUMN code SET DEFAULT nextval('"EHA".committee_code_seq'::regclass);


--
-- TOC entry 2894 (class 2604 OID 24978)
-- Name: filiale id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".filiale ALTER COLUMN id SET DEFAULT nextval('"EHA".filiale_id_seq'::regclass);


--
-- TOC entry 2895 (class 2604 OID 24979)
-- Name: french_cities id_city; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".french_cities ALTER COLUMN id_city SET DEFAULT nextval('"EHA".french_cities_id_city_seq'::regclass);


--
-- TOC entry 2896 (class 2604 OID 24980)
-- Name: group id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA"."group_direction" ALTER COLUMN id SET DEFAULT nextval('"EHA".group_id_seq'::regclass);


--
-- TOC entry 2897 (class 2604 OID 24981)
-- Name: hotline discussion; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".hotline ALTER COLUMN discussion SET DEFAULT nextval('"EHA".hotline_discussion_seq'::regclass);


--
-- TOC entry 2898 (class 2604 OID 24982)
-- Name: hotline_fichiers row; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".hotline_fichiers ALTER COLUMN "row" SET DEFAULT nextval('"EHA".hotline_fichiers_row_seq'::regclass);


--
-- TOC entry 2899 (class 2604 OID 24983)
-- Name: hotline_fil row; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".hotline_fil ALTER COLUMN "row" SET DEFAULT nextval('"EHA".hotline_fil_row_seq'::regclass);


--
-- TOC entry 2912 (class 2604 OID 32773)
-- Name: menu_contacts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".menu_contacts ALTER COLUMN id SET DEFAULT nextval('"EHA".menu_contacts_id_seq'::regclass);


--
-- TOC entry 2914 (class 2604 OID 32785)
-- Name: menu_documentations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".menu_documentations ALTER COLUMN id SET DEFAULT nextval('"EHA".menu_documentations_id_seq'::regclass);


--
-- TOC entry 2917 (class 2604 OID 40990)
-- Name: news id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".news ALTER COLUMN id SET DEFAULT nextval('"EHA".news_id_seq'::regclass);


--
-- TOC entry 2901 (class 2604 OID 24984)
-- Name: provider_item row; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".provider_item ALTER COLUMN "row" SET DEFAULT nextval('"EHA".provider_item_row_seq'::regclass);


--
-- TOC entry 2902 (class 2604 OID 24985)
-- Name: sheet_contracts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_contracts ALTER COLUMN id SET DEFAULT nextval('"EHA".sheet_contracts_id_seq'::regclass);


--
-- TOC entry 2904 (class 2604 OID 24986)
-- Name: sheet_files id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_files ALTER COLUMN id SET DEFAULT nextval('"EHA".sheet_files_id_seq'::regclass);


--
-- TOC entry 2908 (class 2604 OID 24987)
-- Name: sheet_headers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_headers ALTER COLUMN id SET DEFAULT nextval('"EHA".sheet_headers_id_seq'::regclass);


--
-- TOC entry 2909 (class 2604 OID 24988)
-- Name: sheet_options id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_options ALTER COLUMN id SET DEFAULT nextval('"EHA".sheet_options_id_seq'::regclass);


--
-- TOC entry 2910 (class 2604 OID 24989)
-- Name: tenders row; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".tenders ALTER COLUMN "row" SET DEFAULT nextval('"EHA".tenders_row_seq'::regclass);


--
-- TOC entry 2916 (class 2604 OID 49161)
-- Name: user_news id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".user_news ALTER COLUMN id SET DEFAULT nextval('"EHA".user_news_id_seq'::regclass);


--
-- TOC entry 2911 (class 2604 OID 24990)
-- Name: zone id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".zone ALTER COLUMN id SET DEFAULT nextval('"EHA".zone_id_seq'::regclass);


--
-- TOC entry 2919 (class 2606 OID 25365)
-- Name: administrators administrators_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".administrators
    ADD CONSTRAINT administrators_pkey PRIMARY KEY (email);


--
-- TOC entry 2921 (class 2606 OID 25367)
-- Name: committee committee_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".committee
    ADD CONSTRAINT committee_pkey PRIMARY KEY (code);


--
-- TOC entry 2923 (class 2606 OID 25369)
-- Name: contract contract_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".contract
    ADD CONSTRAINT contract_pkey PRIMARY KEY (contract_system, contract_code, provider_code, contract_post_code, item_code);


--
-- TOC entry 2926 (class 2606 OID 25371)
-- Name: dauphin dauphin_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".dauphin
    ADD CONSTRAINT dauphin_pkey PRIMARY KEY (code);


--
-- TOC entry 2928 (class 2606 OID 25373)
-- Name: externes externes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".externes
    ADD CONSTRAINT externes_pkey PRIMARY KEY (externe_email);


--
-- TOC entry 2930 (class 2606 OID 25375)
-- Name: filiale filiale_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".filiale
    ADD CONSTRAINT filiale_pkey PRIMARY KEY (id);


--
-- TOC entry 2935 (class 2606 OID 25377)
-- Name: group group_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA"."group_direction"
    ADD CONSTRAINT group_name_key UNIQUE (name);


--
-- TOC entry 2937 (class 2606 OID 25379)
-- Name: group group_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA"."group_direction"
    ADD CONSTRAINT group_pkey PRIMARY KEY (id);


--
-- TOC entry 2942 (class 2606 OID 25381)
-- Name: hotline_email hotline_email_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".hotline_email
    ADD CONSTRAINT hotline_email_pkey PRIMARY KEY (id);


--
-- TOC entry 2944 (class 2606 OID 25383)
-- Name: hotline_fichiers hotline_fichiers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".hotline_fichiers
    ADD CONSTRAINT hotline_fichiers_pkey PRIMARY KEY ("row");


--
-- TOC entry 2947 (class 2606 OID 25385)
-- Name: hotline_fil hotline_fil_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".hotline_fil
    ADD CONSTRAINT hotline_fil_pkey PRIMARY KEY ("row");


--
-- TOC entry 2950 (class 2606 OID 25387)
-- Name: hotline_groupe hotline_groupe_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".hotline_groupe
    ADD CONSTRAINT hotline_groupe_pkey PRIMARY KEY (id);


--
-- TOC entry 2939 (class 2606 OID 25389)
-- Name: hotline hotline_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".hotline
    ADD CONSTRAINT hotline_pkey PRIMARY KEY (discussion);


--
-- TOC entry 2952 (class 2606 OID 25391)
-- Name: hotline_theme hotline_theme_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".hotline_theme
    ADD CONSTRAINT hotline_theme_pkey PRIMARY KEY (id);


--
-- TOC entry 2954 (class 2606 OID 25393)
-- Name: item_generic item_generic_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".item_generic
    ADD CONSTRAINT item_generic_pkey PRIMARY KEY (item_code);


--
-- TOC entry 2992 (class 2606 OID 32779)
-- Name: menu_contacts menu_contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".menu_contacts
    ADD CONSTRAINT menu_contacts_pkey PRIMARY KEY (id);


--
-- TOC entry 2994 (class 2606 OID 32791)
-- Name: menu_documentations menu_documentations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".menu_documentations
    ADD CONSTRAINT menu_documentations_pkey PRIMARY KEY (id);


--
-- TOC entry 2932 (class 2606 OID 25395)
-- Name: filiale name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".filiale
    ADD CONSTRAINT name_unique UNIQUE (name);


--
-- TOC entry 2998 (class 2606 OID 40998)
-- Name: news news_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".news
    ADD CONSTRAINT news_pkey PRIMARY KEY (id);


--
-- TOC entry 2980 (class 2606 OID 25397)
-- Name: sheet_providers pk_sheet_providers; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_providers
    ADD CONSTRAINT pk_sheet_providers PRIMARY KEY (provider_code, sheet_id);


--
-- TOC entry 2960 (class 2606 OID 25399)
-- Name: provider_item provider_item_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".provider_item
    ADD CONSTRAINT provider_item_pkey PRIMARY KEY ("row");


--
-- TOC entry 2957 (class 2606 OID 25401)
-- Name: provider provider_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".provider
    ADD CONSTRAINT provider_pkey PRIMARY KEY (provider_code);


--
-- TOC entry 2962 (class 2606 OID 25404)
-- Name: repository repository_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".repository
    ADD CONSTRAINT repository_pkey PRIMARY KEY (family, code);


--
-- TOC entry 2966 (class 2606 OID 25406)
-- Name: sheet_contracts sheet_contracts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_contracts
    ADD CONSTRAINT sheet_contracts_pkey PRIMARY KEY (sheet_id, contract_code);


--
-- TOC entry 2968 (class 2606 OID 25408)
-- Name: sheet_files sheet_files_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_files
    ADD CONSTRAINT sheet_files_pkey PRIMARY KEY (id);


--
-- TOC entry 2970 (class 2606 OID 25410)
-- Name: sheet_filiales sheet_filiales_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_filiales
    ADD CONSTRAINT sheet_filiales_pkey PRIMARY KEY (sheet_id, filiale_id);


--
-- TOC entry 2972 (class 2606 OID 25412)
-- Name: sheet_groups sheet_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_groups
    ADD CONSTRAINT sheet_groups_pkey PRIMARY KEY (sheet_id, group_id);


--
-- TOC entry 2976 (class 2606 OID 25414)
-- Name: sheet_lot sheet_lot_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_lot
    ADD CONSTRAINT sheet_lot_unique UNIQUE (sheet_id, name);


--
-- TOC entry 2978 (class 2606 OID 25416)
-- Name: sheet_options sheet_options_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_options
    ADD CONSTRAINT sheet_options_pkey PRIMARY KEY (id);


--
-- TOC entry 2982 (class 2606 OID 25418)
-- Name: sheet_zones sheet_zone_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_zones
    ADD CONSTRAINT sheet_zone_pkey PRIMARY KEY (sheet_id, zone_id);


--
-- TOC entry 2974 (class 2606 OID 25420)
-- Name: sheet_headers sheets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_headers
    ADD CONSTRAINT sheets_pkey PRIMARY KEY (id);


--
-- TOC entry 2984 (class 2606 OID 25422)
-- Name: stats stats_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".stats
    ADD CONSTRAINT stats_pkey PRIMARY KEY (table_name);


--
-- TOC entry 2986 (class 2606 OID 25424)
-- Name: tenders tenders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".tenders
    ADD CONSTRAINT tenders_pkey PRIMARY KEY ("row");


--
-- TOC entry 2964 (class 2606 OID 25426)
-- Name: sheet_contract_lots unique_lot; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_contract_lots
    ADD CONSTRAINT unique_lot PRIMARY KEY (sheet_id, contract_code, lot_name);


--
-- TOC entry 2996 (class 2606 OID 49166)
-- Name: user_news user_news_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".user_news
    ADD CONSTRAINT user_news_pkey PRIMARY KEY (id);


--
-- TOC entry 2988 (class 2606 OID 25428)
-- Name: zone zone_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".zone
    ADD CONSTRAINT zone_name_key UNIQUE (name);


--
-- TOC entry 2990 (class 2606 OID 25430)
-- Name: zone zone_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".zone
    ADD CONSTRAINT zone_pkey PRIMARY KEY (id);


--
-- TOC entry 2933 (class 1259 OID 25431)
-- Name: idx_cities_zipcode; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX idx_cities_zipcode ON "EHA".french_cities USING btree (zipcode);


--
-- TOC entry 2924 (class 1259 OID 25432)
-- Name: idx_contract_provider; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_contract_provider ON "EHA".contract USING btree (provider_code);


--
-- TOC entry 2940 (class 1259 OID 25433)
-- Name: idx_hotline_email_from; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_hotline_email_from ON "EHA".hotline USING btree (email_from);


--
-- TOC entry 2945 (class 1259 OID 25434)
-- Name: idx_hotline_fichiers; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX idx_hotline_fichiers ON "EHA".hotline_fichiers USING btree (file_code);


--
-- TOC entry 2948 (class 1259 OID 25435)
-- Name: idx_hotline_fil_discussion; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_hotline_fil_discussion ON "EHA".hotline_fil USING btree (discussion);


--
-- TOC entry 2958 (class 1259 OID 25436)
-- Name: idx_provider_item; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX idx_provider_item ON "EHA".provider_item USING btree (provider_code, item_code);


--
-- TOC entry 2955 (class 1259 OID 25437)
-- Name: idx_provider_x_y; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_provider_x_y ON "EHA".provider USING btree (x, y);


--
-- TOC entry 2999 (class 2606 OID 25438)
-- Name: sheet_contracts fk_sheet_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_contracts
    ADD CONSTRAINT fk_sheet_id FOREIGN KEY (sheet_id) REFERENCES "EHA".sheet_headers(id);


--
-- TOC entry 3000 (class 2606 OID 25443)
-- Name: sheet_files fk_sheet_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY "EHA".sheet_files
    ADD CONSTRAINT fk_sheet_id FOREIGN KEY (sheet_id) REFERENCES "EHA".sheet_headers(id);


ALTER TABLE "EHA".hotline 
    ADD COLUMN notified INTEGER DEFAULT 0;

ALTER TABLE "EHA".menu_documentations 
    ADD COLUMN shortdescription TEXT;

-- Completed on 2021-11-05 14:43:27

--
-- PostgreSQL database dump complete
--

CREATE TABLE "EHA".static_page
(
    page character varying(50)  NOT NULL,
    content text NOT NULL,
    active boolean DEFAULT false NOT NULL,
    title character varying(100)  NOT NULL,
    CONSTRAINT static_page_pkey PRIMARY KEY (page)
);

INSERT INTO "EHA".static_page(page, content, active, title)
	VALUES ('sheet_modal_news', 'Vitrine de l offre', false, 'Vitrine de l offre');

INSERT INTO "EHA".static_page(page, content, active, title)
	VALUES ('purchase_modal_news', 'Ma situation achat', false, 'Ma situation achat');

CREATE TABLE "EHA".informations
(
    type character varying(50) NOT NULL,
    short_description text  NOT NULL,
    long_description text  NOT NULL,
    start_date timestamp without time zone,
    end_date timestamp without time zone,
    id bigserial NOT NULL
);

ALTER TABLE ONLY "EHA".informations
    ADD CONSTRAINT informations_pkey PRIMARY KEY (id);


ALTER TABLE "EHA".user_news ADD COLUMN page VARCHAR(50);
ALTER TABLE "EHA".news ALTER COLUMN title TYPE TEXT;
ALTER TABLE "EHA".news  ADD COLUMN type VARCHAR (50) ;
INSERT INTO "EHA".static_page(page, content, active, title)
VALUES ('home_page', 'Page principale', false, 'Page principale');

INSERT INTO "EHA".hotline_email VALUES (104, 'TP', 'Déplacement', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (205, 'PR', 'pour démonstration', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (206, 'IN', 'pour démonstration', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (105, 'TP', 'Facility management', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (106, 'TP', 'Formation-RetD', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (107, 'TP', 'Fournitures industrielles', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (108, 'TP', 'Génie civil', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (109, 'TP', 'Mobilier de bureau', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (110, 'TP', 'Prestations administratives', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (111, 'TP', 'Prestations industrielles', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (112, 'TP', 'Processus Achat-SI', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (114, 'TP', 'Transport-logistique', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (115, 'TP', 'Travaux immobiliers', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (116, 'TP', 'Véhicules', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (201, 'IT', 'Logiciels', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (202, 'IT', 'Matériels', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (203, 'IT', 'Prestations', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (204, 'IT', 'Télécommunications', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (101, 'TP', 'Bâtiment provisoire', 'da-dag-achatpedia@edf.fr', false);
INSERT INTO "EHA".hotline_email VALUES (102, 'TP', 'Communication-publicité', 'da-dag-achatpedia@edf.fr', false);
INSERT INTO "EHA".hotline_email VALUES (103, 'TP', 'Conseil', 'da-dag-achatpedia@edf.fr', true);
INSERT INTO "EHA".hotline_email VALUES (113, 'TP', 'Restauration', 'da-dag-achatpedia@edf.fr', true);


INSERT INTO "EHA".hotline_groupe VALUES ('TP', 'Tertiaire et Prestations', true);
INSERT INTO "EHA".hotline_groupe VALUES ('IT', ' Informatique et Télécommunications ', true);
INSERT INTO "EHA".hotline_groupe VALUES ('PR', 'Production', true);
INSERT INTO "EHA".hotline_groupe VALUES ('IN', 'Ingénierie', true);

INSERT INTO "EHA".hotline_theme VALUES (11, 'Question sur demande d''achat', true);
INSERT INTO "EHA".hotline_theme VALUES (12, 'Question sur Marchés en cours', true);
INSERT INTO "EHA".hotline_theme VALUES (13, 'Problème de Modélisation contrat (Fournisseur bloqué, SIRET inactif....)', true);
INSERT INTO "EHA".hotline_theme VALUES (14, 'Commande déléguée (Mode Contrôle)', true);
INSERT INTO "EHA".hotline_theme VALUES (15, 'Relations Fournisseurs', true);
INSERT INTO "EHA".hotline_theme VALUES (16, 'Liens article fournisseur', true);
INSERT INTO "EHA".hotline_theme VALUES (17, 'Création de fournisseur', true);
INSERT INTO "EHA".hotline_theme VALUES (18, 'Question matériel informatique et consommables bureautiques', true);
INSERT INTO "EHA".hotline_theme VALUES (19, 'Achat de licences logiciels et maintenance associée', true);
INSERT INTO "EHA".hotline_theme VALUES (20, 'Réclamation', true);
INSERT INTO "EHA".hotline_theme VALUES (21, 'Divers', true);
INSERT INTO "EHA".hotline_theme VALUES (22, 'Fiches Marchés', true);

ALTER TABLE "EHA".provider ALTER COLUMN provider_code TYPE VARCHAR(200);
ALTER TABLE "EHA".sheet_providers ALTER COLUMN provider_code TYPE VARCHAR(200);
CREATE TABLE "EHA".subscription
(
    sheet_id  bigserial NOT NULL,
    created date,
    user_nni character varying(100) NOT NULL,
    CONSTRAINT subscription_pkey PRIMARY KEY (user_nni, sheet_id)
)



ALTER TABLE "EHA".sheet_headers 
    ADD COLUMN netachat_id integer;

    ALTER TABLE "EHA".sheet_headers 
    ADD COLUMN content text;

       ALTER TABLE "EHA".sheet_headers 
    ADD COLUMN netachat_type text;