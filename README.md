Installation

1. install composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

2. install coop
Set-ExecutionPolicy RemoteSigned -Scope CurrentUser
irm get.scoop.sh | iex

3. install symfony cli
scoop install symfony-cli

Create a project
php composer.phar create-project symfony/website-skeleton project_name

Add ORM to project
php composer.phar require symfony/orm-pack

Add maker bundle
php composer.phar require --dev symfony/maker-bundle

Add annotations for routing
php composer.phar require annotations

Add apache support - this create the .htaccess file inside public folder. Xamp must be configured to point to the public folder
php composer.phar require symfony/apache-pack

Start the symfony server
php composer.phar require --dev symfony/web-server-bundle
symfony server:start

Database

Add entry in .env
DATABASE_URL="mysql://root:@127.0.0.1:3306/new_aluve_db?serverVersion=mariadb-{slq_server_version}&charset=utf8mb4"
Comment out the postgres connection string 

import existing database entities
php bin/console doctrine:mapping:import --force "App\Entity" annotation --path=src/Entity_new

delete all reservations
TRUNCATE TABLE `reservation_notes`;
TRUNCATE TABLE `reservation_add_ons`;
TRUNCATE TABLE `payments`;
TRUNCATE TABLE `cleaning`;
delete FROM `reservations` where id > 0;
delete FROM `guest` where id > 0;
INSERT INTO `reservations` (`id`, `check_in`, `check_out`, `room_id`, `guest_id`, `status`, `additional_info`, `received_on`, `updated_on`, `uid`, `origin`, `origin_url`, `check_in_status`, `cleanliness_score`, `checked_in_time`, `check_in_time`, `check_out_time`) VALUES (NULL, '2022-06-28', '2022-06-29', '2', '64', 'confirmed', '', '2022-06-28 16:09:32', '2022-06-28 16:09:32', '', 'aibnb', 'aibnb', 'not checked in', '0', NULL, '14:00', '10:00')
