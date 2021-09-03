#!/bin/bash

iconv -f utf-8 -t windows-1252 -c /tmp/data_hotline.csv > /appli/projects/{{ INSTANCE_WEB }}/apache_2.4/htdocs/tmp/data_hotline.csv
