#!/bin/bash
# Test a single migration or sequence of migrations

MIGRATION_FILE="$1"

if [ -z "$MIGRATION_FILE" ]; then
    echo "Usage: $0 <migration_file_or_number>"
    echo "Examples:"
    echo "  $0 2024_01_01_000000_create_users_table.php"
    echo "  $0 1    # Test first migration"
    echo "  $0 1-10 # Test migrations 1 through 10"
    exit 1
fi

# Ensure migrations table exists
echo "Ensuring migrations table exists..."
php artisan migrate:install --database=mysql 2>&1 | grep -v "Nothing" || true

# If it's a number or range
if [[ "$MIGRATION_FILE" =~ ^[0-9]+(-[0-9]+)?$ ]]; then
    migrations=($(ls -1 database/migrations/*.php | sort))

    if [[ "$MIGRATION_FILE" =~ ^([0-9]+)-([0-9]+)$ ]]; then
        # Range
        start=${BASH_REMATCH[1]}
        end=${BASH_REMATCH[2]}
        echo "Testing migrations $start through $end"

        for ((i=start-1; i<end && i<${#migrations[@]}; i++)); do
            file=$(basename "${migrations[$i]}")
            echo ""
            echo "[$((i+1))/${#migrations[@]}] Testing: $file"
            echo "---"

            start_time=$(date +%s.%N)

            if timeout 60 php artisan migrate --path="database/migrations/$file" --database=mysql --force; then
                end_time=$(date +%s.%N)
                duration=$(echo "$end_time - $start_time" | bc)
                echo "✅ Success (${duration}s)"
            else
                echo "❌ Failed or timeout"
                exit 1
            fi
        done
    else
        # Single number
        idx=$((MIGRATION_FILE - 1))
        if [ $idx -ge 0 ] && [ $idx -lt ${#migrations[@]} ]; then
            file=$(basename "${migrations[$idx]}")
            echo "Testing migration #$MIGRATION_FILE: $file"

            start_time=$(date +%s.%N)

            if timeout 60 php artisan migrate --path="database/migrations/$file" --database=mysql --force; then
                end_time=$(date +%s.%N)
                duration=$(echo "$end_time - $start_time" | bc)
                echo "✅ Success (${duration}s)"
            else
                echo "❌ Failed or timeout"
                exit 1
            fi
        else
            echo "Error: Invalid migration number"
            exit 1
        fi
    fi
else
    # Specific file
    echo "Testing: $MIGRATION_FILE"

    start_time=$(date +%s.%N)

    if timeout 60 php artisan migrate --path="database/migrations/$MIGRATION_FILE" --database=mysql --force; then
        end_time=$(date +%s.%N)
        duration=$(echo "$end_time - $start_time" | bc)
        echo "✅ Success (${duration}s)"
    else
        echo "❌ Failed or timeout"
        exit 1
    fi
fi
