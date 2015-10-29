CREATE TABLE logs
(
  id serial NOT NULL,
  type integer NOT NULL DEFAULT 0,
  message text NOT NULL,
  table_name text,
  item_id integer,
  date_created timestamp NOT NULL DEFAULT now(),
  user_id integer,
  CONSTRAINT logs_pkey PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);
