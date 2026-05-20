#!/bin/bash

PROJECTS_DIR="$HOME/Projects/liberu"
BOILERPLATE_DIR="$PROJECTS_DIR/boilerplate-laravel"
LOG_FILE="$PROJECTS_DIR/update-projects.log"
TIMESTAMP=$(date "+%Y-%m-%d %H:%M:%S")

echo "===== Starting update: $TIMESTAMP =====" | tee -a "$LOG_FILE"

# List of repositories to update
REPOS=(
    "accounting-laravel"
    "automation-laravel"
    "billing-laravel"
    "browser-game-laravel"
    "cms-laravel"
    "control-panel-laravel"
    "crm-laravel"
    "ecommerce-laravel"
    "genealogy-laravel"
    "maintenance-laravel"
    "real-estate-laravel"
    "social-network-laravel"
    "liberu-website"
    "ofie"
    "floralgo"
)

for repo in "${REPOS[@]}"; do
    TARGET_DIR="$PROJECTS_DIR/$repo"

    if [ ! -d "$TARGET_DIR/.git" ]; then
        echo "Skipping $repo - not a git repo" | tee -a "$LOG_FILE"
        continue
    fi

    echo "----- Processing $repo -----" | tee -a "$LOG_FILE"
    cd "$TARGET_DIR" || { echo "Failed to enter $TARGET_DIR" | tee -a "$LOG_FILE"; continue; }

    # Pull latest changes
    echo "Pulling latest changes from origin..."
    if ! git pull; then
        echo "git pull failed in $repo" | tee -a "$LOG_FILE"
        continue
    fi

    # --- Composer updates ---
    # if [ -f composer.json ]; then
    #     echo "Running composer update..."
    #     if ! composer update; then
    #         echo "composer update failed in $repo" | tee -a "$LOG_FILE"
    #         continue
    #     fi
    # fi

    # # --- NPM updates ---
    # if [ -f package.json ]; then
    #     echo "Running npm install..."
    #     if ! npm install; then
    #         echo "npm install failed in $repo" | tee -a "$LOG_FILE"
    #         continue
    #     fi

    #     echo "Running npm upgrade..."
    #     if ! npm upgrade; then
    #         echo "npm upgrade failed in $repo" | tee -a "$LOG_FILE"
    #         continue
    #     fi

    #     echo "Running npm audit fix..."
    #     if ! npm audit fix --force; then
    #         echo "npm audit fix failed in $repo" | tee -a "$LOG_FILE"
    #         continue
    #     fi

    #     echo "Running npm run build..."
    #     if ! npm run build; then
    #         echo "npm run build failed in $repo" | tee -a "$LOG_FILE"
    #         continue
    #     fi
    # fi

    if [ -d "$TARGET_DIR/app/Filament/Admin/Resources" ]; then
    
        if [ -d "$TARGET_DIR/app/Filament/Admin/Resources/Users" ]; then
            echo "Updating Users resource schema from boilerplate to $repo..."
            cp "$BOILERPLATE_DIR/app/Filament/Admin/Resources/Users/Schemas/UserForm.php" "$TARGET_DIR/app/Filament/Admin/Resources/Users/Schemas/UserForm.php" || echo "Failed to copy UserForm schema to $repo";
        fi
    fi  

    # // copy membership model
    # if [ -f "$BOILERPLATE_DIR/app/Models/Membership.php" ]; then
    #     echo "Copying Membership model from boilerplate to $repo..."
    #     cp "$BOILERPLATE_DIR/app/Models/Membership.php" "$TARGET_DIR/app/Models/Membership.php" || echo "Failed to copy Membership model to $repo";  
    # fi

    # # copy permissions config and Role model
    # if [ -f "$BOILERPLATE_DIR/config/permissions.php" ]; then
    #     echo "Copying permissions config from boilerplate to $repo..."
    #     cp "$BOILERPLATE_DIR/config/permissions.php" "$TARGET_DIR/config/permissions.php" || echo "Failed to copy permissions config to $repo";  
    # fi
    # if [ -f "$BOILERPLATE_DIR/app/Models/Role.php" ]; then
    #     echo "Copying Role model from boilerplate to $repo..."
    #     cp "$BOILERPLATE_DIR/app/Models/Role.php" "$TARGET_DIR/app/Models/Role.php" || echo "Failed to copy Role model to $repo";  
    # fi  

    # # --- Placeholder for custom updates ---
    # # Example: Copy selective boilerplate files
    # # cp -r "$PROJECTS_DIR/boilerplate-laravel/app/." "$TARGET_DIR/app/"
    # # cp -r "$PROJECTS_DIR/boilerplate-laravel/config/." "$TARGET_DIR/config/"

    # Commit & push any changes
    if ! git diff --quiet || ! git diff --cached --quiet; then
        echo "Committing and pushing changes..."
        git add -A
        git commit -m "Update dependencies and boilerplate changes" || true
        git push || { echo "git push failed in $repo"; continue; }
    else
        echo "No changes to commit"
    fi

done

echo "===== Update completed: $(date "+%Y-%m-%d %H:%M:%S") =====" | tee -a "$LOG_FILE"