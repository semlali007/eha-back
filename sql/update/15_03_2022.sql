CREATE TABLE "EHA".notifications
(
    id integer NOT NULL DEFAULT nextval('notifications_id_seq'::regclass),
    user_nni character varying(255) COLLATE pg_catalog."default",
    updated_at timestamp with time zone,
    is_notified boolean,
    sheet_id bigint,
    CONSTRAINT notifications_pkey PRIMARY KEY (id)
)