#!/bin/bash

# export hotline table into a csv file
psql -U postgres -p {{ database.listenPort }} {{ database.databaseName }} -c "COPY (SELECT f.discussion, t.label as theme, h.info_entity, e.groupe, e.category, f.row, date_trunc('second',f.quand) as quand, f.nature, f.is_hotline, f.email, f.message FROM {{ database.schemaName }}.hotline_fil AS f LEFT JOIN {{ database.schemaName }}.hotline AS h ON f.discussion = h.discussion LEFT JOIN {{ database.schemaName }}.hotline_email AS e ON h.category_id = e.id LEFT JOIN {{ database.schemaName }}.hotline_theme AS t ON h.theme_id = t.id) TO '/tmp/data_hotline.csv' DELIMITER ';' CSV HEADER" > /tmp/log_export_hotline.txt

# move the file
#cp data_hotline.csv /tmp/data_hotline.csv
chmod o+r /tmp/data_hotline.csv
