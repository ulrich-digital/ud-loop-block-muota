#!/bin/bash

# =============================
# Funktion
# =============================
# Dieses Skript listet rekursiv alle Ordner und Dateien im aktuellen Verzeichnis auf.
# Ungewünschte Verzeichnisse und Dateien können ausgeschlossen.

# =============================
# Voraussetzung
# =============================
# Diese Datei muss im zu durchsuchenden Verzeichnis liegen.
#
# =============================
# aufruf im Terminal (ohne #)
# =============================
#  chmod +x verzeichnisliste.sh
#  ./verzeichnisliste.sh .DS_Store .git .gitattributes .gitignore build node_modules vendor advanced-custom-fields-pro ajax-search-pro wordpress-seo wp-crontrol

STARTDIR="$PWD"
IGNORE_DIRS=("$@")

# Prüfen, ob ein Ordner ignoriert werden soll
should_ignore() {
    local name="$1"
    for ignore in "${IGNORE_DIRS[@]}"; do
        if [[ "$name" == "$ignore" ]]; then
            return 0
        fi
    done
    return 1
}

# Rekursive Ausgabe
list_dir() {
    local dir="$1"
    local indent="$2"

    local base=$(basename "$dir")

    if should_ignore "$base"; then
        return
    fi

    echo "${indent}${base}"

    # Inhalte alphabetisch sortieren
    local items=()
    while IFS= read -r -d '' item; do
        items+=("$item")
    done < <(find "$dir" -mindepth 1 -maxdepth 1 -print0 | sort -z)

    for item in "${items[@]}"; do
        if [ -d "$item" ]; then
            list_dir "$item" "$indent- "
        elif [ -f "$item" ]; then
            echo "${indent}- $(basename "$item")"
        fi
    done
}

list_dir "$STARTDIR" ""
