<?
$_db = mysqli_connect( 
	'localhost', // Адресс хоста 
	'', // Имя пользователя
	'', // Используемый пароль
	'' ); // База данных для запросов по умолчанию
mysqli_query ( $_db, 'SET NAMES `utf8mb4`' ); // Устанавливаем кодировку по умолчанию

ini_set('mysql.connect_timeout', 300);
ini_set('default_socket_timeout', 300); 