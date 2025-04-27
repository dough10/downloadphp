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

css_path=base.min.$version.css
js_path=app.min.$version.js

template=templates/downloads.phtml

html-minifier html-src/downloads.phtml --collapse-whitespace --decode-entities --remove-comments --remove-optional-tags --remove-redundant-attributes --remove-script-type-attributes --remove-tag-whitespace --use-short-doctype  --output $template
cp html-src/js/*.php public

replace_path ./base.css "./$css_path" $template
replace_path ./app.js "./$js_path" $template

mv public/base.min.css public/"$css_path"
mv public/app.min.js public/"$js_path"

