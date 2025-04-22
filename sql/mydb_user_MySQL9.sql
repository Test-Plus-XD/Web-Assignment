-- Create User
CREATE USER 'mydb_user'@'localhost' IDENTIFIED WITH caching_sha2_password BY 'password';

-- Grant privileges
GRANT ALL PRIVILEGES ON *.* TO 'mydb_user'@'localhost' WITH GRANT OPTION;

ALTER USER 'mydb_user'@'localhost'
    REQUIRE NONE
    WITH MAX_QUERIES_PER_HOUR 0
         MAX_CONNECTIONS_PER_HOUR 0
         MAX_UPDATES_PER_HOUR 0
         MAX_USER_CONNECTIONS 0;

GRANT ALL PRIVILEGES ON `mydb`.* TO 'mydb_user'@'localhost';