#!/usr/bin/bash
html-minifier html-src/downloads.phtml --collapse-whitespace --remove-comments --remove-optional-tags --remove-redundant-attributes --remove-script-type-attributes --remove-tag-whitespace --use-short-doctype  --output public/downloads.phtml
uglifycss html-src/css/base.css --output public/css/base.min.css
uglifyjs html-src/js/app.js --output public/js/app.min.js