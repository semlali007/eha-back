CREATE TABLE "EHA".notifications
(
    id integer NOT NULL DEFAULT nextval('notifications_id_seq'::regclass),
    user_nni character varying(255) COLLATE pg_catalog."default",
    updated_at date,
    is_notified boolean,
    sheet_id bigint,
    CONSTRAINT notifications_pkey PRIMARY KEY (id)
)