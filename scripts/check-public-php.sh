#!/usr/bin/env bash
# Fail build if new .php files are present in public_html (except index.php)
set -e
shopt -s globstar
violations=()
for f in public_html/**/*.php; do
  [[ "$f" == "public_html/index.php" ]] && continue
  [[ -f "$f" ]] || continue
  violations+=("$f")
done
if [[ ${#violations[@]} -gt 0 ]]; then
  echo "Disallowed PHP files detected in public_html:" >&2
  printf '  %s\n' "${violations[@]}" >&2
  exit 1
fi 