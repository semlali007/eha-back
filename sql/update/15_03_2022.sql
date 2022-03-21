CREATE table if not exists "EHA".notifications
(
    id serial NOT NULL,
    user_nni character varying(255) COLLATE pg_catalog."default",
    is_notified boolean,
    sheet_id bigint,
    updated_at timestamp with time zone,
    CONSTRAINT notifications_pkey PRIMARY KEY (id)
)
