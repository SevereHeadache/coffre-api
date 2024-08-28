echo "SELECT 'CREATE DATABASE $TEST_DB' WHERE NOT EXISTS
    (SELECT FROM pg_database WHERE datname = '$TEST_DB')\gexec" \
| psql -U $POSTGRES_USER
