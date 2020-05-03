Steps to make it work


1. configure the DATABASE_URL into the .env file
------------------------------------------------
Enter your database user, password and the name the database you want to create

DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name"

2 Create the database
---------------------
php bin/console doctrine:database:create

3. Create the database's tables
--------------------------------
php bin/console doctrine:migrations:migrate

4. start the server
--------------------
symfony server:start


- The encryption key is located into the file config\services.yaml


