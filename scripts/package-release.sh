#!/bin/sh
# SPDX-FileCopyrightText: 2026 Vitor Mattos
# SPDX-License-Identifier: AGPL-3.0-or-later
set -eu

if [ "$#" -lt 1 ] || [ "$#" -gt 2 ]; then
    echo "Usage: $0 <git-tag-or-commit> [output-directory]" >&2
    exit 2
fi
ref=$1
output=${2:-dist}
cd "$(dirname "$0")/.."
root=$(pwd)
version=$(git show "${ref}:plugin.json" | python3 -c 'import json,sys; print(json.load(sys.stdin)["version"])')
name="whatsapp-gowa-${version}.zip"
mkdir -p "$output"
case $output in
    /*) destination=$output ;;
    *) destination="$root/$output" ;;
esac
staging=$(mktemp -d)
trap 'rm -rf "$staging"' EXIT HUP INT TERM
mkdir -p "$staging/whatsapp-gowa"
git archive --format=tar "$ref" plugin.json src routes views locale README.md LICENSE SECURITY.md docs \
    | tar -xf - -C "$staging/whatsapp-gowa"
timestamp=$(git show -s --format=%ct "$ref")
find "$staging/whatsapp-gowa" -exec touch -h -d "@$timestamp" {} +
(cd "$staging" && find whatsapp-gowa -type f | LC_ALL=C sort | zip -X -q "$destination/$name" -@)
(cd "$destination" && sha256sum "$name" > "$name.sha256")
printf 'PACKAGE=%s\n' "$destination/$name"
cat "$destination/$name.sha256"
