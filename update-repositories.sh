#!/bin/bash

PROJECTS_DIR="$HOME/projects/liberu"
BOILERPLATE_DIR="$PROJECTS_DIR/boilerplate-laravel"
LOG_FILE="$HOME/update-projects.log"
TIMESTAMP=$(date "+%Y-%m-%d %H:%M:%S")

echo "===== Starting update: $TIMESTAMP =====" | tee -a "$LOG_FILE"

# List of repos to update
REPOS=(
    "ecommerce-laravel"
    "browser-game-laravel"
    "real-estate-laravel"
    "accounting-laravel"
    "cms-laravel"
    "maintenance-laravel"
    "social-network-laravel"
    "automation-laravel"
    "control-panel-laravel"
    "genealogy-laravel"
    "billing-laravel"
    "crm-laravel"
    "liberu-website"
    "ofie"
    "floralgo"
)

for repo in "${REPOS[@]}"; do
    TARGET_DIR="$PROJECTS_DIR/$repo"

    if [ -d "$TARGET_DIR/.git" ]; then
        echo "----- Processing $repo -----" | tee -a "$LOG_FILE"
        cd "$TARGET_DIR" || { echo "Failed to enter $TARGET_DIR" | tee -a "$LOG_FILE"; continue; }

        {
            echo "Pulling latest changes from origin..."
            git pull || { echo "git pull failed in $repo"; continue; }

            # --- Composer updates ---
            if [ -f composer.json ]; then
                echo "Running composer update..."
                composer update || { echo "composer update failed in $repo"; continue; }
            fi

            # --- NPM updates ---
            if [ -f package.json ]; then
                echo "Running npm install..."
                npm install || { echo "npm install failed in $repo"; continue; }

                echo "Running npm upgrade..."
                npm upgrade || { echo "npm upgrade failed in $repo"; continue; }

                echo "Running npm audit fix..."
                npm audit fix --force || { echo "npm audit fix failed in $repo"; continue; }

                echo "Running npm run build..."
                npm run build || { echo "npm run build failed in $repo"; continue; }
            fi

            # --- Placeholder for custom updates ---
            # Example: Copy boilerplate files
            # cp -r "$PROJECTS_DIR/boilerplate-laravel/app/." "$TARGET_DIR/app/"
            # cp -r "$PROJECTS_DIR/boilerplate-laravel/config/." "$TARGET_DIR/config/"

            # --- Commit any automatic changes ---
            if ! git diff --quiet || ! git diff --cached --quiet; then
                echo "Committing and pushing changes..."
                git add -A
                git commit -m "Update dependencies and boilerplate changes" || true
                git push || { echo "git push failed in $repo"; continue; }
            else
                echo "No changes to commit"
            fi

        } 2>&1 | tee -a "$LOG_FILE"

    else
        echo "Skipping $repo - not a git repo" | tee -a "$LOG_FILE"
    fi
done

echo "===== Update completed: $(date "+%Y-%m-%d %H:%M:%S") =====" | tee -a "$LOG_FILE"