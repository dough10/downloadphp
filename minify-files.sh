#!/usr/bin/bash

generate_random_string() {
  length=$1
  openssl rand -base64 "$length" | tr -d '/+' | cut -c1-"$length"
}

replace_path() {
  search="$1"
  replace="$2"
  file="$3"

  search_escaped=$(printf '%s' "$search" | sed 's/[&/\]/\\&/g')
  replace_escaped=$(printf '%s' "$replace" | sed 's/[&/\]/\\&/g')

  if [[ "$(uname)" == "Darwin" ]]; then
    sed -i "" "s|$search_escaped|$replace_escaped|g" "$file"
  else
    sed -i "s|$search_escaped|$replace_escaped|g" "$file"
  fi

}

version=$(generate_random_string 8)

css_path=css/base.min.$version.css
js_path=js/app.min.$version.js

template=templates/downloads.phtml

rm -f public/css/*.css
rm -f public/js/*.js
rm -f templates/*.phtml

uglifycss html-src/css/base.css --output "public/$css_path"
uglifyjs html-src/js/app.js --output "public/$js_path"
html-minifier html-src/downloads.phtml --collapse-whitespace --remove-comments --remove-optional-tags --remove-redundant-attributes --remove-script-type-attributes --remove-tag-whitespace --use-short-doctype  --output $template

replace_path ./css/base.css "./$css_path" $template
replace_path ./js/app.js "./$js_path" $template

